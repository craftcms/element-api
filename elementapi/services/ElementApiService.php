<?php
namespace Craft;

/**
 * Element API Service
 */
class ElementApiService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

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

	/**
	 * Fires an 'onAfterSendData' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onAfterSendData(Event $event)
	{
		$this->raiseEvent('onAfterSendData', $event);
	}
}
