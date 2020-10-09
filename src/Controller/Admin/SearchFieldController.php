<?php

/*
 * Copyright BibLibre, 2020
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

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Form\ConfirmForm;
use Solr\Form\Admin\SolrSearchFieldForm;

class SearchFieldController extends AbstractActionController
{
    public function browseAction()
    {
        $solrNodeId = $this->params('nodeId');

        $solrNode = $this->api()->read('solr_nodes', $solrNodeId)->getContent();
        $fields = $this->api()->search('solr_search_fields', [
            'solr_node_id' => $solrNode->id(),
        ])->getContent();

        $view = new ViewModel;
        $view->setVariable('solrNode', $solrNode);
        $view->setVariable('fields', $fields);

        return $view;
    }

    public function addAction()
    {
        $solrNodeId = $this->params('nodeId');

        $form = $this->getForm(SolrSearchFieldForm::class, [
            'solr_node_id' => $solrNodeId,
        ]);

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('schema', $this->getSolrSchema($solrNodeId));

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $data['o:solr_node']['o:id'] = $solrNodeId;
                $this->api()->create('solr_search_fields', $data);

                $this->messenger()->addSuccess('Search field added');

                return $this->redirect()->toRoute('admin/solr/node-id-fields', [
                    'nodeId' => $solrNodeId,
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
        $id = $this->params('id');

        $field = $this->api()->read('solr_search_fields', $id)->getContent();

        $form = $this->getForm(SolrSearchFieldForm::class, [
            'solr_node_id' => $solrNodeId,
        ]);
        $form->setData($field->jsonSerialize());

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('schema', $this->getSolrSchema($solrNodeId));

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $data['o:solr_node']['o:id'] = $solrNodeId;
                $this->api()->update('solr_search_fields', $id, $data);

                $this->messenger()->addSuccess('Search field updated');

                return $this->redirect()->toRoute('admin/solr/node-id-fields', [
                    'nodeId' => $solrNodeId,
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
        $response = $this->api()->read('solr_search_fields', $id);
        $field = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resourceLabel', 'search field');
        $view->setVariable('resource', $field);

        return $view;
    }

    public function deleteAction()
    {
        $id = $this->params('id');
        $field = $this->api()->read('solr_search_fields', $id)->getContent();

        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $this->api()->delete('solr_search_fields', $id);
                $this->messenger()->addSuccess('Search field successfully deleted');
            } else {
                $this->messenger()->addError('Search field could not be deleted');
            }
        }

        return $this->redirect()->toRoute('admin/solr/node-id-fields', [
            'nodeId' => $field->solrNode()->id(),
        ]);
    }

    protected function getSolrSchema($solrNodeId)
    {
        $solrNode = $this->api()->read('solr_nodes', $solrNodeId)->getContent();
        return $solrNode->schema()->getSchema();
    }
}
