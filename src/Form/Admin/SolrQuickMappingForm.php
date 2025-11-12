<?php

namespace Solr\Form\Admin;

use Laminas\Form\Form;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Solr\Form\Element\Transformations;
use Laminas\I18n\Translator\TranslatorAwareInterface;
use Laminas\I18n\Translator\TranslatorAwareTrait;

class SolrQuickMappingForm extends Form implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    protected $transformationManager;

    public function init()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'o:source',
            'type' => Element\Select::class,
            'options' => [
                'label' => $translator->translate('Source'),
                'value_options' => array_combine(
                                            $this->options['terms'],
                                            $this->options['terms']
                                        ),
            ],
            'attributes' => [
                'multiple' => true,
                'required' => true,
            ]
        ]);

        $this->add([
            'name' => 'o:field_name',
            'type' => Element\Select::class,
            'options' => [
                'label' => $translator->translate('Solr fields'),
                'value_options' => array_combine(
                                            $this->options['dynamic_fields'],
                                            $this->options['dynamic_fields']
                                        ),
            ],
            'attributes' => [
                'multiple' => true,
                'required' => true,
            ]
        ]);

        $settingsFieldset = new Fieldset('o:settings');

        $transformationNames = $this->transformationManager->getRegisteredNames($sortAlpha = true);
        $transformationValueOptions = [];
        foreach ($transformationNames as $name) {
            $transformation = $this->transformationManager->get($name);
            $transformationValueOptions[] = [
                'value' => $name,
                'label' => $transformation->getLabel(),
                'empty_option' => '',
            ];
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

    public function setTransformationManager(\Solr\Transformation\Manager $transformationManager)
    {
        $this->transformationManager = $transformationManager;
    }

    public function getTransformationManager(): \Solr\Transformation\Manager
    {
        return $this->transformationManager;
    }
}