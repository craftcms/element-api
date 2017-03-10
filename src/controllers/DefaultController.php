<?php
/**
 * Element API plugin for Craft CMS 3.x
 *
 * Create a JSON API for your elements in Craft.
 *
 * @link      https://pixelandtonic.com/
 * @copyright Copyright (c) 2017 Pixel &amp; Tonic
 */

namespace craft\elementapi\controllers;

use Craft;
use craft\elementapi\DataEvent;
use craft\elementapi\Plugin;
use craft\web\Controller;
use League\Fractal\Manager;
use League\Fractal\Serializer\ArraySerializer;
use ReflectionFunction;
use yii\base\InvalidConfigException;
use yii\base\Response;
use yii\web\NotFoundHttpException;

/**
 * Element API controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  2.0
 */
class DefaultController extends Controller
{
    // Constants
    // =========================================================================

    /**
     * @event DataEvent The event that is triggered before sending the response data
     */
    const EVENT_BEFORE_SEND_DATA = 'beforeSendData';

    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * Returns the requested elements as JSON.
     *
     * @param string $pattern The endpoint URL pattern that was matched
     *
     * @return Response
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function actionIndex(string $pattern)
    {
        $plugin = Plugin::getInstance();
        $config = $plugin->getEndpoint($pattern);

        if (is_callable($config)) {
            $params = Craft::$app->getUrlManager()->getRouteParams();
            $config = $this->_callWithParams($config, $params);
        }

        $adapter = $plugin->createResourceAdapter($config);

        // Get the data resource
        try {
            $resource = $adapter->getResource();
        } catch (\Exception $e) {
            throw new NotFoundHttpException(null, 0, $e);
        }

        // Load Fractal and serialize the data
        $fractal = new Manager();
        $fractal->setSerializer(new ArraySerializer());
        $data = $fractal->createData($resource);

        // Fire a 'beforeSendData' event
        $this->trigger(self::EVENT_BEFORE_SEND_DATA, new DataEvent([
            'data' => $data,
        ]));

        return $this->asJson($data->toArray());
    }

    // Private Methods
    // =========================================================================

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
        if (!$params) {
            return $func();
        }

        $ref = new ReflectionFunction($func);
        $args = [];

        foreach ($ref->getParameters() as $param) {
            $name = $param->getName();

            if (isset($params[$name])) {
                if ($param->isArray()) {
                    $args[] = is_array($params[$name]) ? $params[$name] : [$params[$name]];
                } else if (!is_array($params[$name])) {
                    $args[] = $params[$name];
                } else {
                    return false;
                }
            } else if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                return false;
            }
        }

        return $ref->invokeArgs($args);
    }
}
