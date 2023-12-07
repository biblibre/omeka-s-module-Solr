<?php
namespace Solr\Controller\Admin;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class TransformationsController extends AbstractActionController
{
    public function transformationListAction()
    {
        $solrMappingId = $this->params()->fromQuery('solr_mapping_id');
        $solrMapping = $solrMappingId ? $this->api()->read('solr_mappings', $solrMappingId)->getContent() : null;

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('solrMapping', $solrMapping);

        return $view;
    }

    public function transformationRowAction()
    {
        $transformationData = $this->params()->fromQuery('transformation_data');

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('transformationData', $transformationData);

        return $view;
    }

    public function transformationEditSidebarAction()
    {
        $transformationData = $this->params()->fromQuery('transformation_data');

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('transformationData', $transformationData);

        return $view;
    }
}
