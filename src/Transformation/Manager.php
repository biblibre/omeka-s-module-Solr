<?php

namespace Solr\Transformation;

use Omeka\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $instanceOf = SolrTransformationInterface::class;
}
