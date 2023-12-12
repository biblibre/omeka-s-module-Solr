# Solr module for Omeka S

This module provides a [Search](https://github.com/biblibre/omeka-s-module-Search) adapter for [Solr](https://lucene.apache.org/solr/).

## Requirements

- PHP >= 8.0
- Omeka S >= 3.1.0
- Solr >= 5.1.0

## Quick start

1. Install the [Search] module
2. Install this module
3. In Search admin pages:
  1. add a new index using the Solr adapter,
  2. configure correctly the host, port, and path of the Solr instance,
  3. launch the indexation by clicking on the "reindex" button (two arrows forming a circle),
  4. then add a page using the created index.
  5. In page configuration, you can enable/disable facet and sort fields (more fields can be created by going to the Solr admin page - link in the navigation menu)
4. In your site configuration, add a navigation link to the search page
5. Go to your site, then click on the navigation link you just added.
6. The search form should appear. Type some text then submit the form to display the results.

[Search]: https://github.com/biblibre/omeka-s-module-Search
