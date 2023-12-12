<?php

namespace Solr;

use JsonSerializable;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Request;
use Solr\Exception\BadCredentialsException;

class SolrClient
{
    protected HttpClient $httpClient;
    protected string $uri;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function setAuth(string $user, string $password)
    {
        $this->httpClient->setAuth($user, $password);
    }

    public function ping(): array
    {
        $request = $this->createGetRequest('admin/ping');
        $response = $this->send($request);

        return json_decode($response->getBody(), true);
    }

    public function schema(): Schema
    {
        $request = $this->createGetRequest('schema');
        $response = $this->send($request);

        $data = json_decode($response->getBody(), true);
        $schema = new Schema($data['schema']);

        return $schema;
    }

    public function deleteByQuery(string $query): void
    {
        $request = $this->createPostRequest('update', ['delete' => ['query' => $query]]);
        $this->send($request);
    }

    public function deleteById(string $id): void
    {
        $request = $this->createPostRequest('update', ['delete' => $id]);
        $this->send($request);
    }

    public function commit(): void
    {
        $request = $this->createPostRequest('update', '{"commit": {}}');
        $this->send($request);
    }

    public function addDocument(SolrInputDocument $document): void
    {
        $request = $this->createPostRequest('update', [$document->toArray()]);
        $this->send($request);
    }

    public function query(SolrQuery $query)
    {
        $q = clone $query;
        $q->setParam('wt', 'json');
        $request = $this->createPostRequest('select', $q);
        $response = $this->send($request);

        return new SolrQueryResponse($response);
    }

    protected function send(Request $request)
    {
        $response = $this->httpClient->send($request);
        if ($response->isClientError()) {
            $data = json_decode($response->getBody(), true);
            if (isset($data['error']['msg'])) {
                throw new \Exception($data['error']['msg']);
            }

            if ($response->getStatusCode() === 401) {
                throw new BadCredentialsException(sprintf('%s returned: %s', $request->getUri(), $response->toString()));
            }

            throw new \Exception(sprintf('%s returned: %s', $request->getUri(), $response->toString()));
        }

        if (!$response->isOk()) {
            throw new \Exception(sprintf('%s returned: %s', $request->getUri(), $response->renderStatusLine()));
        }

        return $response;
    }

    protected function createGetRequest($requestHandlerName)
    {
        $request = new Request();
        $request->setUri(sprintf('%s/%s', $this->uri, $requestHandlerName));

        return $request;
    }

    protected function createPostRequest($requestHandlerName, string | array | JsonSerializable $data = [])
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setUri(sprintf('%s/%s', $this->uri, $requestHandlerName));
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        if (is_string($data)) {
            $request->setContent($data);
        } else {
            $request->setContent(json_encode($data));
        }

        return $request;
    }
}
