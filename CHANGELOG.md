# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.2.0 - 2025-05-20

### Fixed

- **Breaking change:** Change custom date range aggregation query variable from a single array `custom_date_range` to two separate variables `date_gte` and `date_lte`. This resolves an issue ([#84](https://github.com/alleyinteractive/elasticsearch-extensions/issues/84)) where if the start date was cleared, the end date would incorrectly replace the start date and the end date would be cleared. **Developers using the form provided in the custom date range class do not need to update their code, but those using custom processing with the `custom_date_range` parameter must update their code to use `date_gte` and `date_lte` instead.** 

## 0.1.0 - 2025-05-13

- Initial release of the plugin

### Added

- Add post meta and term aggregations
- Add SearchPress adapter

### Fixed

- Fix fatal error when param is of type `WP_Term_Query`

## [Unreleased]

- Initial creation of the plugin
- Add support for VIP Enterprise Search
- Add support for restricting indexable post meta, post types and taxonomies
- Add support for aggregations (faceted search)
- Add support for Co-Authors Plus
- Add support for running empty (aggregations only) searches
- Add support for writing tests
