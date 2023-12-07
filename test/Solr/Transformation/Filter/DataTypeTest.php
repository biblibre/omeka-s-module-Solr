<?php

namespace Solr\Test\Transformation\Filter;

use Omeka\Entity\Value;
use Omeka\Api\Representation\ValueRepresentation;
use Solr\Test\TestCase;
use Solr\Transformation\Filter\DataType;

class DataTypeTest extends TestCase
{
    public function testTransformationEmptyConfiguration()
    {
        $serviceLocator = $this->getServiceLocator();
        $transformationManager = $serviceLocator->get('Solr\TransformationManager');
        $transformation = $transformationManager->get(DataType::class);

        $value = new Value();
        $value->setType('literal');
        $valueRep = new ValueRepresentation($value, $serviceLocator);
        $values = [$valueRep];
        $transformedValues = $transformation->transform($values, []);

        $this->assertSameSize($values, $transformedValues);
        $this->assertContains($valueRep, $transformedValues);
    }

    public function testTransformationKeepOnlyLiteral()
    {
        $serviceLocator = $this->getServiceLocator();
        $transformationManager = $serviceLocator->get('Solr\TransformationManager');
        $transformation = $transformationManager->get(DataType::class);

        $values = [];

        $value = new Value();
        $value->setType('uri');
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $value = new Value();
        $value->setType('literal');
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $values[] = 'a string';

        $transformedValues = $transformation->transform($values, ['data_types' => ['literal']]);

        $this->assertCount(2, $transformedValues);
        $this->assertEquals('literal', $transformedValues[0]->type());
        $this->assertEquals('a string', $transformedValues[1]);
    }
}
