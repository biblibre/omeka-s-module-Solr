<?php
namespace Solr;

return [
    'controllers' => [
        'invokables' => [
            'Solr\Controller\Admin\Node' => Controller\Admin\NodeController::class,
            'Solr\Controller\Admin\SearchField' => Controller\Admin\SearchFieldController::class,
            'Solr\Controller\Admin\Transformations' => Controller\Admin\TransformationsController::class,
        ],
        'factories' => [
            'Solr\Controller\Admin\Mapping' => Service\Controller\MappingControllerFactory::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'solr_nodes' => Api\Adapter\SolrNodeAdapter::class,
            'solr_mappings' => Api\Adapter\SolrMappingAdapter::class,
            'solr_search_fields' => Api\Adapter\SolrSearchFieldAdapter::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Solr',
                'route' => 'admin/solr',
                'resource' => 'Solr\Controller\Admin\Node',
                'privilege' => 'browse',
                'class' => 'o-icon-search',
            ],
        ],
    ],
    'form_elements' => [
        'factories' => [
            'Solr\Form\Admin\SolrNodeForm' => Service\Form\SolrNodeFormFactory::class,
            'Solr\Form\Admin\SolrMappingForm' => Service\Form\SolrMappingFormFactory::class,
            'Solr\Form\Admin\SolrSearchFieldForm' => Service\Form\SolrSearchFieldFormFactory::class,
        ],
        'invokables' => [
            'Solr\Form\Admin\SolrMappingImportForm' => Form\Admin\SolrMappingImportForm::class,
            'Solr\Form\Element\Transformations' => Form\Element\Transformations::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'solr' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/solr',
                            'defaults' => [
                                '__NAMESPACE__' => 'Solr\Controller\Admin',
                                'controller' => 'Node',
                                'action' => 'browse',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'node' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/node[/:action]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Solr\Controller\Admin',
                                        'controller' => 'Node',
                                        'action' => 'browse',
                                    ],
                                ],
                            ],
                            'node-id' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/node/:id[/:action]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Solr\Controller\Admin',
                                        'controller' => 'Node',
                                        'action' => 'show',
                                    ],
                                    'constraints' => [
                                        'id' => '\d+',
                                    ],
                                ],
                            ],
                            'node-id-mapping' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/node/:nodeId/mapping',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Solr\Controller\Admin',
                                        'controller' => 'Mapping',
                                        'action' => 'browse',
                                    ],
                                ],
                            ],
                            'node-id-mapping-resource' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/node/:nodeId/mapping/:resourceName[/:action]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Solr\Controller\Admin',
                                        'controller' => 'Mapping',
                                        'action' => 'browseResource',
                                    ],
                                ],
                            ],
                            'node-id-mapping-resource-id' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/node/:nodeId/mapping/:resourceName/:id[/:action]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Solr\Controller\Admin',
                                        'controller' => 'Mapping',
                                        'action' => 'show',
                                    ],
                                    'constraints' => [
                                        'id' => '\d+',
                                    ],
                                ],
                            ],
                            'node-id-mapping-import' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/node/:nodeId/mapping/import',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Solr\Controller\Admin',
                                        'controller' => 'Mapping',
                                        'action' => 'import',
                                    ],
                                ],
                            ],
                            'node-id-fields' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/node/:nodeId/fields[/:action]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Solr\Controller\Admin',
                                        'controller' => 'SearchField',
                                        'action' => 'browse',
                                    ],
                                ],
                            ],
                            'node-id-fields-id' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/node/:nodeId/fields/:id/:action',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Solr\Controller\Admin',
                                        'controller' => 'SearchField',
                                    ],
                                ],
                            ],
                            'transformations' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/transformations/:action',
                                    'defaults' => [
                                        'controller' => 'Transformations',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Solr\ValueExtractorManager' => Service\ValueExtractorManagerFactory::class,
            'Solr\ValueFormatterManager' => Service\ValueFormatterManagerFactory::class,
            'Solr\SolrClient' => Service\SolrClientFactory::class,
            'Solr\TransformationManager' => Service\TransformationManagerFactory::class,
        ],
        'shared' => [
            'Solr\SolrClient' => false,
        ],
    ],
    'view_helpers' => [
        'delegators' => [
            'Laminas\Form\View\Helper\FormElement' => [
                Service\Delegator\FormElementDelegatorFactory::class,
            ],
        ],
        'factories' => [
            'solrTransformation' => Service\View\Helper\TransformationFactory::class,
        ],
        'invokables' => [
            'solrFormTransformations' => Form\View\Helper\FormTransformations::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'search_adapters' => [
        'factories' => [
            'solr' => Service\AdapterFactory::class,
        ],
    ],
    'solr_transformations' => [
        'factories' => [
            'Solr\Transformation\Filter\DataType' => Service\Transformation\Filter\DataTypeFactory::class,
            'Solr\Transformation\Filter\ResourceClass' => Service\Transformation\Filter\ResourceClassFactory::class,
        ],
        'invokables' => [
            'Solr\Transformation\ConvertResourceToString' => Transformation\ConvertResourceToString::class,
            'Solr\Transformation\ConvertToSolrDateRange' => Transformation\ConvertToSolrDateRange::class,
            'Solr\Transformation\StripHtmlTags' => Transformation\StripHtmlTags::class,
        ],
    ],
    'solr_value_extractors' => [
        'factories' => [
            'items' => Service\ValueExtractor\ItemValueExtractorFactory::class,
            'item_sets' => Service\ValueExtractor\ItemSetValueExtractorFactory::class,
        ],
    ],
    'solr_value_formatters' => [
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
];
