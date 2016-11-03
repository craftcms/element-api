<?php
namespace Craft;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\TransformerAbstract;

/**
 * Element API controller
 */
class ElementApiController extends BaseController
{
	/**
	 * @var Allows anonymous access to this controller's actions.
	 * @access protected
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
				'transformer' => 'Craft\ElementApi_ElementTransformer',
			],
			craft()->config->get('defaults', 'elementapi'),
			$config
		);

		if ($config['pageParam'] == 'p')
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
		$fractal->setSerializer(new ArraySerializer());

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

		if ($config['first'])
		{
			$element = $criteria->first();

			if (!$element)
			{
				throw new HttpException(404);
			}

			$resource = new Item($element, $transformer);
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

			$resource = new Collection($elements, $transformer);
			$resource->setPaginator($paginator);
		}
		else
		{
			$resource = new Collection($criteria, $transformer);
		}

		JsonHelper::sendJsonHeaders();

		$data = $fractal->createData($resource);

		// Fire an 'onBeforeSendData' event
		craft()->elementApi->onBeforeSendData(new Event($this, [
			'data' => $data,
		]));

		echo $data->toJson();

		// End the request
		craft()->end();
	}

	/**
	 * Calls a given function. If any params are given, they will be mapped to the function's arguments.
	 *
	 * @param $func The function to call
	 * @param $params Any params that should be mapped to function arguments
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
