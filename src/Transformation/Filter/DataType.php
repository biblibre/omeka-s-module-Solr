<?php

namespace Solr\Transformation\Filter;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\ValueRepresentation;
use Solr\Transformation\AbstractTransformation;

class DataType extends AbstractTransformation
{
    protected $dataTypeManager;

    public function __construct(\Omeka\DataType\Manager $dataTypeManager)
    {
        $this->dataTypeManager = $dataTypeManager;
    }

    public function getLabel(): string
    {
        return 'Filter by data type'; // @translate
    }

    public function getConfigForm(PhpRenderer $view, array $transformationData): string
    {
        $select = new \Laminas\Form\Element\Select('data_types');
        $select->setLabel('Data types'); // @translate
        $select->setValueOptions($this->getDataTypeOptions());
        $select->setValue($transformationData['data_types'] ?? []);
        $select->setOption('info', 'Only selected data types will be used. Selecting none is the same as selecting all. Only relevant when the source is a property.'); // @translate
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', 'chosen-select');
        $select->setAttribute('data-transformation-data-key', 'data_types');

        return $view->formRow($select) . '<script>$(".chosen-select").chosen(chosenOptions);</script>';
    }

    public function transform(array $values, array $transformationData): array
    {
        $data_types = $transformationData['data_types'] ?? [];
        if (empty($data_types)) {
            return $values;
        }

        $filteredValues = [];
        foreach ($values as $value) {
            if (!$value instanceof ValueRepresentation || in_array($value->type(), $data_types)) {
                $filteredValues[] = $value;
            }
        }

        return $filteredValues;
    }

    protected function getDataTypeOptions(): array
    {
        $options = [];
        foreach ($this->dataTypeManager->getRegisteredNames() as $name) {
            $options[$name] = $name;
        }

        return $options;
    }
}
