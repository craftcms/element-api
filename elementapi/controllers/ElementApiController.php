<?php
namespace Craft;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\Serializer\JsonApiSerializer;
use League\Fractal\Serializer\SerializerAbstract;
use League\Fractal\TransformerAbstract;

/**
 * Element API controller
 */
class ElementApiController extends BaseController
{
	/**
	 * @var bool Allows anonymous access to this controller's actions.
	 */
	protected $allowAnonymous = true;

	/**
	 * Returns the requested elements as JSON
	 *
	 * @param callable|null $configFactory A function for generating the config
	 * @param array|null    $config        The API endpoint configuration
	 *
	 * @throws Exception
	 * @throws HttpException
	 */
	public function actionGetElements($configFactory = null, array $config = null)
	{
		if ($configFactory !== null)
		{
			$params = craft()->urlManager->getRouteParams();
			$variables = (isset($params['variables']) ? $params['variables'] : null);
			$config = $this->_callWithParams($configFactory, $variables);
		}

		// Merge in default config options
		$config = array_merge(
			[
				'paginate' => true,
				'pageParam' => 'page',
				'elementsPerPage' => 100,
				'first' => false,
				'transformer' => [
					'class' => 'Craft\ElementApi_ElementTransformer',
				],
				'cache' => false,
			],
			craft()->config->get('defaults', 'elementapi'),
			$config
		);

		// Before anything else, check the cache
		if ($config['cache']) {
			$cacheKey = 'elementapi:'.craft()->request->getPath().':'.craft()->request->getQueryStringWithoutPath();
			$cache = craft()->cache->get($cacheKey);

			if ($cache !== false)
			{
				JsonHelper::sendJsonHeaders();
				echo $cache;
				craft()->end();
			}
		}

		if ($config['pageParam'] === 'p')
		{
			throw new Exception('The pageParam setting cannot be set to "p" because that’s the parameter Craft uses to check the requested path.');
		}

		if (!isset($config['elementType']))
		{
			throw new Exception('Element API configs must specify the elementType.');
		}

		/** @var ElementCriteriaModel $criteria */
		$criteria = craft()->elements->getCriteria($config['elementType'], [
			'limit' => null
		]);

		if (!empty($config['criteria']))
		{
			$criteria->setAttributes($config['criteria']);
		}

		// Load Fractal
		$pluginPath = craft()->path->getPluginsPath().'elementapi/';
		require $pluginPath.'vendor/autoload.php';
		$fractal = new Manager();

		// Set the serializer
		$serializer = isset($config['serializer']) ? $config['serializer'] : null;
		if (!$serializer instanceof SerializerAbstract)
		{
			switch ($serializer)
			{
				case 'dataArray':
					$serializer = new DataArraySerializer();
					break;
				case 'jsonApi':
					$serializer = new JsonApiSerializer();
					break;
				case 'jsonFeed':
					Craft::import('plugins.elementapi.ElementApi_JsonFeedV1Serializer');
					$serializer = new ElementApi_JsonFeedV1Serializer();
					break;
				default:
					$serializer = new ArraySerializer();
			}
		}
		$fractal->setSerializer($serializer);

		// Set the includes
		$includes = isset($config['includes']) ? $config['includes'] : [];
		$fractal->parseIncludes($includes);

		// Set the excludes
		$excludes = isset($config['excludes']) ? $config['excludes'] : [];
		$fractal->parseExcludes($excludes);

		// Define the transformer
		if (is_callable($config['transformer']) || $config['transformer'] instanceof TransformerAbstract)
		{
			$transformer = $config['transformer'];
		}
		else
		{
			Craft::import('plugins.elementapi.ElementApi_ElementTransformer');
			$transformer = Craft::createComponent($config['transformer']);
		}

		$resourceKey = isset($config['resourceKey']) ? $config['resourceKey'] : null;

		if ($config['first'])
		{
			$element = $criteria->first();

			if (!$element)
			{
				throw new HttpException(404);
			}

			$resource = new Item($element, $transformer, $resourceKey);
		}
		else if ($config['paginate'])
		{
			// Create the paginator
			require $pluginPath.'ElementApi_PaginatorAdapter.php';
			$paginator = new ElementApi_PaginatorAdapter($config['elementsPerPage'], $criteria->total(), $config['pageParam']);

			// Fetch this page's elements
			$criteria->offset = $config['elementsPerPage'] * ($paginator->getCurrentPage() - 1);
			$criteria->limit = $config['elementsPerPage'];
			$elements = $criteria->find();
			$paginator->setCount(count($elements));

			$resource = new Collection($elements, $transformer, $resourceKey);
			$resource->setPaginator($paginator);
		}
		else
		{
			$resource = new Collection($criteria, $transformer, $resourceKey);
		}

		// Set any custom meta values
		if (isset($config['meta']))
		{
			$resource->setMeta($config['meta']);
		}

		$data = $fractal->createData($resource);

		// Fire an 'onBeforeSendData' event
		craft()->elementApi->onBeforeSendData(new Event($this, [
			'data' => $data,
		]));

		// Serialize and JSON-encode the data
		$data = $data->toArray();
		JsonHelper::sendJsonHeaders();
		$jsonOptions = isset($config['jsonOptions']) ? $config['jsonOptions'] : 0;
		$output = json_encode($data, $jsonOptions);

		// Cache it?
		if ($config['cache'])
		{
			if (is_int($config['cache']))
			{
				$expire = $config['cache'];
			}
			else if (is_string($config['cache']))
			{
				$expire = DateTimeHelper::timeFormatToSeconds($config['cache']);
			}
			else
			{
				$expire = null;
			}

			craft()->cache->set($cacheKey, $output, $expire);
		}

		// Output and the request
		echo $output;
		craft()->end();
	}

	/**
	 * Calls a given function. If any params are given, they will be mapped to the function's arguments.
	 *
	 * @param callable $func   The function to call
	 * @param array    $params Any params that should be mapped to function arguments
	 *
	 * @return mixed The result of the function
	 */
	private function _callWithParams($func, $params)
	{
		if (!$params)
		{
			return call_user_func($func);
		}

		$ref = new \ReflectionFunction($func);
		$args = [];

		foreach ($ref->getParameters() as $param)
		{
			$name = $param->getName();

			if (isset($params[$name]))
			{
				if ($param->isArray())
				{
					$args[] = is_array($params[$name]) ? $params[$name] : [$params[$name]];
				}
				else if (!is_array($params[$name]))
				{
					$args[] = $params[$name];
				}
				else
				{
					return false;
				}
			}
			else if ($param->isDefaultValueAvailable())
			{
				$args[] = $param->getDefaultValue();
			}
			else
			{
				return false;
			}
		}

		return $ref->invokeArgs($args);
	}
}
