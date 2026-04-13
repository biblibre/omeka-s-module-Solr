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
            $searchFields = $services->get('Omeka\ApiManager')->search('solr_search_fields', ['solr_node_id' => $options['solr_node_id']])->getContent();
            foreach ($searchFields as $searchField) {
                $textFields = $searchField->textFields();
                if (!empty($textFields)) {
                    foreach (explode(' ', $textFields) as $field) {
                        $field = trim($field);
                        if ($field !== '' && !in_array($field, $mappedFields)) {
                            $mappedFields[] = $field;
                        }
                    }
                }
            }
            $form->setOption('mapped_fields', $mappedFields);
        }

        return $form;
    }
}
