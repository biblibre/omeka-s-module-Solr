<?php

namespace Solr\Service\Transformation;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Solr\Transformation\Format;

class FormatFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Format($container->get('Solr\ValueFormatterManager'));
    }
}
