# Creating Adapters

Adapters allow Elasticsearch Extensions to work with a variety of Elasticsearch indexing plugins using a common API. This document describes how to create a new adapter.

The adapter needs to be able to do the following:
- Map fields from WordPress to Elasticsearch
-

## Field Map

The Adapter tells the rest of the plugin how to map fields to the Elasticsearch index. Here is a starting point for your adapter:

```php
protected function get_field_map(): array {
	return [
		'category_id'                   => 'category_id',
		'category_name'                 => 'category_name',
		'category_name.analyzed'        => 'category_name.analyzed',
		'category_slug'                 => 'category_slug',
		'category_tt_id'                => 'category_tt_id',
		'comment_count'                 => 'comment_count',
		'menu_order'                    => 'menu_order',
		'post_author'                   => 'post_author',
		'post_author.user_nicename'     => 'post_author.user_nicename',
		'post_content'                  => 'post_content',
		'post_content.analyzed'         => 'post_content.analyzed',
		'post_date'                     => 'post_date',
		'post_date.day'                 => 'post_date.day',
		'post_date.day_of_week'         => 'post_date.day_of_week',
		'post_date.day_of_year'         => 'post_date.day_of_year',
		'post_date.hour'                => 'post_date.hour',
		'post_date.minute'              => 'post_date.minute',
		'post_date.month'               => 'post_date.month',
		'post_date.second'              => 'post_date.second',
		'post_date.week'                => 'post_date.week',
		'post_date.year'                => 'post_date.year',
		'post_date_gmt'                 => 'post_date_gmt',
		'post_date_gmt.day'             => 'post_date_gmt.day',
		'post_date_gmt.day_of_week'     => 'post_date_gmt.day_of_week',
		'post_date_gmt.day_of_year'     => 'post_date_gmt.day_of_year',
		'post_date_gmt.hour'            => 'post_date_gmt.hour',
		'post_date_gmt.minute'          => 'post_date_gmt.minute',
		'post_date_gmt.month'           => 'post_date_gmt.month',
		'post_date_gmt.second'          => 'post_date_gmt.second',
		'post_date_gmt.week'            => 'post_date_gmt.week',
		'post_date_gmt.year'            => 'post_date_gmt.year',
		'post_excerpt'                  => 'post_excerpt',
		'post_meta'                     => 'post_meta',
		'post_meta.analyzed'            => 'post_meta.analyzed',
		'post_meta.binary'              => 'post_meta.binary',
		'post_meta.date'                => 'post_meta.date',
		'post_meta.datetime'            => 'post_meta.datetime',
		'post_meta.double'              => 'post_meta.double',
		'post_meta.long'                => 'post_meta.long',
		'post_meta.signed'              => 'post_meta.signed',
		'post_meta.time'                => 'post_meta.time',
		'post_meta.unsigned'            => 'post_meta.unsigned',
		'post_mime_type'                => 'post_mime_type',
		'post_modified'                 => 'post_modified',
		'post_modified.day'             => 'post_modified.day',
		'post_modified.day_of_week'     => 'post_modified.day_of_week',
		'post_modified.day_of_year'     => 'post_modified.day_of_year',
		'post_modified.hour'            => 'post_modified.hour',
		'post_modified.minute'          => 'post_modified.minute',
		'post_modified.month'           => 'post_modified.month',
		'post_modified.second'          => 'post_modified.second',
		'post_modified.week'            => 'post_modified.week',
		'post_modified.year'            => 'post_modified.year',
		'post_modified_gmt'             => 'post_modified_gmt',
		'post_modified_gmt.day'         => 'post_modified_gmt.day',
		'post_modified_gmt.day_of_week' => 'post_modified_gmt.day_of_week',
		'post_modified_gmt.day_of_year' => 'post_modified_gmt.day_of_year',
		'post_modified_gmt.hour'        => 'post_modified_gmt.hour',
		'post_modified_gmt.minute'      => 'post_modified_gmt.minute',
		'post_modified_gmt.month'       => 'post_modified_gmt.month',
		'post_modified_gmt.second'      => 'post_modified_gmt.second',
		'post_modified_gmt.week'        => 'post_modified_gmt.week',
		'post_modified_gmt.year'        => 'post_modified_gmt.year',
		'post_name'                     => 'post_name',
		'post_parent'                   => 'post_parent',
		'post_password'                 => 'post_password',
		'post_title'                    => 'post_title',
		'post_title.analyzed'           => 'post_title.analyzed',
		'post_type'                     => 'post_type',
		'tag_id'                        => 'tag_id',
		'tag_name'                      => 'tag_name',
		'tag_name.analyzed'             => 'tag_name.analyzed',
		'tag_slug'                      => 'tag_slug',
		'tag_tt_id'                     => 'tag_tt_id',
		'term_id'                       => 'term_id',
		'term_name'                     => 'term_name',
		'term_name.analyzed'            => 'term_name.analyzed',
		'term_slug'                     => 'term_slug',
		'term_tt_id'                    => 'term_tt_id',
	];
}
```

