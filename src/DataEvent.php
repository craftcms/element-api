<?php

namespace craft\elementapi;

use League\Fractal\Scope;
use yii\base\Event;

/**
 * Fractal data event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class DataEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Scope The Fractal data associated with the event
     */
    public $data;
}
