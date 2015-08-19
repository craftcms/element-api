<?php
namespace Craft;

class ElementApiPlugin extends BasePlugin
{
	public function getName()
	{
		return Craft::t('Element API');
	}

	public function getVersion()
	{
		return '1.0';
	}

	public function getDeveloper()
	{
		return 'Pixel & Tonic';
	}

	public function getDeveloperUrl()
	{
		return 'http://pixelandtonic.com';
	}

	public function registerSiteRoutes()
	{
		$routes = [];
		$endpoints = craft()->config->get('endpoints', 'elementapi');

		foreach ($endpoints as $pattern => $config)
		{
			// Convert Yii 2-style route subpatterns to normal regex subpatterns
			$pattern = preg_replace('/<(\w+):([^>]+)>/', '(?P<\1>\2)', $pattern);

			if (is_callable($config))
			{
				$params = ['configFactory' => $config];
			}
			else
			{
				$params = ['config' => $config];
			}

			$routes[$pattern] = [
				'action' => 'elementApi/getElements',
				'params' => $params,
			];
		}

		return $routes;
	}
}
