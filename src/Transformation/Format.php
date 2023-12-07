<?php

namespace Solr\Transformation;

use Laminas\Form\Element\Select;
use Laminas\View\Renderer\PhpRenderer;
use Solr\ValueFormatter\Manager as ValueFormatterManager;

class Format extends AbstractTransformation
{
    protected ValueFormatterManager $valueFormatterManager;

    public function __construct(ValueFormatterManager $valueFormatterManager)
    {
        $this->valueFormatterManager = $valueFormatterManager;
    }

    public function getLabel(): string
    {
        return 'Format (deprecated)'; // @translate
    }

    public function getSortableString()
    {
        return 'zzz'; // sort in last position
    }

    public function getConfigForm(PhpRenderer $view, array $transformationData): string
    {
        $elementName = 'formatter';
        $element = new Select($elementName);
        $element->setLabel('Formatter'); // @translate
        $element->setValueOptions($this->getFormatterOptions());
        if (isset($transformationData[$elementName])) {
            $element->setValue($transformationData[$elementName]);
        }
        $element->setAttribute('data-transformation-data-key', $elementName);

        return $view->formRow($element);
    }

    public function transform(array $values, array $transformationData): array
    {
        $formatter = $transformationData['formatter'] ?? null;
        if (!isset($formatter)) {
            return $values;
        }

        $valueFormatter = $this->valueFormatterManager->get($formatter);

        return array_map(fn ($v) => $valueFormatter->format((string) $v), $values);
    }

    protected function getFormatterOptions(): array
    {
        $options = [];
        foreach ($this->valueFormatterManager->getRegisteredNames() as $name) {
            $valueFormatter = $this->valueFormatterManager->get($name);
            $options[$name] = $valueFormatter->getLabel();
        }

        return $options;
    }
}
