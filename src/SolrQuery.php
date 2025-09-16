<?php

namespace Solr;

use JsonSerializable;

class SolrQuery implements JsonSerializable
{
    protected array $params = [];

    const PARAMS_MAP = [
        'q' => 'query',
        'fq' => 'filter',
        'start' => 'offset',
        'rows' => 'limit',
        'fl' => 'fields',
        'sort' => 'sort',
    ];

    public function jsonSerialize(): mixed
    {
        $params = $this->params;
        $data = [];

        foreach (self::PARAMS_MAP as $param_name => $json_key) {
            if (isset($params[$param_name])) {
                $data[$json_key] = $params[$param_name];
                unset($params[$param_name]);
            }
        }

        if (isset($params['facet.field'])) {
            $data['facet'] = [];
            $limit = $params['facet.limit'] ?? null;
            foreach ($params['facet.field'] as $field) {
                if (isset($params["facet.sort.$field"])) {
                    $sort = $params["facet.sort.$field"] != '' ? $params["facet.sort.$field"] : 'count';
                    $data['facet'][$field] = ['type' => 'terms', 'field' => $field, 'sort' => $sort];
                } else {
                    unset($params["facet.sort.$field"]);
                }
                if (isset($limit)) {
                    $data['facet'][$field]['limit'] = (int) $limit;
                }
            }
            unset($params['facet.field']);
            unset($params['facet.limit']);
        }

        $data['params'] = $params;

        return $data;
    }

    public function addParam(string $name, string $value)
    {
        $this->params[$name] ??= [];
        if (!is_array($this->params[$name])) {
            $this->params[$name] = [$this->params[$name]];
        }
        $this->params[$name][] = $value;
    }

    public function setParam(string $name, string $value)
    {
        $this->params[$name] = $value;
    }

    public function setBoolParam(string $name, bool $value)
    {
        $this->setParam($name, $value ? 'true' : 'false');
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
        $sort = $this->params['sort'] ?? '';
        if ($sort) {
            $sort .= ",$field $order";
        } else {
            $sort = "$field $order";
        }
        $this->setParam('sort', $sort);
    }
}
