Quick start
===========

1. Install the `Search <https://omeka.org/s/modules/Search>`_ module
2. Install this module
3. In the administration menu, click on "Solr"
4. Edit the default node and set the URI of your Solr instance.
5. Go to the mappings section of the default node (the icon next to the edit icon)
6. Create an item mapping with ``dcterms:title`` as source and ``dcterms_title_txt`` as Solr field
7. Edit the Solr node again and type ``dcterms_title_txt`` in "Query fields (qf)"
8. In Search admin pages:

    a. Add a new index using the Solr adapter
    b. Launch the indexation by clicking on the "reindex" button (two arrows forming a circle)
    c. Add a page using the created index
    d. In page configuration, you can enable/disable facet and sort fields (more fields can be created by going to the Solr admin page)

9. In your site configuration, add a navigation link to the search page
10. Go to your site, then click on the navigation link you just added.
11. The search form should appear. Type some text then submit the form to display the results.
