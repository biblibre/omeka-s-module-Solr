<?php

namespace Solr\Service;

use Interop\Container\ContainerInterface;
use Laminas\Http\Client as HttpClient;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Solr\SolrClient;

class SolrClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $httpClient = new HttpClient();

        $solrClient = new SolrClient($httpClient);

        return $solrClient;
    }
}
