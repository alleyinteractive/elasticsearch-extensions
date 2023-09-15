<?php

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\Features\Aggregations;
use Elasticsearch_Extensions\Features\Empty_Search;
use Elasticsearch_Extensions\Features\Search_Suggestions;
use Elasticsearch_Extensions\Interfaces\Adapterable;
use Elasticsearch_Extensions\Interfaces\Hookable;

class SearchPress implements Adapterable, Hookable {

	public function hook(): void {
		add_action( 'elasticsearch_extensions_enable_empty_search', [ $this, 'enable_empty_search' ] );
	}

	public function unhook(): void {
		// TODO: Implement unhook() method.
	}

	/**
	 * @return string[]
	 */
	public function supports(): array {
		return [
			Empty_Search::class,
			// Aggregations::class,
			// Search_Suggestions::class,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function get_field_map(): array {
		return [
			'category_id'                   => 'terms.category.term_id',
			'category_name'                 => 'terms.category.name.raw',
			'category_name.analyzed'        => 'terms.category.name',
			'category_slug'                 => 'terms.category.slug',
			// This isn't indexed in SearchPress by default.
			'category_tt_id'                => 'terms.category.term_taxonomy_id',
			// This isn't indexed in SearchPress by default.
			'comment_count'                 => 'comment_count',
			'menu_order'                    => 'menu_order',
			'post_author'                   => 'post_author.user_id',
			'post_author.user_nicename'     => 'post_author.user_nicename',
			'post_content'                  => 'post_content',
			'post_content.analyzed'         => 'post_content',
			'post_date'                     => 'post_date.date',
			'post_date.day'                 => 'post_date.day',
			'post_date.day_of_week'         => 'post_date.day_of_week',
			'post_date.day_of_year'         => 'post_date.day_of_year',
			'post_date.hour'                => 'post_date.hour',
			'post_date.minute'              => 'post_date.minute',
			'post_date.month'               => 'post_date.month',
			'post_date.second'              => 'post_date.second',
			'post_date.week'                => 'post_date.week',
			'post_date.year'                => 'post_date.year',
			'post_date_gmt'                 => 'post_date_gmt.date',
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
			'post_meta'                     => 'post_meta.%s.raw',
			'post_meta.analyzed'            => 'post_meta.%s.value',
			'post_meta.binary'              => 'post_meta.%s.boolean',
			'post_meta.date'                => 'post_meta.%s.date',
			'post_meta.datetime'            => 'post_meta.%s.datetime',
			'post_meta.double'              => 'post_meta.%s.double',
			'post_meta.long'                => 'post_meta.%s.long',
			'post_meta.signed'              => 'post_meta.%s.long',
			'post_meta.time'                => 'post_meta.%s.time',
			'post_meta.unsigned'            => 'post_meta.%s.long',
			'post_mime_type'                => 'post_mime_type',
			'post_modified'                 => 'post_modified.date',
			'post_modified.day'             => 'post_modified.day',
			'post_modified.day_of_week'     => 'post_modified.day_of_week',
			'post_modified.day_of_year'     => 'post_modified.day_of_year',
			'post_modified.hour'            => 'post_modified.hour',
			'post_modified.minute'          => 'post_modified.minute',
			'post_modified.month'           => 'post_modified.month',
			'post_modified.second'          => 'post_modified.second',
			'post_modified.week'            => 'post_modified.week',
			'post_modified.year'            => 'post_modified.year',
			'post_modified_gmt'             => 'post_modified_gmt.date',
			'post_modified_gmt.day'         => 'post_modified_gmt.day',
			'post_modified_gmt.day_of_week' => 'post_modified_gmt.day_of_week',
			'post_modified_gmt.day_of_year' => 'post_modified_gmt.day_of_year',
			'post_modified_gmt.hour'        => 'post_modified_gmt.hour',
			'post_modified_gmt.minute'      => 'post_modified_gmt.minute',
			'post_modified_gmt.month'       => 'post_modified_gmt.month',
			'post_modified_gmt.second'      => 'post_modified_gmt.second',
			'post_modified_gmt.week'        => 'post_modified_gmt.week',
			'post_modified_gmt.year'        => 'post_modified_gmt.year',
			'post_name'                     => 'post_name.raw',
			'post_parent'                   => 'post_parent',
			'post_password'                 => 'post_password',
			'post_title'                    => 'post_title.raw',
			'post_title.analyzed'           => 'post_title',
			'post_type'                     => 'post_type.raw',
			'tag_id'                        => 'terms.post_tag.term_id',
			'tag_name'                      => 'terms.post_tag.name.raw',
			'tag_name.analyzed'             => 'terms.post_tag.name',
			'tag_slug'                      => 'terms.post_tag.slug',
			// This isn't indexed in SearchPress by default.
			'tag_tt_id'                     => 'terms.post_tag.term_taxonomy_id',
			'term_id'                       => 'terms.%s.term_id',
			'term_name'                     => 'terms.%s.name.raw',
			'term_name.analyzed'            => 'terms.%s.name',
			'term_slug'                     => 'terms.%s.slug',
			// This isn't indexed in SearchPress by default.
			'term_tt_id'                    => 'terms.%s.term_taxonomy_id',
		];
	}
}
