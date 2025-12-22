<?php

namespace Solr\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class GlossrFacetLink extends AbstractHelper
{
    public function __invoke($name, $facet, $searchPage, $siteSlug, $query,
                                $resourceClassField = null, $resourceClasses = [], $languageField = null, $languages = "")
    {
        $view = $this->getView();

        $active = false;
        if (isset($query['limit'][$name]) && false !== array_search($facet['value'], $query['limit'][$name])) {
            $values = $query['limit'][$name];
            $values = array_filter($values, function ($v) use ($facet) {
                return $v != $facet['value'];
            });
            $query['limit'][$name] = $values;
            $active = true;
        } else {
            $query['limit'][$name][] = $facet['value'];
        }

        if (!empty($resourceClassField) && !empty($resourceClasses)) {
            $query['limit'][$resourceClassField] = $resourceClasses;
        }

        if (!empty($langueField) && !empty($languages)) {
            $query['limit'][$languageField] = explode('|', $languages);
        }

        unset($query['page']);

        $url = $view->url('search-page-' . $searchPage->id(), ['site-slug' => $siteSlug], ['query' => $query]);

        return $view->partial('solr/glossr-facet-link', [
            'url' => $url,
            'active' => $active,
            'name' => $name,
            'value' => $facet['value'],
            'count' => $facet['count'],
        ]);
    }
}
