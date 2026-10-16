<?php

namespace CraftCms\ElementApi\Events;

use League\Fractal\Scope;

/**
 * Dispatched before an endpoint's data is serialized and sent.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class SendingData
{
    /**
     * @param Scope $payload The Fractal data associated with the event
     */
    public function __construct(public Scope $payload) {}
}
