Indexation fields
=================

Indexation fields (also called "mappings") allow you to declare what is indexed
into Solr and thus, what data will be used by Solr to retrieve results.

Each type of resource (item, media, item set) have its own indexation fields
configuration. You only need to configure the part for the resource types you
want in your results.

.. image:: /images/node-mapping-browse.png

Below is the default indexation fields for items.

.. image:: /images/node-mapping-items-browse.png

Add a new mapping
-----------------

To create a new mapping, click on "Add new mapping"

.. image:: /images/node-mapping-items-add.png

Source
    The source is a piece of data stored in Omeka S. The list of available
    options depends on the resource type. It can also be augmented by other
    modules

Solr field
    The Solr field where that piece of data will be stored in Solr documents. You can either:

    * choose a Solr dynamic field in the list and a Solr field name will be auto-generated, or
    * type explicitely a Solr field name

Transformations
    Apply a series of transformations to the original data, either to filter
    out some values, to retrieve additional data, or to convert them to another
    format.

    Other modules can add their own transformations

Sort facets alphabetically
--------------------------

Solr sorts facet values in *Index order* using their binary representation:
uppercase letters come before lowercase letters, accented letters come after
``z``, and values starting with a whitespace or an invisible character (non
breaking space, zero width space, ...) come first.

To get a sort order that is insensitive to case, accents and leading
whitespace:

1. Create a mapping for the facet, using a ``*_ss`` Solr field (for instance
   ``dcterms_subject_facet_ss``), and add the transformation *Prefix with sort
   key (for facets)* **at the end** of the transformations list. Keep a
   separate mapping (``*_txt`` for instance) for full text search.
2. In the node's search fields, use this Solr field as *Facet field*.
3. In the search page facets configuration, set *Value renderer* to *Strip sort
   key (Solr)* and *Sort* to *Index order asc*.
4. Reindex.

The indexed value looks like ``elephant||Éléphant``: Solr sorts on the
normalized part, the value renderer only displays the part after ``||``. If the
value renderer is not configured, the whole value is displayed.
