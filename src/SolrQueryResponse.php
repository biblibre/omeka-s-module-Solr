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
        return json_decode($this->httpResponse->getBody(), true);
    }
}
