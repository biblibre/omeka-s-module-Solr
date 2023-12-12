<?php

namespace Solr;

use Laminas\Http\Response;

class SolrQueryResponse
{
    protected Response $httpResponse;

    public function __construct(Response $httpResponse)
    {
        $this->httpResponse = $httpResponse;
    }

    public function getResponse(): array
    {
        $data = json_decode($this->httpResponse->getBody(), true);

        if (isset($data['facet_counts']['facet_fields'])) {
            $facet_fields = $data['facet_counts']['facet_fields'];
            foreach ($facet_fields as $name => $values) {
                $new_facet_fields[$name] = [];
                while (!empty($values)) {
                    $facet_value = array_shift($values);
                    $facet_count = array_shift($values);
                    if (isset($facet_count)) {
                        $new_facet_fields[$name][$facet_value] = $facet_count;
                    }
                }
            }
            $data['facet_counts']['facet_fields'] = $new_facet_fields;
        }

        return $data;
    }
}
