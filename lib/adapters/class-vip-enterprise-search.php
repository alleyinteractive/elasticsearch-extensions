<?php
/**
 * Elasticsearch Extensions Adapters: VIP Enterprise Search Adapter
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\Aggregations\Aggregation;
use WP_Query;

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
	 * A callback for the ep_elasticpress_enabled filter hook. Overrides the
	 * normal behavior for ElasticPress to determine if it is enabled to allow
	 * for an empty search string, if allowable by the configuration on this
	 * adapter.
	 *
	 * @param bool     $enabled  Whether ElasticPress is enabled for the query or not.
	 * @param WP_Query $wp_query The WP_Query being examined.
	 *
	 * @return bool Whether ElasticPress should be active for the query or not.
	 */
	public function filter__ep_elasticpress_enabled( $enabled, $wp_query ) {
		if ( $this->get_allow_empty_search()
			&& $wp_query->is_search()
			&& $wp_query->is_main_query()
			&& ! is_admin()
		) {
			return true;
		}

		return $enabled;
	}

	/**
	 * A callback for the ep_post_formatted_args filter hook. Allows empty
	 * searches, if enabled, and applies any requested aggregation filters.
	 *
	 * @param array $formatted_args Elasticsearch query arguments.
	 * @param array $args           WordPress query arguments.
	 *
	 * @return array The modified Elasticsearch query arguments.
	 */
	public function filter__ep_post_formatted_args( $formatted_args, $args ) {
		// Enable empty search, if requested.
		if ( $this->get_allow_empty_search() && ! empty( $args['s'] ) ) {
			unset( $formatted_args['query']['match_all'] );
		}

		// Add requested aggregations.
		foreach ( $this->aggregations as $aggregation ) {
			/** Type hinting. @var Aggregation $aggregation */
			$filter = $aggregation->filter();
			if ( ! empty( $filter ) ) {
				$formatted_args['query']['function_score']['query']['bool']['must'][] = $filter;
			}
		}

		return $formatted_args;
	}

	/**
	 * A callback for the ep_query_request_args filter. Adds aggregations to the
	 * request so that the response will include aggregation buckets.
	 *
	 * @param array $request_args The request args to be filtered.
	 *
	 * @return array The filtered request args.
	 */
	public function filter__ep_query_request_args( $request_args ): array {
		// Try to convert the request body to an array, so we can work with it.
		$dsl = json_decode( $request_args['body'], true );
		if ( ! is_array( $dsl ) ) {
			return $request_args;
		}

		// Add our aggregations.
		foreach ( $this->aggregations as $aggregation ) {
			/** Type hinting. @var Aggregation $aggregation */
			$dsl['aggs'][ $aggregation->get_query_var() ] = $aggregation->request();
		}

		// Re-encode the body into the request args.
		$request_args['body'] = wp_json_encode( $dsl );

		return $request_args;
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
	 * Registers action and/or filter hooks with WordPress.
	 */
	public function hook(): void {
		// Register action hooks.
		add_action( 'ep_valid_response', [ $this, 'action__ep_valid_response' ] );

		// Register filter hooks.
		add_filter( 'ep_elasticpress_enabled', [ $this, 'filter__ep_elasticpress_enabled' ], 10, 2 );
		add_filter( 'ep_post_formatted_args', [ $this, 'filter__ep_post_formatted_args' ], 10, 3 );
		add_filter( 'ep_query_request_args', [ $this, 'filter__ep_query_request_args' ], 10, 4 );
	}

	/**
	 * Unregisters action and/or filter hooks that were registered in the hook
	 * method.
	 */
	public function unhook(): void {
		// Unregister action hooks.
		remove_action( 'ep_valid_response', [ $this, 'action__ep_valid_response' ] );

		// Unregister filter hooks.
		remove_filter( 'ep_elasticpress_enabled', [ $this, 'filter__ep_elasticpress_enabled' ] );
		remove_filter( 'ep_post_formatted_args', [ $this, 'filter__ep_post_formatted_args' ] );
		remove_filter( 'ep_query_request_args', [ $this, 'filter__ep_query_request_args' ] );
	}
}
