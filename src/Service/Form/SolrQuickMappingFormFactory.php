<?php
namespace Solr\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Solr\Form\Admin\SolrQuickMappingForm;

class SolrQuickMappingFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new SolrQuickMappingForm;
        $form->setTranslator($services->get('MvcTranslator'));

        if (empty($options['solr_node_id'])) {
            return $form;
        }

        $solrNode = $services->get('Omeka\ApiManager')->read('solr_nodes', $options['solr_node_id'])->getContent();
        $rawDynamicFields = $solrNode->schema()->getDynamicFields();

        $dynamicFields = [];
        foreach ($rawDynamicFields as $dynamicField) {
            $dynamicFields[] = $dynamicField['name'];
        }

        $properties = $services->get('Omeka\ApiManager')->search('properties')->getContent();
        $terms = [];
        foreach ($properties as $property) {
            $terms[] = $property->term();
        }

        $form->setOption('terms', $terms);
        $form->setOption('dynamic_fields', $dynamicFields);

        $transformationManager = $services->get('Solr\TransformationManager');
        $form->setTransformationManager($transformationManager);

        return $form;
    }
}
