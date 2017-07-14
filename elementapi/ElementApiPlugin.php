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
		return '1.6.0';
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
		return 'https://github.com/craftcms/element-api/tree/v1';
	}

	/**
	 * @return string
	 */
	public function getDocumentationUrl()
	{
		return $this->getPluginUrl().'/README.md';
	}

	/**
	 * @return string
	 */
	public function getReleaseFeedUrl()
	{
		return 'https://raw.githubusercontent.com/craftcms/element-api/v1/releases.json';
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
