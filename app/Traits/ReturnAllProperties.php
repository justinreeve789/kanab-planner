<?php

namespace App\Traits;

trait ReturnAllProperties
{
    public function toArrayAll()
    {
        $properties = [];
        $reflector = new \ReflectionClass($this);

        // Get the properties declared in the current class (not inherited).
        foreach ($reflector->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE) as $property) {
            if (!$property->isStatic()) {
                $propertyName = $property->getName();
                if (in_array($propertyName, $this->ignoreProperties())) {
                    continue;
                }
                $properties[$propertyName] = $this->$propertyName;
            }
        }
        return $properties;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArrayAll();
    }

    private function ignoreProperties(): array
    {
        return [
            "appends",
            "attributeCastCache",
            "attributes",
            "casts",
            "changes",
            "classCastCache",
            "connection",
            "dateFormat",
            "dispatchesEvents",
            "escapeWhenCastingToString",
            "exists",
            "fillable",
            "guarded",
            "hidden",
            "incrementing",
            "keyType",
            "observables",
            "original",
            "perPage",
            "preventsLazyLoading",
            "primaryKey",
            "relations",
            "table",
            "timestamps",
            "touches",
            "usesUniqueIds",
            "visible",
            "wasRecentlyCreated",
            "with",
            "withCount",
        ];
    }
}
