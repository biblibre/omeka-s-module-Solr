<?php

/*
 * Copyright BibLibre, 2017-2020
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

namespace Solr\Controller\Admin;

use Laminas\Http\Client as HttpClient;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Form\ConfirmForm;
use Solr\Api\Representation\SolrNodeRepresentation;
use Solr\Form\Admin\SolrMappingForm;
use Solr\Form\Admin\SolrMappingImportForm;
use Solr\ValueExtractor\Manager as ValueExtractorManager;

class MappingController extends AbstractActionController
{
    protected $valueExtractorManager;
    protected $httpClient;

    public function setValueExtractorManager(ValueExtractorManager $valueExtractorManager)
    {
        $this->valueExtractorManager = $valueExtractorManager;
    }

    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function browseAction()
    {
        $solrNodeId = $this->params('nodeId');
        $solrNode = $this->api()->read('solr_nodes', $solrNodeId)->getContent();

        $valueExtractors = [];
        foreach ($this->valueExtractorManager->getRegisteredNames() as $name) {
            $valueExtractors[$name] = $this->valueExtractorManager->get($name);
        }

        $view = new ViewModel;
        $view->setVariable('solrNode', $solrNode);
        $view->setVariable('valueExtractors', $valueExtractors);

        return $view;
    }

    public function browseResourceAction()
    {
        $solrNodeId = $this->params('nodeId');
        $resourceName = $this->params('resourceName');

        $solrNode = $this->api()->read('solr_nodes', $solrNodeId)->getContent();
        $mappings = $this->api()->search('solr_mappings', [
            'solr_node_id' => $solrNode->id(),
            'resource_name' => $resourceName,
        ])->getContent();

        $view = new ViewModel;
        $view->setVariable('solrNode', $solrNode);
        $view->setVariable('resourceName', $resourceName);
        $view->setVariable('mappings', $mappings);

        return $view;
    }

    public function addAction()
    {
        $solrNodeId = $this->params('nodeId');
        $resourceName = $this->params('resourceName');

        $form = $this->getForm(SolrMappingForm::class, [
            'solr_node_id' => $solrNodeId,
            'resource_name' => $resourceName,
        ]);

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('schema', $this->getSolrSchema($solrNodeId));

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $data['o:solr_node']['o:id'] = $solrNodeId;
                $data['o:resource_name'] = $resourceName;
                $this->api()->create('solr_mappings', $data);

                $this->messenger()->addSuccess('Solr mapping created.');

                return $this->redirect()->toRoute('admin/solr/node-id-mapping-resource', [
                    'nodeId' => $solrNodeId,
                    'resourceName' => $resourceName,
                ]);
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        return $view;
    }

    public function editAction()
    {
        $solrNodeId = $this->params('nodeId');
        $resourceName = $this->params('resourceName');
        $id = $this->params('id');

        $mapping = $this->api()->read('solr_mappings', $id)->getContent();

        $form = $this->getForm(SolrMappingForm::class, [
            'solr_node_id' => $solrNodeId,
            'resource_name' => $resourceName,
        ]);
        $form->setData($mapping->jsonSerialize());

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('schema', $this->getSolrSchema($solrNodeId));

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $data['o:solr_node']['o:id'] = $solrNodeId;
                $data['o:resource_name'] = $resourceName;
                $this->api()->update('solr_mappings', $id, $data);

                $this->messenger()->addSuccess('Solr mapping modified.');

                return $this->redirect()->toRoute('admin/solr/node-id-mapping-resource', [
                    'nodeId' => $solrNodeId,
                    'resourceName' => $resourceName,
                ]);
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        return $view;
    }

    public function deleteConfirmAction()
    {
        $id = $this->params('id');
        $response = $this->api()->read('solr_mappings', $id);
        $mapping = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resourceLabel', 'solr mapping');
        $view->setVariable('resource', $mapping);

        return $view;
    }

    public function deleteAction()
    {
        $id = $this->params('id');
        $mapping = $this->api()->read('solr_mappings', $id)->getContent();
        $solrNodeId = $mapping->solrNode()->id();

        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $this->api()->delete('solr_mappings', $id);
                $this->messenger()->addSuccess('Solr mapping successfully deleted');
            } else {
                $this->messenger()->addError('Solr mapping could not be deleted');
            }
        }

        return $this->redirect()->toRoute('admin/solr/node-id-mapping-resource', [
            'nodeId' => $solrNodeId,
            'resourceName' => $mapping->resourceName(),
        ]);
    }

    public function importAction()
    {
        $solrNodeId = $this->params('nodeId');
        $solrNode = $this->api()->read('solr_nodes', $solrNodeId)->getContent();
        $form = $this->getForm(SolrMappingImportForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $data = $form->getData();
                try {
                    $mappings = $this->getMappingsFromUrl($data['url']);
                    if ($data['delete_mappings']) {
                        $this->deleteMappings($solrNode);
                    }

                    $this->importMappings($solrNode, $mappings);
                    $this->messenger()->addSuccess(sprintf($this->translate('%d mappings were found and imported'), count($mappings)));

                    return $this->redirect()->toRoute('admin/solr/node-id-mapping', [
                        'nodeId' => $solrNodeId,
                    ]);
                } catch (\Exception $e) {
                    $this->messenger()->addError($e->getMessage());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('solrNode', $solrNode);
        $view->setVariable('form', $form);

        return $view;
    }

    protected function getSolrSchema($solrNodeId)
    {
        $solrNode = $this->api()->read('solr_nodes', $solrNodeId)->getContent();
        return $solrNode->schema()->getSchema();
    }

    protected function deleteMappings(SolrNodeRepresentation $solrNode)
    {
        $ids = $this->api()->search('solr_mappings', ['solr_node_id' => $solrNode->id()], ['returnScalar' => 'id'])->getContent();
        $this->api()->batchDelete('solr_mappings', $ids);
    }

    protected function importMappings(SolrNodeRepresentation $solrNode, array $mappings)
    {
        foreach ($mappings as $mapping) {
            $mapping['o:solr_node'] = ['o:id' => $solrNode->id()];
            $this->api()->create('solr_mappings', $mapping);
        }
    }

    protected function getMappingsFromUrl(string $url)
    {
        $this->httpClient->reset();
        $this->httpClient->setMethod('GET');
        $this->httpClient->setUri($url);
        $response = $this->httpClient->send();
        if (!$response->isSuccess()) {
            throw new \Exception(sprintf('HTTP request failed for %s : %s', $url, $response->renderStatusLine()));
        }

        $mappings = json_decode($response->getBody(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(sprintf('json_decode error: %s', json_last_error_msg()));
        }

        return $mappings;
    }
}
