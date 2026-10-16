<?php

namespace Solr\Transformation;

use Solr\SortKey;

class PrefixWithSortKey extends AbstractTransformation
{
    public function getLabel(): string
    {
        return 'Prefix with sort key (for facets)'; // @translate
    }

    public function transform(array $values, array $transformationData): array
    {
        $transformedValues = [];
        foreach ($values as $value) {
            $stringValue = SortKey::trim((string) $value);
            if ($stringValue !== '') {
                $transformedValues[] = SortKey::prefix($stringValue);
            }
        }

        return $transformedValues;
    }
}
