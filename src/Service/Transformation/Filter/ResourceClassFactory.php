<?php

namespace Solr\Service\Transformation\Filter;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Solr\Transformation\Filter\ResourceClass;

class ResourceClassFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new ResourceClass($container->get('FormElementManager'));
    }
}
