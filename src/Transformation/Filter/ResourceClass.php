<?php

namespace Solr\Transformation\Filter;

use Laminas\Form\Factory;
use Laminas\Form\FormElementManager;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Form\Element\ResourceClassSelect;
use Solr\Transformation\AbstractTransformation;

class ResourceClass extends AbstractTransformation
{
    protected FormElementManager $formElementManager;

    public function __construct(FormElementManager $formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    public function getLabel(): string
    {
        return 'Filter resources by class'; // @translate
    }

    public function getConfigForm(PhpRenderer $view, array $transformationData): string
    {
        $elementName = 'resource_classes';
        $factory = new Factory($this->formElementManager);
        $element = $factory->create([
            'name' => $elementName,
            'type' => ResourceClassSelect::class,
            'options' => [
                'label' => 'Resource classes', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'multiple' => true,
                'class' => 'chosen-select',
                'data-transformation-data-key' => $elementName,
            ],
        ]);
        $element->setValue($transformationData['resource_classes'] ?? []);

        return $view->formRow($element) . '<script>$(".chosen-select").chosen(chosenOptions);</script>';
    }

    public function transform(array $values, array $transformationData): array
    {
        $resource_classes = $transformationData['resource_classes'] ?? [];
        if (empty($resource_classes)) {
            return $values;
        }

        $filteredValues = [];
        foreach ($values as $value) {
            if ($this->shouldKeepValue($value, $resource_classes)) {
                $filteredValues[] = $value;
            }
        }

        return $filteredValues;
    }

    protected function getResourceClassOptions(): array
    {
        $options = [];
        foreach ($this->api->search('resource_classes')->getContent() as $resourceClass) {
            $options[$resourceClass->term()] = $resourceClass->label();
        }

        return $options;
    }

    protected function shouldKeepValue($value, $resource_classes)
    {
        if ($value instanceof ValueRepresentation) {
            $resource = $value->valueResource();
        } elseif ($value instanceof AbstractResourceEntityRepresentation) {
            $resource = $value;
        }

        if (!isset($resource)) {
            return true;
        }

        $resourceClass = $resource->resourceClass();
        if ($resourceClass && in_array($resourceClass->term(), $resource_classes)) {
            return true;
        }

        return false;
    }
}
