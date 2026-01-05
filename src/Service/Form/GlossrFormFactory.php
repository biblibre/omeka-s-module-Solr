<?php
namespace Solr\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Solr\Form\Admin\GlossrForm;

class GlossrFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new GlossrForm(null, $options);
        $form->setApiManager($services->get('Omeka\ApiManager'));
        return $form;
    }
}
