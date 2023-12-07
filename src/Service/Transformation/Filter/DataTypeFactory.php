<?php

namespace Solr\Service\Transformation\Filter;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Solr\Transformation\Filter\DataType;

class DataTypeFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new DataType($container->get('Omeka\DataTypeManager'));
    }
}
