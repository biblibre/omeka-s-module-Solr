<?php

namespace Solr\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Solr\Transformation\Manager;

class Transformation extends AbstractHelper
{
    protected $transformationManager;

    public function __construct(Manager $transformationManager)
    {
        $this->transformationManager = $transformationManager;
    }

    public function label(array $transformationData): string
    {
        $name = $transformationData['name'];
        $transformation = $this->transformationManager->get($name);

        return $transformation->getLabel();
    }

    public function configForm(array $transformationData): string
    {
        $name = $transformationData['name'];
        $transformation = $this->transformationManager->get($name);

        return $transformation->getConfigForm($this->getView(), $transformationData);
    }
}
