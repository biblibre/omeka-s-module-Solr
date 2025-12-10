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
class Glossr extends AbstractBlockLayout
{
    protected $formElementManager;

    protected $apiManager;

    public function __construct(FormElementManager $formElementManager, \Omeka\Api\Manager $apiManager)
    {
        $this->formElementManager = $formElementManager;
        $this->setApiManager($apiManager);
    }

    public function getLabel()
    {
        return 'Glossr'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
    {
        $defaults = [
            'o:index_id' => '',
            'search_field' => '',
            'resource_class_field' => '',
            'resource_class' => '',
            'letters_list_position' => ['before', 'after'],
            'sort_by' => 'alphabetic',
            'sort_order' => 'asc',
            'display_letters' => [],
            'display_total' => [],
            'per_page' => 10,
        ];

        $data = $block ? $block->data() + $defaults : $defaults;

        $form = new Form();
        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][o:index_id]',
            'type' => 'Select',
            'options' => [
                'label' => 'Index', // @translate
                'value_options' => $this->getIndexesOptions(),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $indexesAux = $this->getApiManager()->search('search_indexes')->getContent();
        $allowed = [];
        $valueOptions = [];

        foreach ($indexesAux as $index)
        {
            $searchFields = $index->adapter()->getAvailableFacetFields($index);
            $allowed[($index->id())] = array_column($searchFields,  'name');

            if (!empty($data['o:index_id']) && is_numeric($data['o:index_id']) && ($index->id() == intval($data['o:index_id'])))
            {
                $valueOptions = array_column($searchFields, 'label', 'name');
            }
        } 

        if ($valueOptions) {
            $form->add([
                'name' => 'o:block[__blockIndex__][o:data][search_field]',
                'required' => true,
                'type' => \Laminas\Form\Element\Select::class,
                'options' => [
                    'label' => 'Search fields', // @translate
                    'value_options' => $valueOptions,
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\Callback::class,
                        'options' => [
                            'callback' => function ($value, $context) use ($allowed) {

                                $type = $context['o:index_id'] ?? null;

                                return $type
                                    && isset($allowed[$type])
                                    && in_array($value, $allowed[$type]);
                            },
                            'message' => 'Incompatible field with selected index.', // @translate
                        ],
                    ],
                ],
            ]);

            $form->add([
                'name' => 'o:block[__blockIndex__][o:data][resource_class_field]',
                'required' => true,
                'type' => \Laminas\Form\Element\Select::class,
                'options' => [
                    'label' => 'Search fields', // @translate
                    'empty_option' => 'Add a resource class field', // @translate
                    'value_options' => $valueOptions,
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\Callback::class,
                        'options' => [
                            'callback' => function ($value, $context) use ($allowed) {

                                $type = $context['o:index_id'] ?? null;

                                return $type
                                    && isset($allowed[$type])
                                    && in_array($value, $allowed[$type]);
                            },
                            'message' => 'Incompatible field with selected index.', // @translate
                        ],
                    ],
                ],
            ]);
        }
        else {
            $form->add([
                'name' => 'o:block[__blockIndex__][o:data][search_field]',
                'required' => true,
                'type' => \Laminas\Form\Element\Select::class,
                'options' => [
                    'label' => 'Search fields', // @translate
                    'empty_option' => 'Add a search field', // @translate
                    'value_options' => $valueOptions,
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\Callback::class,
                        'options' => [
                            'callback' => function ($value, $context) use ($allowed) {

                                $type = $context['o:index_id'] ?? null;

                                return $type
                                    && isset($allowed[$type])
                                    && in_array($value, $allowed[$type]);
                            },
                            'message' => 'Incompatible field with selected index.', // @translate
                        ],
                    ],
                ],
            ]);
            $form->add([
                'name' => 'o:block[__blockIndex__][o:data][resource_class_field]',
                'required' => false,
                'type' => \Laminas\Form\Element\Select::class,
                'options' => [
                    'label' => 'Resource class field', // @translate
                    'empty_option' => 'Add a resource class field', // @translate
                    'value_options' => $valueOptions,
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\Callback::class,
                        'options' => [
                            'callback' => function ($value, $context) use ($allowed) {

                                $type = $context['o:index_id'] ?? null;

                                return $type
                                    && isset($allowed[$type])
                                    && in_array($value, $allowed[$type]);
                            },
                            'message' => 'Incompatible field with selected index.', // @translate
                        ],
                    ],
                ],
            ]);
        }

        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][resource_class]',
            'type' => \Omeka\Form\Element\ResourceClassSelect::class,
            'options' => [
                    'label' => 'Resource classes', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'required' => false,
                    'class' => 'chosen-select',
                    'multiple' => 'multiple',
                    'data-placeholder' => 'Select resource classes…', // @translate
                    'data-fieldset' => 'args',
                ],
        ]);

        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][letters_list_position]',
            'type' => OptionalMultiCheckbox::class,
            'options' => [
                'label' => 'Position of index of letters', // @translate
                'value_options' => [
                    'before' => 'Before', // @translate
                    'after' => 'After', // @translate
                ],
            ],
        ]);

        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][display_letters]',
            'type' => OptionalMultiCheckbox::class,
            'options' => [
                'label' => 'Display letters between results?', // @translate
                'value_options' => [
                    'yes' => 'Display letters', // @translate
                ],
            ],
        ]);

        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][display_total]',
            'type' => OptionalMultiCheckbox::class,
            'options' => [
                'label' => 'Display total between results?', // @translate
                'value_options' => [
                    'yes' => 'Display total', // @translate
                ],
            ],
        ]);

        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][sort_order]',
            'type' => \Laminas\Form\Element\Select::class,
            'options' => [
                'label' => 'In what order to display the letters of the Glossr', // @translate
                'value_options' => [
                    'alphabetic' => 'Alphabetic', // @translate
                    'total' => 'Total', // @translate
                    'chronological' => 'Chronological' // @translate
                ],
            ],
        ]);

        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][sort_by]',
            'type' => \Laminas\Form\Element\Select::class,
            'options' => [
                'label' => 'Ascending or descending order for order of letters of the Glossr', // @translate
                'value_options' => [
                    'asc' => 'Ascending', // @translate
                    'desc' => 'Descending', // @translate
                ],
            ],
        ]);

        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][per_page]',
            'type' => \Laminas\Form\Element\Range::class,
            'options' => [
                'label' => 'Number of max results per letter', // @translate
            ],
            'attributes' => [
                'min' => 1,
                'max' => 50,
            ],
        ]);

        $form->setData([
            'o:block[__blockIndex__][o:data][o:index_id]' => $data['o:index_id'],
            'o:block[__blockIndex__][o:data][search_field]' => $data['search_field'],
            'o:block[__blockIndex__][o:data][resource_class_field]' => $data['resource_class_field'],
            'o:block[__blockIndex__][o:data][resource_class]' => $data['resource_class'],
            'o:block[__blockIndex__][o:data][letters_list_position]' => $data['letters_list_position'],
            'o:block[__blockIndex__][o:data][sort_order]' => $data['sort_order'],
            'o:block[__blockIndex__][o:data][sort_by]' => $data['sort_by'],
            'o:block[__blockIndex__][o:data][display_letters]' => $data['display_letters'],
            'o:block[__blockIndex__][o:data][display_total]' => $data['display_total'],
            'o:block[__blockIndex__][o:data][per_page]' => $data['per_page'],
        ]);

        return $view->formCollection($form);
    }

    protected function getIndexesOptions()
    {
        $api = $this->getApiManager();

        $indexes = $api->search('search_indexes')->getContent();
        $options = [
            '' => 'None', // @translate
        ];
        foreach ($indexes as $index) {
            $options[$index->id()] =
                sprintf('%s (%s)', $index->name(), $index->adapterLabel());
        }

        return $options;
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
