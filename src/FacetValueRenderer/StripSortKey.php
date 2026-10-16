<?php

namespace Solr\FacetValueRenderer;

use Laminas\View\Renderer\PhpRenderer;
use Search\FacetValueRenderer\FacetValueRendererInterface;
use Solr\SortKey;

class StripSortKey implements FacetValueRendererInterface
{
    public function getLabel(): string
    {
        return 'Strip sort key (Solr)'; // @translate
    }

    public function render(PhpRenderer $view, string $value): string
    {
        return $view->escapeHtml(SortKey::strip($value));
    }
}
