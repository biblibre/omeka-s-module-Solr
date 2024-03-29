<?php

/*
 * Copyright BibLibre, 2017
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Solr\Form\Admin;

use Laminas\Form\Element\Select;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;
use Solr\Form\Element\Transformations;
use Solr\ValueExtractor\Manager as ValueExtractorManager;

class SolrMappingForm extends Form
{
    protected $valueExtractorManager;
    protected $transformationManager;

    public function init()
    {
        $this->add([
            'name' => 'o:source',
            'type' => Select::class,
            'options' => [
                'label' => 'Source', // @translate
                'value_options' => $this->getSourceOptions(),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:field_name',
            'type' => 'Text',
            'options' => [
                'label' => 'Solr field', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $settingsFieldset = new Fieldset('o:settings');

        $transformationNames = $this->transformationManager->getRegisteredNames($sortAlpha = true);
        $transformationValueOptions = [];
        foreach ($transformationNames as $name) {
            $transformation = $this->transformationManager->get($name);
            $transformationValueOptions[] = [
                'value' => $name,
                'label' => $transformation->getLabel(),
            ];
        }

        $settingsFieldset->add([
            'name' => 'transformations',
            'type' => Transformations::class,
            'options' => [
                'label' => 'Transformations', // @translate
                'value_options' => $transformationValueOptions,
                'empty_option' => '',
                'solr_mapping_id' => $this->getOption('solr_mapping_id'),
            ],
        ]);

        $this->add($settingsFieldset);
    }

    public function setValueExtractorManager(ValueExtractorManager $valueExtractorManager)
    {
        $this->valueExtractorManager = $valueExtractorManager;
    }

    public function getValueExtractorManager()
    {
        return $this->valueExtractorManager;
    }

    public function setTransformationManager(\Solr\Transformation\Manager $transformationManager)
    {
        $this->transformationManager = $transformationManager;
    }

    public function getTransformationManager(): \Solr\Transformation\Manager
    {
        return $this->transformationManager;
    }

    protected function getSourceOptions()
    {
        $valueExtractorManager = $this->getValueExtractorManager();

        $resourceName = $this->getOption('resource_name');
        $valueExtractor = $valueExtractorManager->get($resourceName);
        if (!isset($valueExtractor)) {
            return null;
        }

        return $this->getFieldsOptions($valueExtractor->getAvailableFields());
    }

    protected function getFieldsOptions($fields, $valuePrefix = '', $labelPrefix = '')
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
}
