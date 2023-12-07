<?php

namespace Solr\Transformation;

class StripHtmlTags extends AbstractTransformation
{
    public function getLabel(): string
    {
        return 'Strip HTML tags'; // @translate
    }

    public function transform(array $values, array $transformationData): array
    {
        return array_map(fn ($v) => strip_tags((string) $v), $values);
    }
}
