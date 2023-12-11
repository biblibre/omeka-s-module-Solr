<?php
namespace Solr\Value;

use DateTimeImmutable;
use DateTimeZone;
use Stringable;

class DateTimeValue extends DateTimeImmutable implements Stringable
{
    public function __toString(): string
    {
        $utc = new DateTimeZone('UTC');

        return $this->setTimezone($utc)->format("Y-m-d\\TH:i:s\\Z");
    }
}
