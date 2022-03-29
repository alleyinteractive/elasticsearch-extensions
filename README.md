# Elasticsearch Extensions

A WordPress plugin to make integrating sites with Elasticsearch easier.
Seamlessly and automatically integrates with different Elasticsearch plugins.
Simplifies common Elasticsearch operations like adding faceted search and
filtering indexable post types, taxonomies, and postmeta in an
implementation-agnostic way.

## Supported Adapters

* [VIP Enterprise Search](https://docs.wpvip.com/how-tos/vip-search/)

## Usage

Install and activate the plugin to have it interface with an existing installed
Elasticsearch plugin. This plugin will automatically detect which supported
Elasticsearch plugin is in use, and will register the appropriate hooks.

Customize the Elasticsearch integration using filter hooks in your site's
theme. A full list of available filter hooks is
[available in the wiki](https://github.com/alleyinteractive/elasticsearch-extensions/wiki).
