<?php

namespace Solr\Test\Transformation\Filter;

use Omeka\Entity\Item;
use Omeka\Entity\ResourceClass;
use Omeka\Entity\Value;
use Omeka\Entity\Vocabulary;
use Omeka\Api\Representation\ValueRepresentation;
use Solr\Test\TestCase;
use Solr\Transformation\Filter;

class ResourceClassTest extends TestCase
{
    public function testTransformationEmptyConfiguration()
    {
        $serviceLocator = $this->getServiceLocator();
        $transformationManager = $serviceLocator->get('Solr\TransformationManager');
        $transformation = $transformationManager->get(Filter\ResourceClass::class);

        $values = [];

        $vocabulary = new Vocabulary();
        $vocabulary->setPrefix('vocab');
        $resourceClass = new ResourceClass();
        $resourceClass->setVocabulary($vocabulary);
        $resourceClass->setLocalName('Class');
        $item = new Item();
        $item->setResourceClass($resourceClass);
        $value = new Value();
        $value->setType('resource:item');
        $value->setValueResource($item);

        $values[] = new ValueRepresentation($value, $serviceLocator);
        $transformedValues = $transformation->transform($values, []);

        $this->assertSameSize($values, $transformedValues);
        $this->assertSame($values[0], $transformedValues[0]);
    }

    public function testTransformationKeepOnlyVocabClass()
    {
        $serviceLocator = $this->getServiceLocator();
        $transformationManager = $serviceLocator->get('Solr\TransformationManager');
        $transformation = $transformationManager->get(Filter\ResourceClass::class);

        $values = [];

        $vocabulary = new Vocabulary();
        $vocabulary->setPrefix('vocab');
        $resourceClass = new ResourceClass();
        $resourceClass->setVocabulary($vocabulary);
        $resourceClass->setLocalName('Class');
        $item = new Item();
        $item->setResourceClass($resourceClass);
        $value = new Value();
        $value->setValueResource($item);
        $value->setType('resource:item');
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $item = new Item();
        $value = new Value();
        $value->setValueResource($item);
        $value->setType('resource:item');
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $transformedValues = $transformation->transform($values, ['resource_classes' => ['vocab:Class']]);

        $this->assertCount(1, $transformedValues);
        $this->assertEquals('vocab:Class', $transformedValues[0]->valueResource()->resourceClass()->term());
    }
}
