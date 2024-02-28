<?php
/**
 * SearchPress class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\REST_API\Post_Suggestion_Search_Handler;

/**
 * SearchPress class.
 */
class SearchPress extends Adapter {

	/**
	 * A callback for the sp_pre_search_results action hook. Parses aggregations
	 * from the raw Elasticsearch response and adds the buckets to the
	 * configured aggregations.
	 *
	 * @param \WP_Post[]|null $results Query results.
	 * @param \SP_WP_Search   $search  Search object.
	 * @return \WP_Post[]|null The unmodified query results.
	 */
	public function extract_aggs_from_results( $results, $search ) {
		$aggs = $search->get_results( 'facets' );
		if ( ! empty( $aggs ) ) {
			$this->parse_aggregations( $aggs );
		}

		return $results;
	}

	/**
	 * A callback for the sp_config_sync_post_types filter hook. Filters the list
	 * of post types that should be indexed in Elasticsearch based on what was
	 * configured. If no restrictions were specified, uses the default list
	 * (all public post types).
	 *
	 * @todo Restricted_post_types needs a refactor to set the searchable post types, not indexed post types. This was
	 *       written to be consistent with the VIP Enterprise Search adapter for now.
	 *
	 * @param array $post_types An array of post type slugs.
	 * @return array The modified list of post types to index.
	 */
	public function apply_sync_post_types( $post_types ) {
		$restricted_post_types = $this->get_restricted_post_types();
		return empty( $restricted_post_types ) ? $post_types : $restricted_post_types;
	}

	/**
	 * A callback for the sp_config_sync_statuses filter hook. Filters the list
	 * of post statuses that should be indexed in SearchPress based on what was
	 * configured. If no restrictions were specified, uses the default list.
	 *
	 * @param array $post_statuses Indexabled post statuses.
	 * @return string[] The modified list of post status to index.
	 */
	public function apply_sync_post_statuses( $post_statuses ): array {

		// Determine whether we should filter the list or not.
		$restricted_post_statuses = $this->get_restricted_post_statuses();

		if ( empty( $restricted_post_statuses ) ) {
			return $post_statuses;
		}

		return array_unique( array_merge( $post_statuses, $restricted_post_statuses ) );
	}

	/**
	 * A callback for the sp_config_mapping filter. Adds the 'search_suggest'
	 * field to the mapping if search suggestions are enabled.
	 *
	 * @param array $mapping Post mapping.
	 * @return array Updated mapping.
	 */
	public function add_search_suggest_to_mapping( $mapping ) {
		if ( $this->get_enable_search_suggestions() ) {
			$mapping['mappings']['properties']['search_suggest'] = [
				'type' => 'search_as_you_type',
			];
		}

		return $mapping;
	}

	/**
	 * A callback for the sp_post_pre_index filter. Indexes
	 * text for the 'search_suggest' field.
	 *
	 * @param array    $data    `sp_post_pre_index` data.
	 * @param \SP_Post $sp_post Post being indexed, as an SP_Post.
	 * @return array Updated data to index.
	 */
	public function add_search_suggest_to_indexed_post_data( $data, $sp_post ) {
		if ( $this->get_enable_search_suggestions() ) {
			$restrict = $this->get_restricted_search_suggestions_post_types();

			/* @phpstan-ignore-next-line */
			if ( ! $restrict || in_array( $sp_post->post_type, $restrict, true ) ) {
				/**
				 * Filters the text to be indexed in the search suggestions field for a post.
				 *
				 * @param string $text    Text to index. Default post title.
				 * @param int    $post_id Post ID.
				 */
				$data['search_suggest'] = apply_filters(
					'elasticsearch_extensions_post_search_suggestions_text',
					$sp_post->post_title, /* @phpstan-ignore-line */
					$sp_post->post_id, /* @phpstan-ignore-line */
				);
			}
		}

		return $data;
	}

	/**
	 * A callback for the sp_search_query_args filter. Adds aggregations to the
	 * request so that the response will include aggregation buckets, and
	 * filters by any aggregations set.
	 *
	 * @param array $es_args The request args to be filtered.
	 * @return array The filtered request args.
	 */
	public function add_aggs_to_es_query( $es_args ): array {
		// Add our aggregations.
		foreach ( $this->get_aggregations() as $aggregation ) {
			// Add aggregations to the request so buckets will be returned with results.
			$request = $aggregation->request();
			if ( ! empty( $request ) ) {
				$es_args['aggs'][ $aggregation->get_query_var() ] = $request;
			}

			// Add any set aggregations to the query so results are properly filtered.
			$filter = $aggregation->filter();
			if ( ! empty( $filter ) ) {
				if ( isset( $es_args['query']['bool'] ) ) {
					$es_args['query']['bool']['filter'] = array_merge( $es_args['query']['bool']['filter'] ?? [], $filter );
				} elseif ( isset( $es_args['query']['function_score']['query']['bool'] ) ) {
					$es_args['query']['function_score']['query']['bool']['filter'] = array_merge( $es_args['query']['function_score']['query']['bool']['filter'] ?? [], $filter );
				}
			}
		}

		return $es_args;
	}

