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

### Configuration Options

#### `disable_empty_search()`

Disables the ability to search without specifying a search term.

##### Available Arguments

None.

#### `enable_cap_author_aggregation( array $args = [] )`

Enables an aggregation on Co-Authors Plus authors. Goes above and beyond what is
included in a normal taxonomy aggregation to map the labels to the Co-Authors
Plus author names. Additionally, adds custom sort options for sorting by author
last name, first name, or display name in addition to the normal sort options.

##### Available Arguments

| Key         | Default           | Possible Values                                                    | Description                                   |
|-------------|-------------------|--------------------------------------------------------------------|-----------------------------------------------|
| `label`     | `Author`          | `*`                                                                | The human-readable name for this aggregation. |
| `order`     | `DESC`            | `ASC`, `DESC`                                                      | How to sort by the `orderby` field.           |
| `orderby`   | `count`           | `count`, `display_name`, `first_name`, `key`, `label`, `last_name` | The field to order results by.                |
| `query_var` | `taxonomy_author` | `*`                                                                | The query var to use in the URL.              |

#### `enable_empty_search()`

Enables the ability to search without specifying a search term.

##### Available Arguments

None.

#### `enable_post_date_aggregation( array $args = [] )`

Enables an aggregation based on the post date according to a specified interval
which defaults to years. The default behavior is to allow aggregating by
publication date within a specific year, e.g., "show me all articles that were
published in 2020." The interval can be changed to aggregate data by quarter,
month, week, day, etc.

##### Available Arguments

| Key         | Default     | Possible Values                                              | Description                                   |
|-------------|-------------|--------------------------------------------------------------|-----------------------------------------------|
| `interval`  | `year`      | `year`, `quarter`, `month`, `week`, `day`, `hour`, `minute`  | The unit of time to aggregate results by.     |
| `label`     | `Date`      | `*`                                                          | The human-readable name for this aggregation. |
| `order`     | `DESC`      | `ASC`, `DESC`                                                | How to sort by the `orderby` field.           |
| `orderby`   | `count`     | `count`, `key`, `label`                                      | The field to order results by.                |
| `query_var` | `post_date` | `*`                                                          | The query var to use in the URL.              |

#### `enable_post_type_aggregation( array $args = [] )`

Enables an aggregation based on post type (e.g., posts, pages, etc).

##### Available Arguments

| Key         | Default        | Possible Values         | Description                                   |
|-------------|----------------|-------------------------|-----------------------------------------------|
| `label`     | `Content Type` | `*`                     | The human-readable name for this aggregation. |
| `order`     | `DESC`         | `ASC`, `DESC`           | How to sort by the `orderby` field.           |
| `orderby`   | `count`        | `count`, `key`, `label` | The field to order results by.                |
| `query_var` | `post_date`    | `*`                     | The query var to use in the URL.              |

#### `enable_relative_date_aggregation( array $args = [] )`

Enables an aggregation based on whether a post was published within a certain
number of days from the current date. Defaults to providing aggregation
buckets for the last 7 days, last 30 days, and last 90 days, but can be
customized with any number of day intervals.

##### Available Arguments

| Key         | Default         | Possible Values                 | Description                                                             |
|-------------|-----------------|---------------------------------|-------------------------------------------------------------------------|
| `intervals` | `[7, 30, 90]`   | Any array of positive integers. | The number of days prior to the current date to include in each bucket. |
| `label`     | `Relative Date` | `*`                             | The human-readable name for this aggregation.                           |
| `order`     | `DESC`          | `ASC`, `DESC`                   | How to sort by the `orderby` field.                                     |
| `orderby`   | `count`         | `count`, `key`, `label`         | The field to order results by.                                          |
| `query_var` | `relative_date` | `*`                             | The query var to use in the URL.                                        |

#### `enable_taxonomy_aggregation( string $taxonomy, array $args = [] )`

Enables an aggregation based on terms in the specified taxonomy. Will
automatically read the taxonomy label and term names from the WordPress
database, so the only required field is the taxonomy slug.

##### Available Arguments

| Key         | Default                            | Possible Values         | Description                                   |
|-------------|------------------------------------|-------------------------|-----------------------------------------------|
| `label`     | `$taxonomy->labels->singular_name` | `*`                     | The human-readable name for this aggregation. |
| `order`     | `DESC`                             | `ASC`, `DESC`           | How to sort by the `orderby` field.           |
| `orderby`   | `count`                            | `count`, `key`, `label` | The field to order results by.                |
| `query_var` | `taxonomy_{$taxonomy->name}`       | `*`                     | The query var to use in the URL.              |

