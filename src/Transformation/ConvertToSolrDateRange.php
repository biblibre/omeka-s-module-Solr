<?php

namespace Solr\Transformation;

use Laminas\View\Renderer\PhpRenderer;

class ConvertToSolrDateRange extends AbstractTransformation
{
    public function getLabel(): string
    {
        return 'Convert to Solr date range'; // @translate
    }

    public function getConfigForm(PhpRenderer $view, array $transformationData): string
    {
        $elementName = 'exclude_unmatching';
        $element = new \Laminas\Form\Element\Checkbox($elementName);
        $element->setLabel('Exclude unmatching values'); // @translate
        $element->setUseHiddenElement(true);
        $element->setCheckedValue('1');
        $element->setUncheckedValue('0');
        $element->setValue($transformationData[$elementName] ?? '1');
        $element->setAttribute('data-transformation-data-key', $elementName);

        return $view->formRow($element);
    }

    public function transform(array $values, array $transformationData): array
    {
        $exclude_unmatching = $transformationData['exclude_unmatching'] ?? '1';

        $transformedValues = [];
        foreach ($values as $value) {
            $stringValue = (string) $value;

            $start = $end = null;
            $matches = [];
            if (preg_match('|^\s*(\d+)\s*[-/]\s*(\d+)\s*$|', $stringValue, $matches)) {
                $start = $matches[1];
                $end = $matches[2];
            } elseif (preg_match('|^\s*(\d+)\s*$|', $stringValue, $matches)) {
                $start = $end = $matches[1];
            }

            if (isset($start) && isset($end)) {
                $transformedValues[] = "[$start TO $end]";
            } elseif (!$exclude_unmatching) {
                $transformedValues[] = $value;
            }
        }

        return $transformedValues;
    }
}
