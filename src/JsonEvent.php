<?php

namespace craft\elementapi;

use yii\base\Event;

/**
 * JsonEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.6
 */
class JsonEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The JSON data associated with the event
     */
    public $json;
}