	/**
	 * A callback for the sp_searchable_post_types filter hook. Filters the list
	 * of post types that should be searched in SearchPress based on a filter.
	 * If no restrictions were specified via the filter, uses the default list
	 * from SearchPress, which includes all indexed post types.
	 *
	 * @param array $post_types An associative array of post type slugs.
	 * @return array The modified list of post types to include in searches.
	 */
	public function apply_searchable_post_types( $post_types ) {
		return $this->get_searchable_post_types( $post_types );
	}

	/**
	 * A callback for the sp_post_allowed_meta filter hook.
	 * Filters the list of post meta fields that should be indexed in
	 * SearchPress based on what was configured.
	 *
	 * @todo Ideally, a site could easily configure which fields to index,
	 *       but that's not presently compatible with other adapters.
	 *
	 * @param array $post_meta A list of meta keys.
	 * @return array The modified list of meta fields to index.
	 */
	public function apply_allowed_meta( $post_meta ) {
		$keys = $this->get_restricted_post_meta();
		if ( ! empty( $keys ) ) {
			foreach ( $keys as $key ) {
				$post_meta[ $key ] = [
					'value',
					'boolean',
					'long',
					'double',
					'date',
					'datetime',
					'time',
				];
			}
		}

		return $post_meta;
	}

	/**
	 * A callback for the sp_post_pre_index filter hook.
	 * Filters the list of taxonomies that should be indexed in SearchPress
	 * based on what was configured. If no restrictions were specified, uses the
	 * default list (all public taxonomies).
	 *
	 * @todo This should probably not impact _indexing_, but rather, _searching_.
	 *
	 * @param array $post_data Post data to be indexed.
	 * @return array The modified post data to index.
	 */
	public function apply_allowed_taxonomies( $post_data ) {
		$taxonomies = $this->get_restricted_taxonomies();
		if ( ! empty( $taxonomies ) ) {
			$map = array_flip( $taxonomies );
			foreach ( $post_data['terms'] as $tax => $terms ) {
				if ( ! isset( $map[ $tax ] ) ) {
					unset( $post_data['terms'][ $tax ] );
				}
			}
		}

		return $post_data;
	}

	/**
	 * A callback for the wp_rest_search_handlers filter. Includes the
	 * search-suggestions REST API handler in the list of allowed handlers if
	 * search suggestions are made available over REST.
	 *
	 * @param \WP_REST_Search_Handler[] $search_handlers List of search handlers to use in the controller.
	 * @return \WP_REST_Search_Handler[] Updated list of handlers.
	 */
	public function filter__wp_rest_search_handlers( $search_handlers ) {
		if ( $this->is_show_search_suggestions_in_rest_enabled() ) {
			$search_handlers[] = new Post_Suggestion_Search_Handler( $this );
		}

		return $search_handlers;
	}

	/**
	 * Query Elasticsearch directly.
	 *
	 * @param array $es_args       Elasticsearch query arguments.
	 * @param array $wp_query_args Optional. An array of query arguments.
	 * @return array|object Elasticsearch response. Defaults to array.
	 */
	public function query_es( array $es_args, array $wp_query_args = [] ): array|object {
		return SP_API()->search( wp_json_encode( $es_args ), [ 'output' => ARRAY_A ] );
	}

