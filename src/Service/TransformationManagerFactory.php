<?php
namespace Solr\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Service\Exception\ConfigException;
use Solr\Transformation\Manager;

class TransformationManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['solr_transformations'])) {
            throw new ConfigException('Missing solr transformations configuration');
        }

        if (!empty($config['solr_value_formatters'])) {
            $config['solr_transformations']['factories']['Solr\Transformation\Format'] = Transformation\FormatFactory::class;
        }

        return new Manager($serviceLocator, $config['solr_transformations']);
    }
}
