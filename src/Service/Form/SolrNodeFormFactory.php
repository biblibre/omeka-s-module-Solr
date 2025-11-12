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
            $mappedFields = [];
            $mappings = $services->get('Omeka\ApiManager')->search('solr_mappings', ['solr_node_id' => $options['solr_node_id']]);
            if ($mappings) {
                $mappings = $mappings->getContent();
                foreach ($mappings as $mapping) {
                    if (str_ends_with($mapping->fieldName(), '_txt'))
                    {
                        $mappedFields[] = $mapping->fieldName();
                    }
                }
            }
            $form->setOption('mapped_fields', $mappedFields);
        }

        return $form;
    }
}
