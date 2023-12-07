<?php

namespace Solr\Service\View\Helper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Solr\View\Helper\Transformation;

class TransformationFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $transformationManager = $services->get('Solr\TransformationManager');

        $transformation = new Transformation($transformationManager);

        return $transformation;
    }
}
