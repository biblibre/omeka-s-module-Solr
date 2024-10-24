# Solr module for Omeka S

This module provides a [Search](https://github.com/biblibre/omeka-s-module-Search) adapter for [Solr](https://lucene.apache.org/solr/).

## Requirements

- PHP >= 8.0
- Omeka S >= 3.1.0
- Solr >= 5.1.0
- Search module version 0.16.0 or greater is recommended (but not strictly required)

## Quick start

1. Install the [Search] module
2. Install this module
3. In the administration menu, click on "Solr"
4. Edit the default node and set the URI of your Solr instance.
5. Go to the mappings section of the default node (the icon next to the edit icon)
6. Create an item mapping with `dcterms:title` as source and `dcterms_title_txt` as Solr field
7. Edit the Solr node again and type `dcterms_title_txt` in "Query fields (qf)"
8. In Search admin pages:
    1. Add a new index using the Solr adapter
    2. Launch the indexation by clicking on the "reindex" button (two arrows forming a circle)
    3. Add a page using the created index
    4. In page configuration, you can enable/disable facet and sort fields (more fields can be created by going to the Solr admin page)
9. In your site configuration, add a navigation link to the search page
10. Go to your site, then click on the navigation link you just added.
11. The search form should appear. Type some text then submit the form to display the results.

## Features

- Search among items and item sets
- Highlighting (show fragments of documents that match the user’s query)
- Customizable facets and sort options
- Customizable mappings
- Extensible by other modules:
    - Custom transformations (modifications applied to a value before being added
      to a Solr document)
    - Custom value extractors (make module-specific data related to resources
      available to the indexer)
    - Solr documents can be altered right before being sent to Solr
    - Solr query can be altered right before being sent to Solr
- Compatible with the module [Group]
  (can return private resources if they belong to user's groups)

[Search]: https://github.com/biblibre/omeka-s-module-Search
[Group]: https://omeka.org/s/modules/Group
