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

        $solrNode = $services->get('Omeka\ApiManager')->read('solr_nodes', $options['solr_node_id'])->getContent();
        $dynamicFieldsAux = $solrNode->schema()->getDynamicFields();

        $services->get('Omeka\Logger')->debug('[Solr] Dynamic fields:');
        $services->get('Omeka\Logger')->debug(json_encode($dynamicFieldsAux));

        $dynamicFields = [];
        foreach ($dynamicFieldsAux as  $dynamicField) {
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
