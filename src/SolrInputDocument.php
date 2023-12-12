<?php

namespace Solr;

class SolrInputDocument
{
    protected array $fields = [];

    public function addField(string $name, string $value): void
    {
        if (array_key_exists($name, $this->fields)) {
            if (is_array($this->fields[$name])) {
                $this->fields[$name][] = $value;
            } else {
                $values = [$this->fields[$name], $value];
                $this->fields[$name] = $values;
            }
        } else {
            $this->fields[$name] = $value;
        }
    }

    public function toArray(): array
    {
        return $this->fields;
    }
}
