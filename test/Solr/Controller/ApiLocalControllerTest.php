<?php

namespace Solr\Test\Controller;

use Omeka\Mvc\Exception\PermissionDeniedException;
use Solr\Test\Controller\SolrControllerTestCase;

class ApiLocalControllerTest extends SolrControllerTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (version_compare(\Omeka\Module::VERSION, '4.1.0') < 0) {
            $this->markTestSkipped('Omeka S version < 4.1.0');
        }
    }

    public function testApiSolrNodesIsDeniedToAnonymousUsers()
    {
        $this->logout();
        $this->expectException(PermissionDeniedException::class);
        $this->dispatch('/api-local/solr_nodes');
    }

    public function testApiSolrMappingsIsDeniedToAnonymousUsers()
    {
        $this->logout();
        $this->expectException(PermissionDeniedException::class);
        $this->dispatch('/api-local/solr_mappings');
    }

    public function testApiSolrSearchFieldsIsDeniedToAnonymousUsers()
    {
        $this->logout();
        $this->expectException(PermissionDeniedException::class);
        $this->dispatch('/api-local/solr_search_fields');
    }

    public function testApiSolrNodesIsAllowedToAdmin()
    {
        $this->dispatch('/api-local/solr_nodes');
        $this->assertResponseStatusCode(200);
    }

    public function testApiSolrMappingsIsAllowedToAdmin()
    {
        $this->dispatch('/api-local/solr_mappings');
        $this->assertResponseStatusCode(200);
    }

    public function testApiSolrSearchFieldsIsAllowedToAdmin()
    {
        $this->dispatch('/api-local/solr_search_fields');
        $this->assertResponseStatusCode(200);
    }
}
