<?php

namespace craft\elementapi;

use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use League\Fractal\TransformerAbstract;

/**
 * Element transformer class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class ElementTransformer extends TransformerAbstract
{
    public function transform(ElementInterface $element): array
    {
        return ArrayHelper::toArray($element);
    }
}
