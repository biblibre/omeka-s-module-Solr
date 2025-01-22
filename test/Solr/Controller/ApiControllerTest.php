<?php

namespace Solr\Test\Controller;

use Omeka\Mvc\Exception\PermissionDeniedException;
use Solr\Test\Controller\SolrControllerTestCase;

class ApiControllerTest extends SolrControllerTestCase
{
    public function testApiSolrNodesIsDeniedToAnonymousUsers()
    {
        $this->logout();
        $this->expectException(PermissionDeniedException::class);
        $this->dispatch('/api/solr_nodes');
    }

    public function testApiSolrMappingsIsDeniedToAnonymousUsers()
    {
        $this->logout();
        $this->expectException(PermissionDeniedException::class);
        $this->dispatch('/api/solr_mappings');
    }

    public function testApiSolrSearchFieldsIsDeniedToAnonymousUsers()
    {
        $this->logout();
        $this->expectException(PermissionDeniedException::class);
        $this->dispatch('/api/solr_search_fields');
    }

    public function testApiSolrNodesIsAllowedToAdmin()
    {
        $this->dispatch('/api/solr_nodes');
        $this->assertResponseStatusCode(200);
    }

    public function testApiSolrMappingsIsAllowedToAdmin()
    {
        $this->dispatch('/api/solr_mappings');
        $this->assertResponseStatusCode(200);
    }

    public function testApiSolrSearchFieldsIsAllowedToAdmin()
    {
        $this->dispatch('/api/solr_search_fields');
        $this->assertResponseStatusCode(200);
    }
}
