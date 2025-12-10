<?php
namespace Solr\Service\Site\BlockLayout;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Solr\Site\BlockLayout\Glossr;

class GlossrFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Glossr($services->get('FormElementManager'), $services->get('Omeka\ApiManager'));
    }
}
