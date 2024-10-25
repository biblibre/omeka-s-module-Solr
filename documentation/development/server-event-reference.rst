Server event reference
======================

``Solr\Indexer``
----------------

``solr.indexDocument``
^^^^^^^^^^^^^^^^^^^^^^

Triggered right before the document is sent to Solr.

Event's target is a ``Solr\SolrInputDocument``. Can be modified.

Parameters
""""""""""

``resource: Omeka\Entity\Resource``
    The resource being indexed
``solrNode: Solr\Api\Representation\SolrNodeRepresentation``
    The solr node where the document is being sent

Example
"""""""

.. code-block::

   $sharedEventManager->attach('Solr\Indexer', 'solr.indexDocument', function ($event) {
       $document = $event->getTarget();
       $resource = $event->getParam('resource');
       $solrNode = $event->getParam('solrNode');

       $document->addField('foo', 'bar');
   });

``Solr\Querier``
----------------

``solr.query``
^^^^^^^^^^^^^^

Triggered right before the query is sent to Solr.

Event's target is a ``Solr\SolrQuery``. Can be modified.

Parameters
""""""""""

``query: Search\Query``
    The original search query
``solrNode: Solr\Api\Representation\SolrNodeRepresentation``
    The solr node about to be queried

Example
"""""""

.. code-block::

   $sharedEventManager->attach('Solr\Querier', 'solr.query', function ($event) {
       $solrQuery = $event->getTarget();
       $query = $event->getParam('query');
       $solrNode = $event->getParam('solrNode');

       $solrQuery->addFilterQuery('foo:bar');
   });
