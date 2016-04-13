<?php
namespace Craft;

/**
 * Element API Service
 */
class ElementApiService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	/**
	 * Executor for single-element queries. Will attempt to retrieve via the
	 * cache, if enabled, in the config.
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function executeSingleElementQuery($criteria)
	{
		if ($this->cacheEnabled() && ($result = $this->getCachedQuery($criteria)))
		{
			return $result;
		}
		else
		{
			$element = $criteria->first();
			$this->setCachedQuery($criteria, $element);
			return $element;
		}
	}

	/**
	 * Executor for other element queries. Will attempt to retrieve via the
	 * cache, if enabled, in the config.
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function executeElementQuery($criteria)
	{
		if ($this->cacheEnabled() && ($results = $this->getCachedQuery($criteria)))
		{
			return $results;
		}
		else
		{
			$elements = $criteria->find();
			$this->setCachedQuery($criteria, $elements);
			return $elements;
		}
	}

	/**
	 * Tries the cache for the given criteria and returns them, if the key exists.
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function getCachedQuery($criteria)
	{
		return craft()->cache->get($this->getCacheId($criteria->getAttributes()));
	}

	/**
	 * Save the results of an ElementAPI query to the cache.
	 *
	 * @param ElementCriteriaModel $criteria
	 * @param mixed $data
	 * @param Integer $duration
	 *
	 * @return mixed
	 */
	public function setCachedQuery($criteria, $data)
	{
		return craft()->cache->set($this->getCacheId($criteria->getAttributes()), $data, craft()->config->get('cacheDuration', 'elementapi'));
	}

	/**
	 * Composes a reliable string for identifying cached criteria.
	 *
	 * @param Array $attributes
	 *
	 * @return string
	 */
	private function getCacheId($attributes)
	{
		return 'elementapi_' . md5(json_encode($attributes));
	}

	/**
	 * Shorthand way of determining whether attempts to set and get queries
	 * from the cache are allowed.
	 *
	 * @return boolean
	 */
	private function cacheEnabled()
	{
		return craft()->config->get('cache', 'elementapi');
	}


	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeSendData' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSendData(Event $event)
	{
		$this->raiseEvent('onBeforeSendData', $event);
	}
}
