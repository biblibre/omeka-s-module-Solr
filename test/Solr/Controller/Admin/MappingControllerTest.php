<?php

namespace Solr\Test\Controller\Admin;

use Solr\SolrClient;
use Solr\Schema;
use Solr\Test\Controller\SolrControllerTestCase;

class MappingControllerTest extends SolrControllerTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $schema = new Schema();
        $solrClientStub = $this->createStub(SolrClient::class);
        $solrClientStub->method('schema')->willReturn($schema);
        $this->getServiceLocator()->setService('Solr\SolrClient', $solrClientStub);
    }

    public function testBrowseAction()
    {
        $this->dispatch($this->solrNode->mappingUrl('browse'));
        $this->assertResponseStatusCode(200);
    }

    public function testResourceBrowseAction()
    {
        $this->dispatch($this->solrNode->resourceMappingUrl('items', 'browse'));
        $this->assertResponseStatusCode(200);
    }

    public function testAddAction()
    {
        $this->dispatch($this->solrNode->resourceMappingUrl('items', 'add'));
        $this->assertResponseStatusCode(200);
    }

    public function testEditAction()
    {
        $this->dispatch($this->solrMapping->adminUrl('edit'));
        $this->assertResponseStatusCode(200);
    }

    public function testDeleteConfirmAction()
    {
        $this->dispatch($this->solrMapping->adminUrl('delete-confirm'));
        $this->assertResponseStatusCode(200);
    }

    public function testDeleteAction()
    {
        $solrMapping = $this->api()->create('solr_mappings', [
            'o:solr_node' => [
                'o:id' => $this->solrNode->id(),
            ],
            'o:resource_name' => 'items',
            'o:field_name' => 'dcterms_description_t',
            'o:source' => 'dcterms:description',
            'o:settings' => [
                'formatter' => '',
            ],
        ])->getContent();

        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get(\Omeka\Form\ConfirmForm::class);
        $this->dispatch($solrMapping->adminUrl('delete'), 'POST', [
            'confirmform_csrf' => $form->get('confirmform_csrf')->getValue(),
        ]);
        $this->assertRedirectTo($this->solrNode->resourceMappingUrl('items'));
    }
}
