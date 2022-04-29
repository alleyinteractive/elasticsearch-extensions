<?php
/**
 * Elasticsearch Extensions Adapters: VIP Enterprise Search Adapter
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\DSL;

/**
 * An adapter for WordPress VIP Enterprise Search.
 *
 * @package Elasticsearch_Extensions
 */
class VIP_Enterprise_Search extends Adapter {

	/**
	 * A callback for the ep_valid_response action hook. Parses aggregations
	 * from the raw Elasticsearch response and adds the buckets to the
	 * configured aggregations.
	 *
	 * @param array $response Response from the Elasticsearch server.
	 */
	public function action__ep_valid_response( $response ): void {
		if ( ! empty( $response['aggregations'] ) ) {
			$this->parse_aggregations( $response['aggregations'] );
		}
	}

	/**
	 * Gets the field map for this adapter.
	 *
	 * @return array The field map.
	 */
	public function get_field_map(): array {
		return [
			'category_id'                   => 'terms.category.term_id',
			'category_name'                 => 'terms.category.name.sortable',
			'category_name.analyzed'        => 'terms.category.name.analyzed',
			'category_slug'                 => 'terms.category.slug',
			'category_tt_id'                => 'terms.category.term_taxonomy_id',
			'comment_count'                 => 'comment_count',
			'menu_order'                    => 'menu_order',
			'post_author'                   => 'post_author.id',
			'post_author.user_nicename'     => 'post_author.login.raw',
			'post_content'                  => 'post_content',
			'post_content.analyzed'         => 'post_content',
			'post_date'                     => 'post_date',
			'post_date.day'                 => 'date_terms.day',
			'post_date.day_of_week'         => 'date_terms.dayofweek',
			'post_date.day_of_year'         => 'date_terms.dayofyear',
			'post_date.hour'                => 'date_terms.hour',
			'post_date.minute'              => 'date_terms.minute',
			'post_date.month'               => 'date_terms.month',
			'post_date.second'              => 'date_terms.second',
			'post_date.week'                => 'date_terms.week',
			'post_date.year'                => 'date_terms.year',
			'post_date_gmt'                 => 'post_date_gmt',
			'post_date_gmt.day'             => 'date_gmt_terms.day',
			'post_date_gmt.day_of_week'     => 'date_gmt_terms.day_of_week',
			'post_date_gmt.day_of_year'     => 'date_gmt_terms.day_of_year',
			'post_date_gmt.hour'            => 'date_gmt_terms.hour',
			'post_date_gmt.minute'          => 'date_gmt_terms.minute',
			'post_date_gmt.month'           => 'date_gmt_terms.month',
			'post_date_gmt.second'          => 'date_gmt_terms.second',
			'post_date_gmt.week'            => 'date_gmt_terms.week',
			'post_date_gmt.year'            => 'date_gmt_terms.year',
			'post_excerpt'                  => 'post_excerpt',
			'post_meta'                     => 'meta.%s.value.sortable',
			'post_meta.analyzed'            => 'meta.%s.value',
			'post_meta.binary'              => 'meta.%s.boolean',
			'post_meta.date'                => 'post_meta.%s.date',
			'post_meta.datetime'            => 'post_meta.%s.datetime',
			'post_meta.double'              => 'meta.%s.double',
			'post_meta.long'                => 'meta.%s.long',
			'post_meta.signed'              => 'post_meta.%s.signed',
			'post_meta.time'                => 'post_meta.%s.time',
			'post_meta.unsigned'            => 'post_meta.%s.unsigned',
			'post_mime_type'                => 'post_mime_type',
			'post_modified'                 => 'post_modified',
			'post_modified.day'             => 'modified_date_terms.day',
			'post_modified.day_of_week'     => 'modified_date_terms.day_of_week',
			'post_modified.day_of_year'     => 'modified_date_terms.day_of_year',
			'post_modified.hour'            => 'modified_date_terms.hour',
			'post_modified.minute'          => 'modified_date_terms.minute',
			'post_modified.month'           => 'modified_date_terms.month',
			'post_modified.second'          => 'modified_date_terms.second',
			'post_modified.week'            => 'modified_date_terms.week',
			'post_modified.year'            => 'modified_date_terms.year',
			'post_modified_gmt'             => 'post_modified_gmt',
			'post_modified_gmt.day'         => 'modified_date_gmt_terms.day',
			'post_modified_gmt.day_of_week' => 'modified_date_gmt_terms.day_of_week',
			'post_modified_gmt.day_of_year' => 'modified_date_gmt_terms.day_of_year',
			'post_modified_gmt.hour'        => 'modified_date_gmt_terms.hour',
			'post_modified_gmt.minute'      => 'modified_date_gmt_terms.minute',
			'post_modified_gmt.month'       => 'modified_date_gmt_terms.month',
			'post_modified_gmt.second'      => 'modified_date_gmt_terms.second',
			'post_modified_gmt.week'        => 'modified_date_gmt_terms.week',
			'post_modified_gmt.year'        => 'modified_date_gmt_terms.year',
			'post_name'                     => 'post_name.raw',
			'post_parent'                   => 'post_parent',
			'post_password'                 => 'post_password', // This isn't indexed on VIP.
			'post_title'                    => 'post_title.raw',
			'post_title.analyzed'           => 'post_title',
			'post_type'                     => 'post_type.raw',
			'tag_id'                        => 'terms.post_tag.term_id',
			'tag_name'                      => 'terms.post_tag.name.sortable',
			'tag_name.analyzed'             => 'terms.post_tag.name.analyzed',
			'tag_slug'                      => 'terms.post_tag.slug',
			'tag_tt_id'                     => 'terms.post_tag.term_taxonomy_id',
			'term_id'                       => 'terms.%s.term_id',
			'term_name'                     => 'terms.%s.name.sortable',
			'term_name.analyzed'            => 'terms.%s.name.analyzed',
			'term_slug'                     => 'terms.%s.slug',
			'term_tt_id'                    => 'terms.%s.term_taxonomy_id',
		];
	}

	/**
	 * Sets up the singleton by registering action and filter hooks and loading
	 * the DSL class with the field map.
	 */
	public function setup(): void {
		// Create an instance of the DSL class and inject this adapter's field map into it.
		$this->dsl = new DSL( $this->get_field_map() );

		// Register action hooks.
		add_action( 'ep_valid_response', [ $this, 'action__ep_valid_response' ] );

		// Register filter hooks.
		add_filter( 'ep_post_formatted_args', [ $this, 'filter__ep_post_formatted_args' ], 10, 3 );
		add_filter( 'ep_query_request_args', [ $this, 'filter__ep_query_request_args' ], 10, 4 );
	}
}
