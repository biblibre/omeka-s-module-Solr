<?php

namespace Solr\Site\BlockLayout;

use Laminas\Form\FormElementManager;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Solr\Form\Element\OptionalMultiCheckbox;
use Search\Querier\Exception\QuerierException;
use Search\Query;
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
            'date_field' => '',
        ];

        $data = $block ? $block->data() + $defaults : $defaults;

        $form = $this->formElementManager->get(GlossrForm::class, ['o:index_id' => $data['o:index_id'], 
                                                                                'site-slug' => $block->page()->site()->slug()]);

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
            'o:block[__blockIndex__][o:data][date_field]' => $data['date_field'],
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
        foreach ($indexesAux as $index)
        {
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
        $languageFieldName = $block->dataValue('languages_field');
        $languages = $block->dataValue('language_class');
        $indexResponse = $this->apiManager->read('search_indexes', $indexId)->getContent();
        $sortBy = $block->dataValue('sort_by');
        $sortOrder = $block->dataValue('sort_order');
        $dateField = $block->dataValue('date_field');

        if (empty($indexResponse)) {
            $view->messenger()->addError(sprintf('Index with id %s not found.', $indexId));
            return null;
        }

        $page = $this->apiManager->read('search_pages', $pageId)->getContent();
        if (empty($page)) {
            $view->messenger()->addError(sprintf('Page with id %s not found.', $pageId));
            return null;
        }

        $site = $view->currentSite();

        $customQuery = [];
        parse_str($customQueryInput, $customQuery);

        $formAdapter = $page->formAdapter();
        if (!isset($formAdapter)) {
            $formAdapterName = $page->formAdapterName();
            $msg = sprintf("Form adapter '%s' not found", $formAdapterName);
            throw new RuntimeException($msg);
        }

        $searchPageSettings = $page->settings();
        $searchFormSettings = [];
        if (isset($searchPageSettings['form'])) {
            $searchFormSettings = $searchPageSettings['form'];
        }

        $query = $formAdapter->toQuery($customQuery, $searchFormSettings);

        $this->logger->debug("Custom query after toQuery()");
        $this->logger->debug(json_encode($query));

        $querier = $indexResponse->querier();

        $response = null;
        $facets = [];

        $letters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
        try {
            $response = $querier->glossaire($indexId, $site, $fieldName, $resourceClassFieldName,
            $resourceClasses, $languageFieldName, $languages, $query);
            if (array_key_exists($fieldName, $response->getFacetCounts()))
                $facets[$fieldName] = $response->getFacetCounts()[$fieldName];
        } catch (QuerierException $e) {
            throw $e;
        }

        $facetLetter = [];
        if (array_key_exists($fieldName, $response->getFacetCounts())) {
            foreach ($letters as $letter) {
                // $this->logger->debug(sprintf('[Glossr] processing letter "%s"', $letter));
                $facetLetter[$letter] = []; // init
                foreach ($facets[$fieldName] as $facetValue) {
                    // $this->logger->debug(sprintf('[Glossr] facet value:' . PHP_EOL . '%s', json_encode($facetValue)));
                    if (str_starts_with(strtolower($facetValue['value']), $letter)) {
                        $facetLetter[$letter][] = $facetValue;
                    }
                }
                if ($sortOrder == 'alphabetic') {
                    if ($sortBy == 'asc') {
                        usort($facetLetter[$letter], function ($a, $b) { return $a['value'] < $b['value']; });
                    }
                    else {
                        usort($facetLetter[$letter], function ($a, $b) { return $a['value'] > $b['value']; });
                    }
                }
                else if ($sortOrder == 'total') {
                    if ($sortBy == 'asc') {
                        $facetLetter[$letter] = array_reverse($facetLetter[$letter]);
                    }
                    else {
                        // nothing to do!
                    }
                }
            }
        }

        /*foreach ($responses as $index => $response) {
            $results = $response->getResults($resourceName);
            if (!empty($results)) {
                foreach () {
                    foreach ($results as $result) {
                        $resource = $this->api()->read($resourceName, $result['id'])->getContent();

                    }
                }
            }
        }*/

    /*<div class="resource-list">
        <?php foreach ($results as $result): ?>
            <?php  ?>
            <?php $highlights = $response->getHighlights($resourceName, $result['id']); ?>
            <?php echo $this->partial('search/resource', [
                'resource' => $resource,
                'site' => $site,
                'tag' => 'div',
                'highlights' => $highlights,
            ]); ?>
        <?php endforeach; ?>
    </div>
            $response->getHighlights(); */

        $lettersPosition = is_array($block->dataValue('letters_list_position')) ?
            $block->dataValue('letters_list_position')
            : [$block->dataValue('letters_list_position')];

        $lettersBetweenResults = is_array($block->dataValue('display_letters')) ?
            $block->dataValue('display_letters')
            : [$block->dataValue('display_letters')];

        $totalBetweenResults = is_array($block->dataValue('display_total')) ?
            $block->dataValue('display_total')
            : [$block->dataValue('display_total')];

        // throw new \Exception(sprintf('%s', json_encode($facetLetter)));

        return $view->partial('solr/block-layout/glossaire', [
            'site' => $site,
            'response' => $response,
            'letters' => $letters,
            'lettersListPosition' => $lettersPosition,
            'lettersBetweenResults' => $lettersBetweenResults,
            'totalBetweenResults' => $totalBetweenResults,
            'facetLetter' => $facetLetter,
            // 'sortOptions' => $sortOptions,
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
}
