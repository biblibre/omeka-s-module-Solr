<?php

namespace Solr\Site\BlockLayout;

use Laminas\Form\FormElementManager;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Search\Querier\Exception\QuerierException;
use Solr\Form\Admin\GlossrForm;

class Glossr extends AbstractBlockLayout
{
    protected $formElementManager;

    protected $apiManager;

    protected $logger;

    public function __construct(FormElementManager $formElementManager, \Omeka\Api\Manager $apiManager, $logger)
    {
        $this->formElementManager = $formElementManager;
        $this->setApiManager($apiManager);
        $this->logger = $logger;
    }

    public function getLabel()
    {
        return 'Glossr'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
    {
        $defaults = [
            'o:index_id' => '',
            'search_page' => '',
            'search_field' => '',
            'resource_class_field' => '',
            'resource_class' => '',
            'language_field' => '',
            'language' => '',
            'custom_query' => '',
            'letters_list_position' => ['before', 'after'],
            'sort_by' => 'alphabetic',
            'sort_order' => 'asc',
            'display_letters' => [],
            'display_total' => [],

        ];

        $data = $block ? $block->data() + $defaults : $defaults;

        $siteSlug = $block ? $block->page()->site()->slug() : $site->slug();
        $form = $this->formElementManager->get(GlossrForm::class, ['o:index_id' => $data['o:index_id'], 'site-slug' => $siteSlug]);

        $form->setData([
            'o:block[__blockIndex__][o:data][o:index_id]' => $data['o:index_id'],
            'o:block[__blockIndex__][o:data][search_page]' => $data['search_page'],
            'o:block[__blockIndex__][o:data][search_field]' => $data['search_field'],
            'o:block[__blockIndex__][o:data][resource_class_field]' => $data['resource_class_field'],
            'o:block[__blockIndex__][o:data][resource_class]' => $data['resource_class'],
            'o:block[__blockIndex__][o:data][language_field]' => $data['language_field'],
            'o:block[__blockIndex__][o:data][language]' => $data['language'],
            'o:block[__blockIndex__][o:data][letters_list_position]' => $data['letters_list_position'],
            'o:block[__blockIndex__][o:data][sort_order]' => $data['sort_order'],
            'o:block[__blockIndex__][o:data][sort_by]' => $data['sort_by'],
            'o:block[__blockIndex__][o:data][display_letters]' => $data['display_letters'],
            'o:block[__blockIndex__][o:data][display_total]' => $data['display_total'],
            'o:block[__blockIndex__][o:data][custom_query]' => $data['custom_query'],

        ]);

        return $view->formCollection($form);
    }

    public function setApiManager($apiManager)
    {
        $this->apiManager = $apiManager;
    }

    public function getApiManager()
    {
        return $this->apiManager;
    }

    public function prepareForm(PhpRenderer $view)
    {
        $indexesAux = $this->getApiManager()->search('search_indexes')->getContent();

        $indexesSearch = [];
        $indexesFacet = [];
        $indexesSort = [];
        foreach ($indexesAux as $index) {
            $searchFields = $index->adapter()->getAvailableSearchFields($index);
            $facetFields = $index->adapter()->getAvailableFacetFields($index);
            $sortFields = $index->adapter()->getAvailableSortFields($index);
            $indexesSearch[($index->id())] = array_column($searchFields, 'label', 'name');
            $indexesFacet[($index->id())] = array_column($facetFields, 'label', 'name');
            $indexesSort[($index->id())] = array_column($sortFields, 'label', 'name');
        }

        $view->headScript()->appendScript(
            'window.availableSearchFields = ' . json_encode($indexesSearch, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
        );

        $view->headScript()->appendScript(
            'window.availableFacetFields = ' . json_encode($indexesFacet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
        );

        $view->headScript()->appendScript(
            'window.availableSortFields = ' . json_encode($indexesSort, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
        );

        $view->headScript()->appendFile($view->assetUrl('js/glossaire-form.js', 'Solr'));
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $indexId = $block->dataValue('o:index_id');
        $pageId = $block->dataValue('search_page');
        $customQueryInput = $block->dataValue('custom_query');
        $fieldName = $block->dataValue('search_field');
        $resourceClassFieldName = $block->dataValue('resource_class_field');
        $resourceClasses = $block->dataValue('resource_class');
        $languageFieldName = $block->dataValue('language_field');
        $languages = $block->dataValue('language');

        $sortBy = $block->dataValue('sort_order') ?: 'alphabetic';  // 'alphabetic', 'total'
        $sortOrder = $block->dataValue('sort_by') ?: 'asc';  // 'asc', 'desc'

        try {
            $indexResponse = $this->apiManager->read('search_indexes', $indexId)->getContent();
        } catch (\Exception $e) {
            $view->messenger()->addError(sprintf('Index with id %s not found.', $indexId));
            return '';
        }

        try {
            $page = $this->apiManager->read('search_pages', $pageId)->getContent();
        } catch (\Exception $e) {
            $view->messenger()->addError(sprintf('Page with id %s not found.', $pageId));
            return '';
        }

        $site = $view->currentSite();

        $customQuery = [];
        if (!empty($customQueryInput)) {
            parse_str($customQueryInput, $customQuery);
        }

        $formAdapter = $page->formAdapter();
        if (!$formAdapter) {
            $formAdapterName = $page->formAdapterName();
            $view->messenger()->addError(sprintf("Form adapter '%s' not found", $formAdapterName));
            return '';
        }

        $searchPageSettings = $page->settings();
        $searchFormSettings = [];
        if (isset($searchPageSettings['form'])) {
            $searchFormSettings = $searchPageSettings['form'];
        }

        $query = $formAdapter->toQuery($customQuery, $searchFormSettings);
        $querier = $indexResponse->querier();

        $response = null;
        $facets = [];

        $letters = range('a', 'z');
        try {
            $response = $querier->glossaire(
                $indexId,
                $site,
                $fieldName,
                $resourceClassFieldName,
                $resourceClasses,
                $languageFieldName,
                $languages,
                $query
            );
            if (array_key_exists($fieldName, $response->getFacetCounts())) {
                $facets[$fieldName] = $response->getFacetCounts()[$fieldName];
            }
        } catch (QuerierException $e) {
            $view->messenger()->addError('An error occurred while executing the search query.');
            return '';
        }

        $facetLetter = [];
        if (array_key_exists($fieldName, $response->getFacetCounts())) {
            foreach ($letters as $letter) {
                $facetLetter[$letter] = [];
                foreach ($facets[$fieldName] as $facetValue) {
                    if (str_starts_with(strtolower($facetValue['value']), $letter)) {
                        $facetLetter[$letter][] = $facetValue;
                    }
                }

                if (!empty($facetLetter[$letter])) {
                    $this->sortFacetsForLetter($facetLetter[$letter], $sortBy, $sortOrder);
                }
            }
        }

        $lettersPosition = is_array($block->dataValue('letters_list_position')) ?
            $block->dataValue('letters_list_position')
            : [$block->dataValue('letters_list_position')];

        $lettersBetweenResults = is_array($block->dataValue('display_letters')) ?
            $block->dataValue('display_letters')
            : [$block->dataValue('display_letters')];

        $totalBetweenResults = is_array($block->dataValue('display_total')) ?
            $block->dataValue('display_total')
            : [$block->dataValue('display_total')];

        return $view->partial('solr/block-layout/glossaire', [
            'site' => $site,
            'response' => $response,
            'letters' => $letters,
            'lettersListPosition' => $lettersPosition,
            'lettersBetweenResults' => $lettersBetweenResults,
            'totalBetweenResults' => $totalBetweenResults,
            'facetLetter' => $facetLetter,
            'fieldName' => $fieldName,
            'searchPage' => $page,
            'siteSlug' => $site->slug(),
            'customQuery' => $customQuery,
            'languageField' => $languageFieldName,
            'languages' => $languages,
            'resourceClassField' => $resourceClassFieldName,
            'resourceClasses' => $resourceClasses,
        ]);
    }

    public function prepareRender(PhpRenderer $view): void
    {
        $view->headLink()
            ->appendStylesheet($view->assetUrl('css/reference.css', 'Solr'));
    }

    protected function sortFacetsForLetter(array &$facets, string $sortBy, string $sortOrder): void
    {
        if (empty($facets)) {
            return;
        }

        // Ensure we have valid sort parameters
        $sortBy = $sortBy ?: 'alphabetic';
        $sortOrder = $sortOrder ?: 'asc';

        switch ($sortBy) {
            case 'alphabetic':
                usort($facets, function ($a, $b) use ($sortOrder) {
                    $result = strcasecmp($a['value'], $b['value']);
                    return $sortOrder === 'desc' ? -$result : $result;
                });
                break;

            case 'total':
                usort($facets, function ($a, $b) use ($sortOrder) {
                    $countA = $a['count'] ?? 0;
                    $countB = $b['count'] ?? 0;
                    $result = $countB <=> $countA;
                    return $sortOrder === 'asc' ? -$result : $result;
                });
                break;

            default:
                break;
        }
    }
}
