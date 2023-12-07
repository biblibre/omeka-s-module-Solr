<?php

namespace Solr\Test\Transformation;

use Omeka\Entity\Value;
use Omeka\Api\Representation\ValueRepresentation;
use Solr\Test\TestCase;
use Solr\Transformation\ConvertToSolrDateRange;

class ConvertToSolrDateRangeTest extends TestCase
{
    public function testTransformation()
    {
        $serviceLocator = $this->getServiceLocator();
        $transformationManager = $serviceLocator->get('Solr\TransformationManager');
        $transformation = $transformationManager->get(ConvertToSolrDateRange::class);

        $values = [];

        $value = new Value();
        $value->setType('literal');
        $value->setValue('1900-1905');
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $value = new Value();
        $value->setType('literal');
        $value->setValue('2010');
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $value = new Value();
        $value->setType('literal');
        $value->setValue(' 1500   -   1600  ');
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $value = new Value();
        $value->setType('literal');
        $value->setValue('not a date');
        $values[] = new ValueRepresentation($value, $serviceLocator);

        $transformedValues = $transformation->transform($values, ['exclude_unmatching' => '1']);

        $this->assertCount(3, $transformedValues);
        $this->assertEquals('[1900 TO 1905]', $transformedValues[0]);
        $this->assertEquals('[2010 TO 2010]', $transformedValues[1]);
        $this->assertEquals('[1500 TO 1600]', $transformedValues[2]);
    }
}
