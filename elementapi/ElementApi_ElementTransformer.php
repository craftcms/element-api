<?php
namespace Craft;

use League\Fractal\TransformerAbstract;

class ElementApi_ElementTransformer extends TransformerAbstract
{
    public function transform(BaseElementModel $element)
	{
		$values = array();

		foreach ($element->attributeNames() as $name)
		{
			$values[$name] = $this->transformAttribute($element->getAttribute($name));
		}

		return $values;
	}

	protected function transformAttribute($value)
	{
		// Convert dates to ISO-8601
		if ($value instanceof \DateTime)
		{
			return $value->format(\DateTime::ISO8601);
		}
		else
		{
			return ModelHelper::packageAttributeValue($value);
		}
	}
}
