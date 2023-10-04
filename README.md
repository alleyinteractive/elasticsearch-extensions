# Elasticsearch Extensions

[![read me standard badge](https://img.shields.io/badge/readme%20style-standard-brightgreen.svg?style=flat-square)](https://github.com/RichardLitt/standard-readme)

A WordPress plugin to make integrating sites with
[Elasticsearch](https://www.elastic.co/webinars/getting-started-elasticsearch)
easier:

- Seamlessly and automatically integrates with different Elasticsearch plugins.
- Simplifies common Elasticsearch operations like adding aggregations and
  filtering indexable post types, taxonomies, and postmeta in an
  implementation-agnostic way.

## Table of Contents

- [Background](#background)
- [Releases](#Releases)
	- [Install](#install)
	- [Use](#use)
	- [Source](#from-source)
	- [Changelog](#changelog)
- [Development Process](#development-process)
	- [Contributing](#contributing)
- [Project Structure](#project-structure)
- [Third-Party Dependencies](#third-party-dependencies)
- [Related Efforts](#related-efforts)
- [Maintainers](#maintainers)
- [License](#license)

## Background

In most projects that need to integrate with an Elasticsearch provider, there is
a lot of common code—configuring the post types to index and search, taxonomies,
postmeta, and setting up front-end and back-end logic for handling aggregations
(often referred to as faceted search). This plugin aims to provide a unified
interface for the most common configuration options for Elasticsearch
integrations on WordPress sites to reduce the amount of custom code or
boilerplate that developers need to write on every project. Likewise, it aims to
centralize updates so if something changes in an Elasticsearch plugin, the
update likely only needs to be made to this plugin, which can in turn be updated
on the sites that depend on it.

This plugin is a work in progress. It currently supports the following adapters:

- [VIP Enterprise Search](https://docs.wpvip.com/technical-references/enterprise-search/)
- [SearchPress](https://github.com/alleyinteractive/searchpress)

With future support planned for the following adapters:

- [ElasticPress](https://www.elasticpress.io/)

Test coverage is also a work in progress, but the goal is to have test coverage
for all functionality for all adapters which also tests different versions of
Elasticsearch (as is necessary and practical) using GitHub Actions.

## Releases

All work on this plugin should be based off of `main` and pull requests should
be made into `main`. Once sufficient work has been done to justify making a new
release, it should be created using GitHub Releases, tagged, and described.
Releases must follow semantic versioning. During the active development phase,
releases should start with a `0` and we can move to a `1.0.0` once we have full
test coverage and full support for all target adapters.

For a list of all releases, please see the
[releases page](https://github.com/alleyinteractive/elasticsearch-extensions/releases).


### Install

In order to use this plugin, you must install it alongside a supported
Elasticsearch plugin:

- [VIP Enterprise Search](https://docs.wpvip.com/technical-references/enterprise-search/)


### Use

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


### From Source

In order to install this plugin to develop new features or fix bugs, you should
first install a clean WordPress site, then clone this repository into the
`plugins` folder:

```sh
$ cd wp-content/plugins
$ git clone git@github.com:alleyinteractive/elasticsearch-extensions.git
```

If you are using a fork to develop against, substitute the URL of your fork
above.

Next, you need to run `composer install` in order to install dependencies,
including the adapter plugins:

```sh
$ cd elasticsearch-extensions
$ composer install
```

This will install all current and planned adapter plugins. Regular WordPress
plugins will be installed to `plugins` and VIP Enterprise Search will be
installed as part of `vip-go-mu-plugins` in the `mu-plugins` folder.

In order to ensure you are developing against the latest version of these
plugins, you may need to run `composer update`. Since there is a custom package
configuration, if you haven't done so already, you may need to establish a
GitHub access token to read package information via the API. Composer will
prompt you if this is the case—follow the instructions it provides.

You will also need to ensure that you are running Elasticsearch in a location
where the plugins can access it, and you will need to configure a connection to
Elasticsearch for the plugin you are working on (e.g., VIP Enterprise Search).


### Changelog

This project keeps a [changelog](CHANGELOG.md).

## Development Process

See instructions above on installing from source. Pull requests are welcome from
the community and will be considered for inclusion.

### Contributing

See [our contributor guidelines](CONTRIBUTING.md) for instructions on how to
contribute to this open source project.


## Project Structure

This project is built on an adapter pattern to allow for a common API that
supports various Elasticsearch plugins. Adapters are extended from a base class
and are available in [lib/adapters](lib/adapters).

Aggregations (faceted search) are a major feature of this plugin. Aggregations
are defined, similarly to adapters, by extending a base class, and all
aggregations are defined in [lib/aggregations](lib/aggregations).

Tests are run via phpunit, verified by GitHub Actions when a pull request is
created, and are located in the [tests](tests) folder.

Interaction with Elasticsearch involves writing and modifying Elasticsearch DSL
([Domain-Specific Language](https://en.wikipedia.org/wiki/Domain-specific_language)).
There is a [DSL class](lib/class-dsl.php) which should contain all DSL that the
plugin needs to write. Specific DSL structures will differ based on which
adapter is being used, so the adapters are each responsible for modifying the
DSL that is written by the plugin in use in order to establish the same
functionality for a developer or user irrespective of their chosen integration.

This plugin uses a
[factory pattern](https://en.wikipedia.org/wiki/Factory_(object-oriented_programming))
to handle creating a controller that loads an adapter based on which plugin is
active.

Controller methods are chainable, following a common practice in Laravel, by
returning the current object at the end of a function call. See
[the controller class](lib/class-controller.php) to see how this works. This
design choice makes it possible to call several configuration methods in a row
without having to reference the object again (see the Use section above for
an example of how this works in practice).


## Third-Party Dependencies

In order to use this plugin, you need to have an Elasticsearch cluster available
and a supported Elasticsearch plugin installed and configured to connect to it.


## Related Efforts

This plugin is part of a collection of [Elasticsearch](https://www.elastic.co/)
open source plugins developed and maintained by [Alley](https://alley.co):

- [SearchPress](https://github.com/alleyinteractive/searchpress)
- [ES_WP_Query](https://github.com/alleyinteractive/es-wp-query)
- [ES Admin](https://github.com/alleyinteractive/es-admin)

## Maintainers

- [Alley](https://github.com/alleyinteractive)

![Alley logo](https://avatars.githubusercontent.com/u/1733454?s=200&v=4)

### Contributors

Thanks to all of the [contributors](CONTRIBUTORS.md) to this project.


## License

This project is licensed under the
[GNU Public License (GPL) version 3](LICENSE) or later.
