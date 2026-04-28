<?php

namespace Solr\Form\Admin;

use Laminas\Form\Form;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Api\Manager as ApiManager;
use Solr\Form\Element\Transformations;
use Solr\Transformation\Manager as TransformationManager;
use Solr\ValueExtractor\Manager as ValueExtractorManager;

class SolrQuickMappingForm extends Form
{
    protected ApiManager $apiManager;
    protected TransformationManager $transformationManager;
    protected ValueExtractorManager $valueExtractorManager;

    public function init()
    {
        $this->add([
            'name' => 'o:source',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Sources', // @translate
                'value_options' => $this->getSourceValueOptions(),
            ],
            'attributes' => [
                'id' => 'source',
                'multiple' => true,
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:field_name',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Solr fields', // @translate
                'value_options' => $this->getFieldNameValueOptions(),
            ],
            'attributes' => [
                'id' => 'field-name',
                'multiple' => true,
                'required' => true,
            ],
        ]);

        $settingsFieldset = new Fieldset('o:settings');

        $transformationNames = $this->transformationManager->getRegisteredNames($sortAlpha = true);
        $transformationValueOptions = [];
        foreach ($transformationNames as $name) {
            $transformation = $this->transformationManager->get($name);
            $transformationValueOptions[$name] = $transformation->getLabel();
        }

        $settingsFieldset->add([
            'name' => 'transformations',
            'type' => Transformations::class,
            'options' => [
                'label' => 'Transformations', // @translate
                'value_options' => $transformationValueOptions,
                'empty_option' => '',
            ],
        ]);

        $this->add($settingsFieldset);
    }

    public function setApiManager(ApiManager $apiManager): void
    {
        $this->apiManager = $apiManager;
    }

    public function setTransformationManager(TransformationManager $transformationManager): void
    {
        $this->transformationManager = $transformationManager;
    }

    public function setValueExtractorManager(ValueExtractorManager $valueExtractorManager): void
    {
        $this->valueExtractorManager = $valueExtractorManager;
    }

    protected function getSourceValueOptions(): array
    {
        $resourceName = $this->getOption('resource_name');
        $valueExtractor = $this->valueExtractorManager->get($resourceName);
        if (!isset($valueExtractor)) {
            return [];
        }

        return $this->getFieldsOptions($valueExtractor->getAvailableFields());
    }

    protected function getFieldsOptions($fields, $valuePrefix = '', $labelPrefix = ''): array
    {
        $options = [];

        foreach ($fields as $name => $field) {
            $label = $field['label'];
            $value = $name;

            if (!empty($field['children'])) {
                $childrenOptions = $this->getFieldsOptions($field['children'],
                    $valuePrefix ? "$valuePrefix/$value" : $value,
                    $labelPrefix ? "$labelPrefix / $label" : $label);
                $options = array_merge($options, $childrenOptions);
            } else {
                $value = $valuePrefix ? "$valuePrefix/$value" : $value;
                if ($labelPrefix) {
                    if (!isset($options[$labelPrefix])) {
                        $options[$labelPrefix] = ['label' => $labelPrefix];
                    }
                    $options[$labelPrefix]['options'][$value] = $label;
                } else {
                    $options[$value] = $label;
                }
            }
        }

        return $options;
    }

    protected function getFieldNameValueOptions(): array
    {
        $options = [];

        $solrNodeId = $this->getOption('solr_node_id');
        $solrNode = $this->apiManager->read('solr_nodes', $solrNodeId)->getContent();
        $dynamicFields = $solrNode->schema()->getDynamicFields();
        foreach ($dynamicFields as $dynamicField) {
            $name = $dynamicField['name'];
            $options[$name] = $name;
        }

        return $options;
    }
}
