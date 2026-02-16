<?php

/*
 * Copyright BibLibre, 2016-2017
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

namespace Solr\ValueExtractor;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Representation\AbstractResourceRepresentation;
use Solr\Value\DateTimeValue;
use Stringable;

class MediaValueExtractor extends AbstractValueExtractor
{
    protected $api;

    public function setApiManager(ApiManager $api)
    {
        $this->api = $api;
    }

    public function getLabel()
    {
        return 'Media';
    }

    public function getAvailableFields()
    {
        $fields = [
            'created' => [
                'label' => 'Created',
            ],
            'modified' => [
                'label' => 'Modified',
            ],
            'is_public' => [
                'label' => 'Is public',
            ],
            'resource_class' => [
                'label' => 'Resource class',
            ],
            'resource_template' => [
                'label' => 'Resource template',
            ],
            'content' => [
                'label' => 'HTML Content',
            ],
        ];

        $properties = $this->api->search('properties')->getContent();
        foreach ($properties as $property) {
            $term = $property->term();
            $fields[$term]['label'] = $term;
        }

        $fields['item'] = [
            'label' => 'Item',
            'children' => [
                'id' => [
                    'label' => 'Internal identifier',
                ],
            ],
        ];

        foreach ($properties as $property) {
            $term = $property->term();
            $fields['item']['children'][$term]['label'] = $term;
        }

        $params = ['fields' => $fields];
        $params = $this->triggerEvent('solr.value_extractor.fields', null, $params);
        $fields = $params['fields'];

        return $fields;
    }

    public function extractValue(AbstractResourceRepresentation $media, $field, $settings): Stringable | array | string | int | float | bool
    {
        $params = ['field' => $field, 'settings' => $settings, 'value' => null];
        $params = $this->triggerEvent('solr.value_extractor.extract_value', $media, $params);
        if (isset($params['value'])) {
            return $params['value'];
        }

        if ($field === 'created') {
            return DateTimeValue::createFromInterface($media->created());
        }

        if ($field === 'modified') {
            $modified = $media->modified();
            return $modified ? DateTimeValue::createFromInterface($modified) : [];
        }

        if ($field === 'is_public') {
            return $media->isPublic();
        }

        if ($field === 'resource_class') {
            $resourceClass = $media->resourceClass();
            return $resourceClass ? $resourceClass->term() : [];
        }

        if ($field === 'resource_template') {
            $resourceTemplate = $media->resourceTemplate();
            return $resourceTemplate ? $resourceTemplate->label() : [];
        }

        if (preg_match('/^item\/(.*)/', $field, $matches)) {
            $mediaField = $matches[1];
            return $this->extractItemValue($media, $mediaField, $settings);
        }

        if (preg_match('/^item_set\/(.*)/', $field, $matches)) {
            $itemSetField = $matches[1];
            return $this->extractItemSetValue($media, $itemSetField, $settings);
        }

        return $media->value($field, ['all' => true, 'default' => []]);
    }

    protected function extractItemValue(MediaRepresentation $media, $field, array $settings)
    {
        $extractedValue = [];

        $item = $media->item();

        if ($field == 'id') {
            $extractedValue[] = $item->id();
        } else {
            $itemExtractedValue = $item->value($field, ['all' => true, 'default' => []]);
            $extractedValue = array_merge($extractedValue, $itemExtractedValue);
        }

        return $extractedValue;
    }

    protected function extractItemSetValue(MediaRepresentation $media, $field, array $settings)
    {
        $extractedValue = [];

        $item = $media->item();

        foreach ($item->itemSets() as $itemSet) {
            if ($field == 'id') {
                $extractedValue[] = $itemSet->id();
            } else {
                $itemSetExtractedValue = $itemSet->value($field, ['all' => true, 'default' => []]);
                $extractedValue = array_merge($extractedValue, $itemSetExtractedValue);
            }
        }

        return $extractedValue;
    }
}
