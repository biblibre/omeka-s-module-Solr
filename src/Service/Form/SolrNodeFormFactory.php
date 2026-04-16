<?php
namespace Solr\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Solr\Form\Admin\SolrNodeForm;

class SolrNodeFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new SolrNodeForm;
        $form->setTranslator($services->get('MvcTranslator'));

        if (!empty($options['solr_node_id'])) {
            $response = $services->get('Omeka\ApiManager')->search(
                'solr_mappings',
                ['solr_node_id' => $options['solr_node_id'], 'sort_by' => 'field_name'],
                ['returnScalar' => 'fieldName']
            );
            $fieldNames = $response->getContent();
            $mappedFields = array_values(array_unique($fieldNames));
            $form->setOption('mapped_fields', $mappedFields);
        }

        return $form;
    }
}
