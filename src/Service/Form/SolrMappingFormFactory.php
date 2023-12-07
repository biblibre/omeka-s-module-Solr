<?php
namespace Solr\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Solr\Form\Admin\SolrMappingForm;

class SolrMappingFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $valueExtractorManager = $services->get('Solr\ValueExtractorManager');
        $transformationManager = $services->get('Solr\TransformationManager');

        $form = new SolrMappingForm(null, $options);
        $form->setValueExtractorManager($valueExtractorManager);
        $form->setTransformationManager($transformationManager);

        return $form;
    }
}
