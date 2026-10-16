<?php

namespace CraftCms\ElementApi\Contracts;

use League\Fractal\Resource\ResourceInterface;

/**
 * Resource adapter contract.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
interface ResourceAdapter
{
    /**
     * Returns the Fractal data resource.
     */
    public function getResource(): ResourceInterface;
}
