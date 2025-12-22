<?php

/*
 * Copyright BibLibre, 2025
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Solr\Form\Admin;

use Laminas\Form\Form;
use Solr\Form\Element\OptionalMultiCheckbox;

class GlossrForm extends Form
{
    protected $apiManager;

    public function init()
    {
        $this->add([
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

        $pagesAux = $this->getApiManager()->search('search_pages')->getContent();
        $pagesValueOptions = [];
        foreach ($pagesAux as $page) {
            $pagesValueOptions[strval($page->id())] = sprintf('%s (%s)', $page->name(), $page->siteUrl($this->getOption('site-slug')));
        }

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][search_page]',
            'type' => \Laminas\Form\Element\Select::class,
            'options' => [
                    'label' => 'Search page to use', // @translate
                    'value_options' => $pagesValueOptions,
                ],
                'attributes' => [
                    'required' => false,
                ],
        ]);

        $indexesAux = $this->getApiManager()->search('search_indexes')->getContent();
        $allowedFacetable = [];
        $allowedSearchable = [];
        $allowedSortable = [];
        $valueOptionsFacetable = [];
        $valueOptionsSearchable = [];
        $valueOptionsSortable = [];

        foreach ($indexesAux as $index) {
            $facetFields = $index->adapter()->getAvailableFacetFields($index);
            $searchFields = $index->adapter()->getAvailableSearchFields($index);
            $sortFields = $index->adapter()->getAvailableSortFields($index);
            $allowedFacetable[($index->id())] = array_column($facetFields, 'name');
            $allowedSearchable[($index->id())] = array_column($searchFields, 'name');
            $allowedSearchable[($index->id())] = array_column($sortFields, 'name');

            if (!empty($this->getOption('o:index_id'))
                && is_numeric($this->getOption('o:index_id'))
                && ($index->id() == intval($this->getOption('o:index_id')))) {
                $valueOptionsFacetable = array_column($facetFields, 'label', 'name');
                $valueOptionsSearchable = array_column($searchFields, 'label', 'name');
                $valueOptionsSortable = array_column($sortFields, 'label', 'name');
            }
        }

        if ($valueOptionsFacetable) {
            $this->add([
                'name' => 'o:block[__blockIndex__][o:data][search_field]',
                'required' => true,
                'type' => \Laminas\Form\Element\Select::class,
                'options' => [
                    'label' => 'Search fields', // @translate
                    'value_options' => $valueOptionsFacetable,
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\Callback::class,
                        'options' => [
                            'callback' => function ($value, $context) use ($allowedFacetable) {
                                $type = $context['o:index_id'] ?? null;

                                return $type
                                    && isset($allowedFacetable[$type])
                                    && in_array($value, $allowedFacetable[$type]);
                            },
                            'message' => 'Incompatible field with selected index.', // @translate
                        ],
                    ],
                ],
            ]);
        } else {
            $this->add([
                'name' => 'o:block[__blockIndex__][o:data][search_field]',
                'required' => true,
                'type' => \Laminas\Form\Element\Select::class,
                'options' => [
                    'label' => 'Search fields', // @translate
                    'empty_option' => 'Add a search field', // @translate
                    'value_options' => $valueOptionsFacetable,
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\Callback::class,
                        'options' => [
                            'callback' => function ($value, $context) use ($allowedFacetable) {
                                $type = $context['o:index_id'] ?? null;

                                return $type
                                    && isset($allowedFacetable[$type])
                                    && in_array($value, $allowedFacetable[$type]);
                            },
                            'message' => 'Incompatible field with selected index.', // @translate
                        ],
                    ],
                ],
            ]);
        }

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][resource_class_field]',
            'required' => true,
            'type' => \Laminas\Form\Element\Select::class,
            'options' => [
                'label' => 'Resource class field', // @translate
                'empty_option' => 'None', // @translate
                'value_options' => $valueOptionsFacetable,
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\Callback::class,
                    'options' => [
                        'callback' => function ($value, $context) use ($allowedFacetable) {
                            $type = $context['o:index_id'] ?? null;

                            return $type
                                && isset($allowedFacetable[$type])
                                && in_array($value, $allowedFacetable[$type]);
                        },
                        'message' => 'Incompatible field with selected index.', // @translate
                    ],
                ],
            ],
        ]);

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][resource_class]',
            'type' => \Omeka\Form\Element\ResourceClassSelect::class,
            'options' => [
                    'label' => 'Resource classes', // @translate
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

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][language_field]',
            'required' => true,
            'type' => \Laminas\Form\Element\Select::class,
            'options' => [
                'label' => 'Language field', // @translate
                'empty_option' => 'None', // @translate
                'value_options' => $valueOptionsFacetable,
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\Callback::class,
                    'options' => [
                        'callback' => function ($value, $context) use ($allowedFacetable) {
                            $type = $context['o:index_id'] ?? null;

                            return $type
                                && isset($allowedFacetable[$type])
                                && in_array($value, $allowedFacetable[$type]);
                        },
                        'message' => 'Incompatible field with selected index.', // @translate
                    ],
                ],
            ],
        ]);

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][language]',
            'type' => 'text',
            'options' => [
                    'label' => 'Language', // @translate
                ],
        ]);

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][custom_query]',
            'type' => 'text',
            'options' => [
                'label' => 'Custom query parameters', // @translate
            ],
            ]);

        $this->add([
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

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][display_letters]',
            'type' => OptionalMultiCheckbox::class,
            'options' => [
                'label' => 'Display letters between results?', // @translate
                'value_options' => [
                    'yes' => 'Display letters', // @translate
                ],
            ],
        ]);

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][display_total]',
            'type' => OptionalMultiCheckbox::class,
            'options' => [
                'label' => 'Display total between results?', // @translate
                'value_options' => [
                    'yes' => 'Display total', // @translate
                ],
            ],
        ]);

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][sort_order]',
            'type' => \Laminas\Form\Element\Select::class,
            'options' => [
                'label' => 'In what order to display the letters of the Glossr', // @translate
                'value_options' => [
                    'alphabetic' => 'Alphabetic', // @translate
                    'total' => 'Total', // @translate
                    'chronological' => 'Chronological', // @translate
                ],
            ],
        ]);

        $this->add([
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

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][date_field]',
            'required' => true,
            'type' => \Laminas\Form\Element\Select::class,
            'options' => [
                'label' => 'Date field for sorting chronologically', // @translate
                'empty_option' => 'None', // @translate
                'value_options' => $valueOptionsSortable,
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\Callback::class,
                    'options' => [
                        'callback' => function ($value, $context) use ($allowedSortable) {
                            $type = $context['o:index_id'] ?? null;

                            return $type
                                && isset($allowedSortable[$type])
                                && in_array($value, $allowedSortable[$type]);
                        },
                        'message' => 'Incompatible field with selected index.', // @translate
                    ],
                ],
            ],
        ]);
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
}
