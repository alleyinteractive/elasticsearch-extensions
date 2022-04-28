# Elasticsearch Extensions

A WordPress plugin to make integrating sites with Elasticsearch easier.
Seamlessly and automatically integrates with different Elasticsearch plugins.
Simplifies common Elasticsearch operations like adding aggregations and
filtering indexable post types, taxonomies, and postmeta in an
implementation-agnostic way.

## Supported Adapters

* [VIP Enterprise Search](https://docs.wpvip.com/how-tos/vip-search/)

## Usage

Install and activate the plugin to have it interface with an existing installed
Elasticsearch plugin. This plugin will automatically detect which supported
Elasticsearch plugin is in use, and will register the appropriate hooks.

Customize the Elasticsearch integration using the `elasticsearch_extensions()`
function. Method calls can be chained for ease of configuration. For example:

```php
if ( function_exists( 'elasticsearch_extensions' ) ) {
	elasticsearch_extensions()
		->enable_empty_search()
		->enable_post_type_aggregation()
		->enable_taxonomy_aggregation( 'my-cool-taxonomy' );
}
```

## Aggregations

TODO Add documentation regarding all of the aggregation-related features as well
as code samples for setting up aggregations.