	/**
	 * Suggest posts that match the given search term.
	 *
	 * @param string $search Search string.
	 * @param array  $args   {
	 *     Optional. An array of query arguments.
	 *
	 *     @type string[] $subtypes Limit suggestions to this subset of all post
	 *                              types that support search suggestions.
	 *     @type int      $page     Page of results.
	 *     @type int      $per_page Results per page. Default 10.
	 *     @type int[]    $include  Search within these post IDs.
	 *     @type int[]    $exclude  Exclude these post IDs from results.
	 *     @type string[] $status   Post statuses to search. Default 'publish'.
	 * }
	 * @return int[] Post IDs in this page of results and total number of results.
	 */
	public function query_post_suggestions( string $search, array $args = [] ): array {
		$out = [ [], 0 ];

		if ( ! $search && ! $this->get_allow_empty_search() ) {
			return $out;
		}

		$args = wp_parse_args(
			$args,
			[
				'subtypes' => [],
				'page'     => 1,
				'per_page' => 10,
				'exclude'  => [], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'include'  => [],
				'status'   => [ 'publish' ],
			],
		);

		/**
		 * Filters the arguments used to build an Elasticsearch search for suggested posts.
		 *
		 * @param array  $args   An array of query arguments.
		 * @param string $search Search string.
		 */
		$args = apply_filters( 'elasticsearch_extensions_query_post_suggestions_args', $args, $search );

		$subtypes = (array) $args['subtypes'];
		$page     = (int) $args['page'];
		$per_page = (int) $args['per_page'];
		$exclude  = (array) $args['exclude'];
		$include  = (array) $args['include'];

		// Avoid returning stale data in the index.
		$subtypes = array_filter( $subtypes, 'post_type_exists' );

		if ( $search ) {
			$query_from     = ( $page - 1 ) * $per_page;
			$query_per_page = $per_page;
			$query_must     = [
				[
					'multi_match' => [
						'query'    => $search,
						'type'     => 'bool_prefix',
						'fields'   => [
							'search_suggest',
							'search_suggest._2gram',
							'search_suggest._3gram',
						],

						/*
						 * The purpose of using 'and' here is to assume that more terms in the search indicate
						 * someone searching for something more specific, not someone trying to get more results.
						 */
						'operator' => 'and',
						'_name'    => 'search',
					],
				],
				[
					'terms' => [
						'post_status' => (array) $args['status'],
						'_name'       => 'status',
					],
				],
			];
			$query_must_not = [];

			if ( $subtypes ) {
				$restrict = $this->get_restricted_search_suggestions_post_types();

				/*
				 * If search suggestions have been limited to a list of post types,
				 * don't suggest posts from any other types, even if posts in other
				 * types have suggestions indexed because they used to be allowed.
				 */
				if ( $restrict ) {
					$subtypes = array_intersect( $subtypes, $restrict );

					if ( ! $subtypes ) {
						$subtypes = $restrict;
					}
				}

				$query_must[] = [
					'terms' => [
						'post_type.raw' => $subtypes,
						'_name'         => 'subtypes',
					],
				];
			}

			if ( $include ) {
				$query_must[] = [
					'terms' => [
						'post_id' => $include,
						'_name'   => 'include',
					],
				];
			}

			if ( $exclude ) {
				$query_must_not[] = [
					'terms' => [
						'post_id' => $exclude,
						'_name'   => 'exclude',
					],
				];
			}

			$query = [
				'from'    => $query_from,
				'size'    => $query_per_page,
				'query'   => [
					'bool' => [
						'must'     => $query_must,
						'must_not' => $query_must_not,
					],
				],
				'_source' => [
					'post_id',
				],
			];

			$search = new \SP_Search( $query );
			$out    = [
				$search->pluck_field( 'post_id' ),
				$search->get_results( 'total' ),
			];
		}

		return $out;
	}

	/**
	 * Get the field map for the adapter.
	 *
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

	/**
	 * Registers action and/or filter hooks with WordPress.
	 */
	public function hook(): void {
		// Register filter hooks.
		add_filter( 'sp_pre_search_results', [ $this, 'extract_aggs_from_results' ], 10, 2 );
		add_filter( 'sp_config_sync_post_types', [ $this, 'apply_sync_post_types' ] );
		add_filter( 'sp_config_sync_statuses', [ $this, 'apply_sync_post_statuses' ] );
		add_filter( 'sp_config_mapping', [ $this, 'add_search_suggest_to_mapping' ] );
		add_filter( 'sp_post_pre_index', [ $this, 'add_search_suggest_to_indexed_post_data' ], 10, 2 );
		add_filter( 'sp_search_query_args', [ $this, 'add_aggs_to_es_query' ] );
		add_filter( 'sp_search_query_args', [ $this, 'add_phrase_matching_to_es_args' ] );
		add_filter( 'sp_searchable_post_types', [ $this, 'apply_searchable_post_types' ] );
		add_filter( 'sp_post_allowed_meta', [ $this, 'apply_allowed_meta' ] );
		add_filter( 'sp_post_pre_index', [ $this, 'apply_allowed_taxonomies' ] );
		add_filter( 'wp_rest_search_handlers', [ $this, 'filter__wp_rest_search_handlers' ] );
	}

	/**
	 * Unregisters action and/or filter hooks that were registered in the hook
	 * method.
	 */
	public function unhook(): void {
		// Unregister filter hooks.
		remove_filter( 'sp_pre_search_results', [ $this, 'extract_aggs_from_results' ] );
		remove_filter( 'sp_config_sync_post_types', [ $this, 'apply_sync_post_types' ] );
		remove_filter( 'sp_config_sync_statuses', [ $this, 'apply_sync_post_statuses' ] );
		remove_filter( 'sp_config_mapping', [ $this, 'add_search_suggest_to_mapping' ] );
		remove_filter( 'sp_post_pre_index', [ $this, 'add_search_suggest_to_indexed_post_data' ] );
		remove_filter( 'sp_search_query_args', [ $this, 'add_aggs_to_es_query' ] );
		remove_filter( 'sp_search_query_args', [ $this, 'add_phrase_matching_to_es_args' ] );
		remove_filter( 'sp_searchable_post_types', [ $this, 'apply_searchable_post_types' ] );
		remove_filter( 'sp_post_allowed_meta', [ $this, 'apply_allowed_meta' ] );
		remove_filter( 'sp_post_pre_index', [ $this, 'apply_allowed_taxonomies' ] );
		remove_filter( 'wp_rest_search_handlers', [ $this, 'filter__wp_rest_search_handlers' ] );
	}
}
