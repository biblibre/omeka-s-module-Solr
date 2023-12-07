<?php

namespace Solr\Transformation;

use Laminas\Form\Element\Select;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ValueRepresentation;

class ConvertResourceToString extends AbstractTransformation
{
    public function getLabel(): string
    {
        return 'Convert resource to string'; // @translate
    }

    public function getConfigForm(PhpRenderer $view, array $transformationData): string
    {
        $select = new Select('resource_field');
        $select->setLabel('Resource field'); // @translate
        $select->setValueOptions([
            'title' => 'Resource title', // @translate
            'id' => 'Resource ID', // @translate
        ]);
        $select->setValue($transformationData['resource_field'] ?? 'title');
        $select->setAttribute('data-transformation-data-key', 'resource_field');

        return $view->formRow($select);
    }

    public function transform(array $values, array $transformationData): array
    {
        $resource_field = $transformationData['resource_field'] ?? 'title';

        $transformedValues = [];
        foreach ($values as $value) {
            $resource = null;
            if ($value instanceof ValueRepresentation) {
                $resource = $value->valueResource();
            } elseif ($value instanceof AbstractResourceEntityRepresentation) {
                $resource = $value;
            }

            if (isset($resource)) {
                if ($resource_field == 'title') {
                    $transformedValues[] = $resource->title();
                    continue;
                } elseif ($resource_field == 'id') {
                    $transformedValues[] = $resource->id();
                    continue;
                }
            }

            $transformedValues[] = $value;
        }

        return $transformedValues;
    }
}
