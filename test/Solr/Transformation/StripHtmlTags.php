<?php

namespace Solr\Test\Transformation;

use Omeka\Entity\Value;
use Omeka\Api\Representation\ValueRepresentation;
use Solr\Test\TestCase;
use Solr\Transformation\StripHtmlTags;

class StripHtmlTagsTest extends TestCase
{
    public function testTransformation()
    {
        $serviceLocator = $this->getServiceLocator();
        $transformationManager = $serviceLocator->get('Solr\TransformationManager');
        $transformation = $transformationManager->get(StripHtmlTags::class);

        $values = [];

        $value = new Value();
        $value->setType('literal');
        $value->setValue('This is <strong>HTML!</strong>');
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $transformedValues = $transformation->transform($values, ['resource_field' => 'title']);

        $this->assertCount(1, $transformedValues);
        $this->assertEquals('This is HTML!', $transformedValues[0]);
    }
}
