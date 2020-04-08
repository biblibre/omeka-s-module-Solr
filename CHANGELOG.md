# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.6.0] - 2020-04-08

### Added

- Trigger events in value extractors to allow other modules to add fields
- Added ability to declare search fields
- Added ability to define `qf` and `mm` parameters
- Implemented several search operators (contains any word, contains all words,
  is like)


## [0.5.0] - 2020-03-06

### Added

- Added PlainText value formatter to trip HTML tags

### Changed

- Omeka S >= 2.0.0 required
- Travis: Test code style with php-cs-fixer
- Extract title as value for resource values

### Fixed

- Fix fq parameter for resource name
- Fixed compatibility issues with Omeka S 2.x


## [0.4.0] - 2017-11-09

### Changed

- Travis: use node 7
- Travis: Use phpenv's pecl to install solr extension

## [0.3.0] - 2017-08-07

### Changed

- Update code to work with Search 0.3.0

### Fixed

- Fix display of actions links in tables


## [0.2.0] - 2017-06-30

### Added

- Allow simple date in DateRange value formatter

### Changed

- Simplified configuration form
- Allow everyone to use all api adapters

### Fixed

- Fixed display of actions in 'browse' pages
- Added required [info] header in module.ini
- Fixed some issues with Travis


## [0.1.2] - 2016-12-20

First release

[0.6.0]: https://github.com/biblibre/omeka-s-module-Solr/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/biblibre/omeka-s-module-Solr/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/biblibre/omeka-s-module-Solr/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/biblibre/omeka-s-module-Solr/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/biblibre/omeka-s-module-Solr/compare/v0.1.2...v0.2.0
[0.1.2]: https://github.com/biblibre/omeka-s-module-Solr/releases/tag/v0.1.2
