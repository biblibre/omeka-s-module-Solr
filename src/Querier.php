<?php

/*
 * Copyright BibLibre, 2016-2020
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Solr;

use Search\Querier\AbstractQuerier;
use Search\Querier\Exception\QuerierException;
use Search\Query;
use Search\Response;

class Querier extends AbstractQuerier
{
    const SOLR_SPECIAL_CHARS = ['+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '/'];
    const PCRE_SPECIAL_CHARS = ['.', '\\', '+', '*', '?', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|', ':', '-', '#'];

    protected $client;
    protected $solrNode;

    protected $searchFields;

    public function query(Query $query)
    {
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');
        $api = $serviceLocator->get('Omeka\ApiManager');
        $logger = $serviceLocator->get('Omeka\Logger');
        $eventManager = $serviceLocator->get('EventManager');

        $client = $this->getClient();

        $solrNode = $this->getSolrNode();
        $solrNodeSettings = $solrNode->settings();
        $resource_name_field = $solrNodeSettings['resource_name_field'];
        $sites_field = $solrNodeSettings['sites_field'];
        $is_public_field = $solrNodeSettings['is_public_field'];
        $highlightSettings = $solrNodeSettings['highlight'] ?? [];
        $highlighting = $highlightSettings['highlighting'] ?? false;
        $highlightQueryParts = [];

        $solrQuery = new SolrQuery;
        $solrQuery->setParam('defType', 'edismax');

        if (!empty($solrNodeSettings['qf'])) {
            $solrQuery->setParam('qf', $solrNodeSettings['qf']);
        }

        if (!empty($solrNodeSettings['mm'])) {
            $solrQuery->setParam('mm', $solrNodeSettings['mm']);
        }

        $uf = [];
        $searchFields = $this->getSearchFields();
        foreach ($searchFields as $name => $searchField) {
            $textFields = $searchField->textFields();
            if (!empty($textFields)) {
                $paramName = sprintf('f.%s.qf', $name);
                $solrQuery->setParam($paramName, $textFields);
                $uf[] = $name;
            }

            $facetField = $searchField->facetField();
            if (!empty($facetField)) {
                $searchFieldMapByFacetField[$facetField] = $searchField;
            }
        }

        if (!empty($uf)) {
            $solrQuery->setParam('uf', implode(' ', $uf));
        } else {
            $solrQuery->setParam('uf', '-*');
        }

        $q = $query->getQuery();
        $q = $this->getQueryStringFromSearchQuery($q);

        if (empty($q)) {
            $q = '*:*';
        } else {
            $highlightQueryParts[] = $q;
        }

        $solrQuery->setQuery($q);
        $solrQuery->addField('id');

        $solrQuery->setGroup(true);
        $solrQuery->addGroupField($resource_name_field);

        $resources = $query->getResources();
        $fq = sprintf('%s:(%s)', $resource_name_field, implode(' OR ', $resources));
        $solrQuery->addFilterQuery($fq);

        $site = $query->getSite();
        if (isset($site)) {
            $fq = sprintf('%s:%d', $sites_field, $site->id());
            $solrQuery->addFilterQuery($fq);
        }

        $isPublic = $query->getIsPublic();
        if (isset($isPublic)) {
            $fq = sprintf('%s:%s', $is_public_field, $isPublic ? 'true' : 'false');
            $solrQuery->addFilterQuery($fq);
        }

        $facetFields = $query->getFacetFields();
        if (!empty($facetFields)) {
            foreach ($facetFields as $facetField) {
                $searchField = $this->getSearchField($facetField);
                if (!$searchField) {
                    throw new QuerierException(sprintf('Field %s does not exist', $facetField));
                }
                $solrFacetField = $searchField->facetField();
                if (!$solrFacetField) {
                    throw new QuerierException(sprintf('Field %s is not facetable', $facetField));
                }

                $solrQuery->addFacetField($solrFacetField);
            }
        }

        $facetLimit = $query->getFacetLimit();
        if ($facetLimit) {
            $solrQuery->setFacetLimit($facetLimit);
        }

        $facetFilters = $query->getFacetFilters();
        if (!empty($facetFilters)) {
            foreach ($facetFilters as $name => $values) {
                $values = array_filter($values);
                foreach ($values as $value) {
                    if (is_array($value)) {
                        $value = array_filter($value);
                        if (empty($value)) {
                            continue;
                        }

                        $value = '(' . implode(' OR ', array_map([$this, 'enclose'], $value)) . ')';
                    } else {
                        $value = $this->enclose($value);
                    }

                    $searchField = $this->getSearchField($name);
                    if (!$searchField) {
                        throw new QuerierException(sprintf('Field %s does not exist', $name));
                    }
                    $solrFacetField = $searchField->facetField();
                    if (!$solrFacetField) {
                        throw new QuerierException(sprintf('Field %s is not facetable', $name));
                    }

                    $solrQuery->addFilterQuery(sprintf('%s:%s', $solrFacetField, $value));
                }
            }
        }

        $queryFilters = $query->getQueryFilters();
        foreach ($queryFilters as $queryFilter) {
            $fq = $this->getQueryStringFromSearchQuery($queryFilter);
            if (!empty($fq)) {
                $solrQuery->addFilterQuery($fq);
                $highlightQueryParts[] = $fq;
            }
        }

        $dateRangeFilters = $query->getDateRangeFilters();
        foreach ($dateRangeFilters as $name => $filterValues) {
            foreach ($filterValues as $filterValue) {
                $start = $filterValue['start'] ? $filterValue['start'] : '*';
                $end = $filterValue['end'] ? $filterValue['end'] : '*';
                $solrQuery->addFilterQuery("$name:[$start TO $end]");
            }
        }

        if ($highlighting) {
            $solrQuery->setHighlight(true);
            $solrQuery->setHighlightSimplePre('<mark>');
            $solrQuery->setHighlightSimplePost('</mark>');

            $highlight_fragsize = $highlightSettings['fragsize'] ?? '';
            if (is_numeric($highlight_fragsize)) {
                $solrQuery->setHighlightFragsize($highlight_fragsize);
            }

            $highlight_snippets = $highlightSettings['snippets'] ?? '';
            if (is_numeric($highlight_snippets)) {
                $solrQuery->setHighlightSnippets($highlight_snippets);
            }

            $highlight_maxAnalyzedChars = $highlightSettings['maxAnalyzedChars'] ?? '';
            if (is_numeric($highlight_maxAnalyzedChars)) {
                $solrQuery->setParam('hl.maxAnalyzedChars', $highlight_maxAnalyzedChars);
            }

            if (!empty($highlightQueryParts)) {
                $highlight_query = implode(' AND ', array_map(fn ($part) => "($part)", $highlightQueryParts));
                $solrQuery->setParam('hl.q', $highlight_query);
            }

            $highlight_fields = $highlightSettings['fields'] ?? '';
            if (!empty($highlight_fields)) {
                $highlight_fields = str_replace(' ', ',', $highlight_fields);
                $solrQuery->setParam('hl.fl', $highlight_fields);
            }
        }

        $sort = $query->getSort();
        if (isset($sort)) {
            [$sortField, $sortOrder] = explode(' ', $sort);

            if ($sortField !== 'score') {
                $searchField = $this->getSearchField($sortField);
                if (!$searchField) {
                    throw new QuerierException(sprintf('Field %s does not exist', $sortField));
                }
                $solrSortField = $searchField->sortField();
                if (!$solrSortField) {
                    throw new QuerierException(sprintf('Field %s is not sortable', $sortField));
                }
                $sortField = $solrSortField;
            }

            $solrQuery->addSortField($sortField, $sortOrder);
        }

        if ($limit = $query->getLimit()) {
            $solrQuery->setGroupLimit($limit);
        }

        if ($offset = $query->getOffset()) {
            $solrQuery->setGroupOffset($offset);
        }

        $eventManager->setIdentifiers(['Solr\Querier']);
        $eventManager->trigger('solr.query', $solrQuery, ['query' => $query, 'solrNode' => $solrNode]);

        try {
            $logger->debug(sprintf('Solr query params: %s', json_encode($solrQuery)));
            $solrQueryResponse = $client->query($solrQuery);
        } catch (\Exception $e) {
            throw new QuerierException($e->getMessage(), $e->getCode(), $e);
        }
        $solrResponse = $solrQueryResponse->getResponse();

        $response = new Response;
        $response->setTotalResults($solrResponse['grouped'][$resource_name_field]['matches']);
        foreach ($solrResponse['grouped'][$resource_name_field]['groups'] as $group) {
            $response->setResourceTotalResults($group['groupValue'], $group['doclist']['numFound']);
            foreach ($group['doclist']['docs'] as $doc) {
                [, $resourceId] = explode(':', $doc['id']);
                $response->addResult($group['groupValue'], ['id' => $resourceId]);
            }
        }

        if (!empty($solrResponse['facets'])) {
            foreach ($solrResponse['facets'] as $name => $facetData) {
                if ($name === 'count') {
                    continue;
                }

                foreach ($facetData['buckets'] as $bucket) {
                    if ($bucket['count'] > 0) {
                        $searchField = $searchFieldMapByFacetField[$name];
                        $response->addFacetCount($searchField->name(), $bucket['val'], $bucket['count']);
                    }
                }
            }
        }

        if (isset($solrResponse['highlighting'])) {
            foreach ($solrResponse['highlighting'] as $key => $highlightsByField) {
                [$resource, $resourceId] = explode(':', $key);
                foreach ($highlightsByField as $solrFieldName => $highlights) {
                    foreach ($highlights as $highlight) {
                        $response->addHighlight($resource, $resourceId, $highlight);
                    }
                }
            }
        }

        return $response;
    }

    protected function enclose($value)
    {
        return '"' . addcslashes($value, '"') . '"';
    }

    protected function getClient()
    {
        if (!isset($this->client)) {
            $this->client = $this->getSolrNode()->client();
        }

        return $this->client;
    }

    protected function getSolrNode()
    {
        if (!isset($this->solrNode)) {
            $api = $this->getServiceLocator()->get('Omeka\ApiManager');

            $solrNodeId = $this->getAdapterSetting('solr_node_id');
            if ($solrNodeId) {
                $response = $api->read('solr_nodes', $solrNodeId);
                $this->solrNode = $response->getContent();
            }
        }

        return $this->solrNode;
    }

    protected function getQueryStringFromSearchQuery($q)
    {
        if (is_string($q)) {
            return $this->escape($q);
        }

        if (is_array($q) && isset($q['match']) && !empty($q['queries'])) {
            $joiner = $q['match'] === 'any' ? ' OR ' : ' AND ';
            $parts = array_filter(array_map(function ($query) {
                return $this->getQueryStringFromSearchQuery($query);
            }, $q['queries']));

            if (!empty($parts)) {
                $qs = sprintf('(%s)', implode($joiner, $parts));
                return $qs;
            }

            return '';
        }

        if (is_array($q) && isset($q['field']) && !empty($q['term'])) {
            $searchField = $this->getSearchField($q['field']);
            if (!isset($searchField)) {
                throw new QuerierException(sprintf('Field %s does not exist', $q['field']));
            }

            $term = trim($q['term']);

            switch ($q['operator']) {
                case Adapter::OPERATOR_CONTAINS_ANY_WORD:
                    $solrFields = $searchField->textFields();
                    if (empty($solrFields)) {
                        throw new QuerierException(sprintf('Field %s cannot be used with "contains any word" operator', $searchField->name()));
                    }

                    $term = $this->escape($term);
                    break;

                case Adapter::OPERATOR_CONTAINS_ALL_WORDS:
                    $solrFields = $searchField->textFields();
                    if (empty($solrFields)) {
                        throw new QuerierException(sprintf('Field %s cannot be used with "contains all words" operator', $searchField->name()));
                    }

                    $term = $this->escape($term);
                    if (isset($q['proximity']) && is_numeric($q['proximity'])) {
                        $term = sprintf('+"%s" ~%s', $term, $q['proximity']);
                    } else {
                        $words = explode(' ', $term);
                        $term = implode(' ', array_map(function ($word) {
                            return "+$word";
                        }, $words));
                    }
                    break;

                case Adapter::OPERATOR_CONTAINS_EXPR:
                    $solrFields = $searchField->textFields();
                    if (empty($solrFields)) {
                        throw new QuerierException(sprintf('Field %s cannot be used with "contains expression" operator', $searchField->name()));
                    }

                    $term = sprintf('"%s"', $this->escape($term));
                    break;

                case Adapter::OPERATOR_MATCHES_PATTERN:
                    $solrFields = $searchField->stringFields();
                    if (empty($solrFields)) {
                        throw new QuerierException(sprintf('Field %s cannot be used with "matches pattern" operator', $searchField->name()));
                    }

                    $charsToEscape = array_diff(self::SOLR_SPECIAL_CHARS, ['*', '?']);
                    $charsToEscape[] = ' ';
                    $term = $this->escapeChars($charsToEscape, $term);
                    break;

                default:
                    throw new QuerierException(sprintf("Unknown operator '%s'", $q['operator']));
            }

            $qs = sprintf('(%s)', implode(' OR ', array_map(function ($solrField) use ($term) {
                return sprintf('%s:(%s)', $solrField, $term);
            }, array_filter(explode(' ', $solrFields)))));

            return $qs;
        }
    }

    protected function getSearchFields()
    {
        if (!$this->searchFields) {
            $api = $this->getServiceLocator()->get('Omeka\ApiManager');
            $solrNode = $this->getSolrNode();
            $searchFields = $api->search('solr_search_fields', ['solr_node_id' => $solrNode->id()])->getContent();
            $this->searchFields = [];
            foreach ($searchFields as $searchField) {
                $name = trim($searchField->name());
                $this->searchFields[$name] = $searchField;
            }
        }

        return $this->searchFields;
    }

    protected function getSearchField($name)
    {
        $searchFields = $this->getSearchFields();

        return $searchFields[$name] ?? null;
    }

    protected function escape($string)
    {
        return $this->escapeChars(self::SOLR_SPECIAL_CHARS, $string);
    }

    protected function escapeChars($charsToEscape, $string)
    {
        $pattern = '/([' . implode('', array_map(function ($c) {
            return preg_quote($c, '/');
        }, $charsToEscape)) . '])/';
        return preg_replace($pattern, '\\\\$1', $string);
    }
}
