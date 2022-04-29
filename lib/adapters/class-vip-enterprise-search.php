<?php
/**
 * Elasticsearch Extensions Adapters: VIP Enterprise Search Adapter
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\DSL;
use Elasticsearch_Extensions\Aggregations\Post_Date;
use Elasticsearch_Extensions\Aggregations\Post_Type;
use Elasticsearch_Extensions\Aggregations\Taxonomy;

/**
 * An adapter for WordPress VIP Enterprise Search.
 *
 * @package Elasticsearch_Extensions
 */
class VIP_Enterprise_Search extends Adapter {

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

	// TODO: REFACTOR LINE

	/**
	 * Add facets to EP query.
	 * Filters `ep_post_formatted_args`.
	 *
	 * @see \ElasticPress\Indexable\Post\Post::format_args() For the `ep_post_formatted_args` filter.
	 *
	 * @param array $query Formatted Elasticsearch query.
	 * @return array
	 */
	public function add_facets_to_ep_query( $query ): array {
		// Do we have any facets specified?
		$searched_facets = get_query_var( 'fs' );
		if ( empty( $searched_facets ) ) {
			return $query;
		}

		// Try to get the list of configured facets.
		$configured_facets = $this->get_facet_config();
		if ( empty( $configured_facets ) ) {
			return $query;
		}

		// Match searched facets against configured facets and augment the query accordingly.
		foreach ( $searched_facets as $facet_label => $facet_terms ) {
			// Skip any specified facets that are not configured.
			if ( empty( $configured_facets[ $facet_label ]['type'] ) ) {
				continue;
			}

			// Loop over terms and add each based on type.
			foreach ( $facet_terms as $facet_term ) { // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
				switch ( $configured_facets[ $facet_label ]['type'] ) {
					case 'taxonomy':
						$query['query']['function_score']['query']['bool']['must'][] = [
							'terms' => [
								'terms.' . $configured_facets[ $facet_label ]['taxonomy'] . '.slug' => [
									$facet_term,
								],
							],
						];
						break;
					case 'post_type':
						$query['query']['function_score']['query']['bool']['must'][] = [
							'terms' => [
								'post_type.raw' => [
									$facet_term,
								],
							],
						];
						break;
				}
			}
		}

		// TODO Write DSL for date faceting as configured.

		return $query;
	}

	/**
	 * Allow empty searching in conjunction with faceting.
	 * Filters `ep_post_formatted_args`.
	 *
	 * Since EP is not "expecting" our custom faceting,
	 * it does a match_all when no search query string is present.
	 * For that same reason, if there are no facets, EP's match_all is required.
	 *
	 * @see \ElasticPress\Indexable\Post\Post::format_args() For the `ep_post_formatted_args` filter.
	 *
	 * @param array $formatted_args Formatted ES args.
	 * @param array $args           WP args.
	 */
	public function allow_empty_search( $formatted_args, $args ) {
		if (
			isset( $args['s'] )
			&& '' === $args['s']
			&& true === $this->empty_search
		) {
			unset( $formatted_args['query']['match_all'] );
		}
		return $formatted_args;
	}

	/**
	 * Filters ElasticPress request query args to apply registered customizations.
	 * Filters `ep_query_request_args`.
	 *
	 * @see \ElasticPress\Elasticsearch::query() For the `ep_query_request_args` filter.
	 *
	 * @param array  $request_args Request arguments.
	 * @param string $path         Request path.
	 * @param string $index        Index name.
	 * @param string $type         Index type.
	 *
	 * @return array New request arguments.
	 */
	public function filter_ep_query_request_args( $request_args, $path, $index, $type ): array {
		// Try to convert the request body to an array, so we can work with it.
		$dsl = json_decode( $request_args['body'], true );
		if ( ! is_array( $dsl ) ) {
			return $request_args;
		}

		// Add our aggregations.
		if ( $this->get_aggregate_post_dates() ) {
			$post_date_facet = new Post_Date();
			$post_date_facet::set_calendar_interval( $this->facets_config['post_date']['calendar_interval'] );
			$dsl['aggs'] = array_merge( $dsl['aggs'], $post_date_facet->request() );
		}

		if ( $this->get_aggregate_post_types() ) {
			$post_type_facet = new Post_Type();
			$dsl['aggs']     = array_merge( $dsl['aggs'], $post_type_facet->request() );
		}

		if ( $this->get_aggregate_categories() ) {
			$category_facet = new Category();
			$dsl['aggs']    = array_merge( $dsl['aggs'], $category_facet->request() );
		}

		if ( $this->get_aggregate_tags() ) {
			$tag_facet   = new Tag();
			$dsl['aggs'] = array_merge( $dsl['aggs'], $tag_facet->request() );
		}

		$agg_taxonomies = $this->get_aggregate_taxonomies();
		if ( ! empty( $agg_taxonomies ) ) {
			foreach ( $agg_taxonomies as $agg_taxonomy ) {
				$dsl['aggs'][ "taxonomy_{$agg_taxonomy}" ] = [
					'terms' => [
						'size'  => 1000,
						'field' => "terms.{$agg_taxonomy}.slug",
					],
				];
			}
		}

		// Re-encode the body into the request args.
		$request_args['body'] = wp_json_encode( $dsl );

		return $request_args;
	}

	/**
	 * Set results from last query.
	 * Filters `ep_valid_response`.
	 *
	 * @see \ElasticPress\Elasticsearch::query() For the `ep_valid_response` filter.
	 *
	 * @param array $response Elasticsearch decoded response.
	 * @return void
	 */
	public function set_results( $response ) {
		// Set aggregations if applicable.
		if ( ! empty( $response['aggregations'] ) ) {
			$this->set_aggregations( $response['aggregations'] );
		}

		// TODO ensure this is a search and this isn't too broad.
		if ( apply_filters( 'elasticsearch_extensions_should_set_results', true ) ) {
			$this->results = $response;
		}
	}
}
