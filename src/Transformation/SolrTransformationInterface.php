<?php

namespace Solr\Transformation;

use Laminas\View\Renderer\PhpRenderer;

interface SolrTransformationInterface
{
    public function getLabel(): string;

    public function getConfigForm(PhpRenderer $view, array $transformationData): string;

    public function transform(array $values, array $transformationData): array;
}
