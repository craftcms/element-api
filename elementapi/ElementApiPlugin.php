<?php
namespace Craft;

class ElementApiPlugin extends BasePlugin
{
	/**
	 * @return mixed
	 */
	public function getName()
	{
		return Craft::t('Element API');
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return '1.2.1';
	}

	/**
	 * @return string
	 */
	public function getSchemaVersion()
	{
		return '1.0.0';
	}

	/**
	 * @return string
	 */
	public function getDeveloper()
	{
		return 'Pixel & Tonic';
	}

	/**
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return 'http://pixelandtonic.com';
	}

	/**
	 * @return string
	 */
	public function getPluginUrl()
	{
		return 'https://github.com/pixelandtonic/ElementAPI';
	}

	/**
	 * @return string
	 */
	public function getDocumentationUrl()
	{
		return $this->getPluginUrl().'/blob/master/README.md';
	}

	/**
	 * @return string
	 */
	public function getReleaseFeedUrl()
	{
		return 'https://raw.githubusercontent.com/pixelandtonic/ElementAPI/master/releases.json';
	}

	/**
	 * @return array
	 */
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
