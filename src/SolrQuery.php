<?php

namespace Solr;

use ArrayObject;
use Laminas\Stdlib\ParametersInterface;

class SolrQuery extends ArrayObject implements ParametersInterface
{
    public function __construct(array $values = null)
    {
        if (null === $values) {
            $values = [];
        }
        parent::__construct($values, ArrayObject::ARRAY_AS_PROPS);
    }

    public function fromArray(array $values)
    {
        $this->exchangeArray($values);
    }

    public function fromString($string)
    {
        $parts = explode('&', $string);
        foreach ($parts as $part) {
            [$name, $value] = explode('=', $part, 2);
            $this->addParam($name, rawurldecode($value));
        }
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }

    public function toString()
    {
        $parts = [];
        foreach ($this as $name => $values) {
            if (is_array($values)) {
                foreach ($values as $value) {
                    $parts[] = sprintf('%s=%s', rawurlencode($name), rawurlencode($value));
                }
            } else {
                $parts[] = sprintf('%s=%s', rawurlencode($name), rawurlencode($values));
            }
        }

        return implode('&', $parts);
    }

    public function get($name, $default = null)
    {
        return $this[$name] ?? $default;
    }

    public function set($name, $value)
    {
        $this[$name] = $value;
    }

    public function addParam(string $name, string $value)
    {
        $this[$name] ??= [];
        if (!is_array($this[$name])) {
            $this[$name] = [$this[$name]];
        }
        $this[$name][] = $value;
    }

    public function setParam(string $name, string $value)
    {
        $this[$name] = $value;
    }

    public function setBoolParam(string $name, bool $value)
    {
        $this[$name] = $value ? 'true' : 'false';
    }

    public function setQuery(string $query)
    {
        $this->setParam('q', $query);
    }

    public function addField(string $field)
    {
        $this->addParam('fl', $field);
    }

    public function setGroup(bool $group)
    {
        $this->setBoolParam('group', $group);
    }

    public function addGroupField(string $field)
    {
        $this->addParam('group.field', $field);
    }

    public function setGroupLimit(string $limit)
    {
        $this->setParam('group.limit', $limit);
    }

    public function setGroupOffset(string $offset)
    {
        $this->setParam('group.offset', $offset);
    }

    public function addFilterQuery(string $query)
    {
        $this->addParam('fq', $query);
    }

    public function setFacet(bool $facet)
    {
        $this->setBoolParam('facet', $facet);
    }

    public function addFacetField(string $field)
    {
        $this->addParam('facet.field', $field);
    }

    public function setFacetLimit(string $facetLimit)
    {
        $this->setParam('facet.limit', $facetLimit);
    }

    public function setHighlight(bool $highlight)
    {
        $this->setBoolParam('hl', $highlight);
    }

    public function setHighlightSimplePre(string $value)
    {
        $this->setParam('hl.tag.pre', $value);
    }

    public function setHighlightSimplePost(string $value)
    {
        $this->setParam('hl.tag.post', $value);
    }

    public function setHighlightFragsize(string $fragsize)
    {
        $this->setParam('hl.fragsize', $fragsize);
    }

    public function setHighlightSnippets(string $snippets)
    {
        $this->setParam('hl.snippets', $snippets);
    }

    public function addSortField(string $field, string $order = 'desc')
    {
        $order = $order === 'asc' ? 'asc' : 'desc';
        $sort = $this['sort'] ?? '';
        if ($sort) {
            $sort .= ",$field $order";
        } else {
            $sort = "$field $order";
        }
        $this->setParam('sort', $sort);
    }
}
