<?php

namespace craft\elementapi;

use craft\base\Element;
use craft\base\ElementInterface;
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
        // Get the serialized custom field values
        $fields = $element->getSerializedFieldValues();

        // Get the element attributes that aren't custom fields
        /** @var Element $element */
        $attributes = array_diff($element->attributes(), array_keys($fields));

        // Return the element as an array merged with its serialized custom field values
        return array_merge($element->toArray($attributes), $fields);
    }
}
