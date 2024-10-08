<?php

/*
 * Copyright BibLibre, 2016
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
use Solr\Form\Admin\SolrNodeForm;

class NodeController extends AbstractActionController
{
    public function browseAction()
    {
        $response = $this->api()->search('solr_nodes');
        $nodes = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('nodes', $nodes);
        return $view;
    }

    public function addAction()
    {
        $form = $this->getForm(SolrNodeForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $response = $this->api($form)->create('solr_nodes', $data);
                if ($response) {
                    $this->messenger()->addSuccess('Solr node created.'); // @translate
                    return $this->redirect()->toRoute('admin/solr');
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);

        return $view;
    }

    public function editAction()
    {
        $id = $this->params('id');
        $node = $this->api()->read('solr_nodes', $id)->getContent();

        $form = $this->getForm(SolrNodeForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $response = $this->api($form)->update('solr_nodes', $id, $data);
                if ($response) {
                    $this->messenger()->addSuccess('Solr node updated.'); // @translate
                    return $this->redirect()->toRoute('admin/solr');
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        } else {
            $form->setData($node->jsonSerialize());
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);

        return $view;
    }

    public function deleteConfirmAction()
    {
        $id = $this->params('id');
        $response = $this->api()->read('solr_nodes', $id);
        $node = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resourceLabel', 'solr node');
        $view->setVariable('resource', $node);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $this->api()->delete('solr_nodes', $this->params('id'));
                $this->messenger()->addSuccess('Solr node successfully deleted');
            } else {
                $this->messenger()->addError('Solr node could not be deleted');
            }
        }
        return $this->redirect()->toRoute('admin/solr');
    }
}
