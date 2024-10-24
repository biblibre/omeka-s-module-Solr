Solr module for Omeka S
=======================

This module provides a
`Search <https://github.com/biblibre/omeka-s-module-Search>`_ adapter for
`Solr <https://lucene.apache.org/solr/>`_.

Features
--------

* Search among items and item sets
* Highlighting (show fragments of documents that match the user’s query)
* Customizable facets and sort options
* Customizable mappings
* Extensible by other modules:

    * Custom transformations (modifications applied to a value before being added
      to a Solr document)
    * Custom value extractors (make module-specific data related to resources
      available to the indexer)
    * Solr documents can be altered right before being sent to Solr
    * Solr query can be altered right before being sent to Solr

* Compatible with the module [Group]
  (can return private resources if they belong to user's groups)

.. toctree::
   :maxdepth: 1
   :caption: User documentation

   user/quick-start
   user/configuration

.. toctree::
   :maxdepth: 1
   :caption: Developer documentation
   :glob:

   development/*
