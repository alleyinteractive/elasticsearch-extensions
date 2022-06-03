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

Customize the Elasticsearch integration using the
`elasticsearch_extensions_config` action. Method calls can be chained for ease
of configuration. For example:

```php
add_action(
	'elasticsearch_extensions_config',
	 function( $es_config ) {
		$es_config
			->restrict_post_types( [ 'post', 'page' ] )
			->enable_empty_search()
			->enable_post_type_aggregation()
			->enable_taxonomy_aggregation( 'category' )
			->enable_taxonomy_aggregation( 'post_tag' );
	 }
);
```

For detailed information on all configuration options, action and filter hooks,
and how to integrate aggregation controls into the search template, see
[the wiki](https://github.com/alleyinteractive/elasticsearch-extensions/wiki).
