<?php

namespace Solr\Test\Transformation;

use Omeka\Entity\Item;
use Omeka\Entity\Value;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\ValueRepresentation;
use Solr\Test\TestCase;
use Solr\Transformation\ConvertResourceToString;

class ConvertResourceToStringTest extends TestCase
{
    public function testTransformationTitle()
    {
        $serviceLocator = $this->getServiceLocator();
        $transformationManager = $serviceLocator->get('Solr\TransformationManager');
        $transformation = $transformationManager->get(ConvertResourceToString::class);

        $values = [];

        $item = new Item();
        $item->setTitle('Item title');
        $value = new Value();
        $value->setType('resource:item');
        $value->setValueResource($item);
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $values[] = 'a string value';

        $transformedValues = $transformation->transform($values, ['resource_field' => 'title']);

        $this->assertCount(2, $transformedValues);
        $this->assertEquals('Item title', $transformedValues[0]);
        $this->assertEquals('a string value', $transformedValues[1]);
    }

    public function testTransformationId()
    {
        $serviceLocator = $this->getServiceLocator();
        $transformationManager = $serviceLocator->get('Solr\TransformationManager');
        $entityManager = $serviceLocator->get('Omeka\EntityManager');
        $transformation = $transformationManager->get(ConvertResourceToString::class);

        $values = [];

        $item = new Item();
        $item->setTitle('Item title');
        $item->setCreated(new \DateTime());
        $entityManager->persist($item);
        $entityManager->flush();
        $value = new Value();
        $value->setType('resource:item');
        $value->setValueResource($item);
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $values[] = new ItemRepresentation($item, $serviceLocator->get('Omeka\ApiAdapterManager')->get('items'));

        $values[] = 'a string value';

        $transformedValues = $transformation->transform($values, ['resource_field' => 'id']);

        $this->assertCount(3, $transformedValues);
        $this->assertEquals($item->getId(), $transformedValues[0]);
        $this->assertEquals($item->getId(), $transformedValues[1]);
        $this->assertEquals('a string value', $transformedValues[2]);
    }
}
