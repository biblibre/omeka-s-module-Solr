<?php

namespace Solr\Test\Value;

use Solr\Test\TestCase;
use Solr\Value\DateTimeValue;

class DateTimeValueTest extends TestCase
{
    public function testToString()
    {
        $dateTimeValue = new DateTimeValue('@1');
        $this->assertEquals('1970-01-01T00:00:01Z', (string) $dateTimeValue);
    }
}
