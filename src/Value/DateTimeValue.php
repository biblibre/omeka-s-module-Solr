<?php
namespace Solr\Value;

use DateTimeImmutable;
use DateTimeZone;

class DateTimeValue extends DateTimeImmutable implements Stringable
{
    public function __toString(): string
    {
        $utc = new DateTimeZone(DateTimeZone::UTC);

        return $this->setTimezone($utc)->format("Y-m-d\\TH:i:s\\Z");
    }
}
