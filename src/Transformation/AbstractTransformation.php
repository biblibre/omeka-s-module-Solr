<?php

namespace Solr\Transformation;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\ServiceManager\SortableInterface;

abstract class AbstractTransformation implements SolrTransformationInterface, SortableInterface
{
    public function getConfigForm(PhpRenderer $view, array $transformationData): string
    {
        return '';
    }

    public function getSortableString()
    {
        return $this->getLabel();
    }
}
