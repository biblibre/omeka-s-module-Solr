<?php
namespace Solr\Service\Controller;

use Interop\Container\ContainerInterface;
use Solr\Controller\ApiLocalController;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ApiLocalControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ApiLocalController(
            $services->get('Omeka\Paginator'),
            $services->get('Omeka\ApiManager')
        );
    }
}
