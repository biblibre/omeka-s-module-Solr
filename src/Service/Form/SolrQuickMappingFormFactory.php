<?php
namespace Solr\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Solr\Form\Admin\SolrQuickMappingForm;

class SolrQuickMappingFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new SolrQuickMappingForm(null, $options ?? []);

        $form->setApiManager($services->get('Omeka\ApiManager'));
        $form->setTransformationManager($services->get('Solr\TransformationManager'));
        $form->setValueExtractorManager($services->get('Solr\ValueExtractorManager'));

        return $form;
    }
}
