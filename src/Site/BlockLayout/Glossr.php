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

        $indexes = [];
        foreach ($indexesAux as $index)
        {
            $searchFields = $index->adapter()->getAvailableSearchFields($index);
            $indexes[($index->id())] = array_column($searchFields, 'label', 'name');
        }
         
        $view->headScript()->appendScript(
        'window.availableFields = ' . json_encode($indexes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
        );

        $view->headScript()->appendFile($view->assetUrl('js/glossaire-form.js', 'Search'));
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {   
        $indexId = $block->dataValue('o:index_id');
        $fieldName = $block->dataValue('search_field');
        $indexResponse = $this->apiManager->read('search_indexes', $indexId)->getContent();

        if (empty($indexResponse))
        {
            $view->messenger()->addError(sprintf('Index with id %s not found.', $indexId));
            return $view;
        }

        $site = $view->currentSite();

        $querier = $indexResponse->querier();

        $responses = null;
        $facets = [];

        $letters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
        try {
            foreach($letters as $letter)
            {
                $responses[] = $querier->glossaire($indexId, $site, $letter, $fieldName);
                if (array_key_exists($fieldName, end($responses)->getFacetCounts()))
                    $facets[][$fieldName] = end($responses)->getFacetCounts()[$fieldName];
                else
                    $facets[] = null;
            }
        } catch (QuerierException $e) {
            /*$view->messenger()->addError('Query error: ' . $e->getMessage());
            return $view;*/
            throw $e;
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

        return $view->partial('solr/block-layout/glossaire', [
            'site' => $site,
            'responses' => $responses,
            'letters' => $letters,
            'lettersListPosition' => $lettersPosition,
            'lettersBetweenResults' => $lettersBetweenResults,
            'totalBetweenResults' => $totalBetweenResults,
            'facets' => $facets,
            // 'sortOptions' => $sortOptions,
        ]);
    }

    public function prepareRender(PhpRenderer $view): void
    {
        $view->headLink()
            ->appendStylesheet($view->assetUrl('css/reference.css', 'Solr'));
    }
}
