<?php

namespace craft\elementapi;

use League\Fractal\Resource\ResourceInterface;

/**
 * Resource adapter interface.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
interface ResourceAdapterInterface
{
    /**
     * Returns the Fractal data resource.
     *
     * @return ResourceInterface
     */
    public function getResource(): ResourceInterface;
}