#### `restrict_post_types( array $post_types )`

Restricts the indexable and searchable post types to the list of post type
slugs provided.

#### `restrict_taxonomies( array $taxonomies )`

Restricts the indexable and searchable taxonomies to the list of taxonomy slugs
provided.

## Hooks

### Actions

#### `do_action( 'elasticsearch_extensions_config', Controller $controller );`

An action hook that fires after this plugin is initialized and is ready for
configuration.

##### Parameters

**`$controller`**

*(Elasticsearch_Extensions\Controller)* The instance of the controller class, which is used to configure the plugin.

##### Source

[`lib/class-controller.php`](lib/class-controller.php)

### Filters

#### `apply_filters( 'elasticsearch_extensions_aggregation_date_format', string $format, string $interval, string $aggregation, string $mapped_field )`

Filters the Elasticsearch date format string used in the `date_histogram`
aggregation.

##### Parameters

**`$format`**

*(string)* The format to use.

**`$interval`**

*(string)* The interval to aggregate (year, month, etc).

**`$aggregation`**

*(string)* The aggregation slug to use for grouping.

**`$mapped_field`**

*(string)* The mapped field to use for the date aggregation.

##### Source

[`lib/class-dsl.php`](lib/class-dsl.php)

#### `apply_filters( 'elasticsearch_extensions_aggregation_date_label', string $label )`

Allows the label for a date aggregation to be filtered. For example, can be used
to convert "2022-04" to "April 2022".

##### Parameters

**`$label`**

*(string)* The label to use.

##### Source

[`lib/aggregations/class-post-date.php`](lib/aggregations/class-post-date.php)

#### `apply_filters( 'elasticsearch_extensions_aggregation_post_type_label', string $label )`

Allows the label field for a post type aggregation to be filtered. For example,
this filter could be used to use the plural form of the label instead of the
singular.

##### Parameters

**`$label`**

*(string)* The slug of the label to use. See get_post_type_labels() for a full list of options.

##### Source

[`lib/aggregations/class-post-type.php`](lib/aggregations/class-post-type.php)

#### `apply_filters( 'elasticsearch_extensions_aggregation_term_size', int $size, string $aggregation )`

Allows the `size` property of a terms aggregation to be filtered. By default,
Elasticsearch Extensions will return up to 1000 different terms on a terms
aggregation, but this value can be increased for completeness or decreased for
performance.

##### Parameters

**`$size`**

*(int)* The maximum number of terms to return. Defaults to 1000.

**`$aggregation`**

*(string)* The unique aggregation slug.

##### Source

[`lib/class-dsl.php`](lib/class-dsl.php)

#### `apply_filters( 'elasticsearch_extensions_buckets', Bucket[] $buckets, Aggregation $aggregation )`

Allows the buckets to be filtered before they are displayed, which can allow for
removing certain items, or changing labels, or changing the sort order of
buckets.

##### Parameters

**`$buckets`**

*(Elasticsearch_Extensions\Aggregations\Bucket[])* The array of buckets to filter.

**`$aggregation`**

*(Elasticsearch_Extensions\Aggregations\Aggregation)* The aggregation that the buckets are associated with.

##### Source

[`lib/aggregations/class-aggregation.php`](lib/aggregations/class-aggregation.php)

#### `apply_filters( 'elasticsearch_extensions_searchable_fields', string[] $fields, DSL $dsl )`

Filter the Elasticsearch fields to search. The fields should already be mapped
(use `$dsl->map_field()`, `$dsl->map_tax_field()`, or `$dsl->map_meta_field()`
to map a field).

##### Parameters

**`$fields`**

*(string[])* A list of string fields to search against. Defaults to the post
title at 3x priority, the post excerpt, the post content, the post author's
display name, and the attachment's alt text (for attachments).

**`$dsl`**

*(Elasticsearch_Extensions\DSL)* The DSL object, which provides `map_field`
functionality.

##### Source

[`lib/class-dsl.php`](lib/class-dsl.php)

#### `apply_filters( 'elasticsearch_extensions_aggregation_taxonomy_field', string $field, WP_Taxonomy $taxonomy )`

Filters the unmapped field name used in a taxonomy aggregation. Defaults to
`term_slug`, but can be changed to use the term ID, for example.

**`$field`**

*(string)* The field to aggregate.

**`$taxonomy`**

*(WP_Taxonomy)* The taxonomy for this aggregation.

##### Source

[`lib/aggregations/class-taxonomy.php`](lib/class-taxonomy.php)
