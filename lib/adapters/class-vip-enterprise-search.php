<?php
/**
 * Elasticsearch Extensions Adapters: VIP Enterprise Search Adapter
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\REST_API\Post_Suggestion_Search_Handler;
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
	 * Add aggs to formatted ES query args.
	 *
	 * @param array $formatted_args The formatted ES args.
	 * @return array
	 */
	public function add_aggs_to_es_query( $formatted_args ): array {
		/**
		 * ElasticPress uses post_filter to filter results after search, but
		 * post_filter only applies to search hits, not aggregations. In order
		 * to apply the filter to aggregations as well, we need to ensure that
		 * there is a query with a filter clause, even if there is no search
		 * term.
		 *
		 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/filter-search-results.html
		 */
		if ( ! empty( $formatted_args['post_filter']['bool']['must'] ) ) {
			if ( empty( $formatted_args['query'] ) ) {
				$formatted_args['query']['bool']['filter'] = $formatted_args['post_filter']['bool']['must'];
			} elseif ( ! empty( $formatted_args['query']['function_score']['query']['bool'] ) ) {
				$formatted_args['query']['function_score']['query']['bool']['filter'] = array_merge(
					$formatted_args['query']['function_score']['query']['bool']['filter'] ?? [],
					$formatted_args['post_filter']['bool']['must']
				);
			} elseif ( ! empty( $formatted_args['query']['match_all'] ) ) {
				// This condition triggers on secondary queries that use ep_integrate.
				// Adding filters to match_all queries requires converting it to a bool query with the match_all clause nested within.
				$formatted_args['query']['bool']['must']['match_all'] = $formatted_args['query']['match_all'];
				// Unset the original DSL, since this will use a bool query instead.
				unset( $formatted_args['query']['match_all'] );
				// Add bool query with filters.
				$formatted_args['query']['bool']['filter'] = $formatted_args['post_filter']['bool']['must'];
			}
		}

		// Add requested aggregations.
		$use_filter = ! empty( $formatted_args['query']['bool']['filter'] );
		foreach ( $this->get_aggregations() as $aggregation ) {
			$filter = $aggregation->filter();
			if ( ! empty( $filter ) ) {
				if ( $use_filter ) {
					// If we aren't using a search term, just use a basic query filter.
					$formatted_args['query']['bool']['filter'] = array_merge(
						$formatted_args['query']['bool']['filter'],
						$filter
					);
				} elseif ( ! empty( $formatted_args['query']['function_score']['query']['bool'] ) ) {
					$formatted_args['query']['function_score']['query']['bool']['filter'] = array_merge(
						$formatted_args['query']['function_score']['query']['bool']['filter'] ?? [],
						$filter
					);
				}
			}
		}

		// If we are searching for a keyword and applying filters, ensure both are required.
		if (
			! empty( $formatted_args['query']['function_score']['query']['bool']['filter'] )
			&& ! empty( $formatted_args['query']['function_score']['query']['bool']['should'] )
			&& ! isset( $formatted_args['query']['function_score']['query']['bool']['minimum_should_match'] )
		) {
			$formatted_args['query']['function_score']['query']['bool']['minimum_should_match'] = 1;
		}

		return $formatted_args;
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
	 * A callback for the ep_indexable_post_types filter hook. Filters the list
	 * of post types that should be indexed in ElasticPress based on what was
	 * configured. If no restrictions were specified, uses the default list
	 * (all public post types).
	 *
	 * @param array $post_types An associative array of post type slugs.
	 *
	 * @return array The modified list of post types to index.
	 */
	public function filter__ep_indexable_post_types( $post_types ) {

		// Determine whether we should filter the list or not.
		$restricted_post_types = $this->get_restricted_post_types();
		if ( empty( $restricted_post_types ) ) {
			return $post_types;
		}

		// Rebuild the list of post types using the allowlist.
		$filtered_post_types = [];
		foreach ( $restricted_post_types as $post_type ) {
			$filtered_post_types[ $post_type ] = $post_type;
		}

		return $filtered_post_types;
	}

	/**
	 * A callback for the ep_indexable_post_status filter hook. Filters the list
	 * of post statuses that should be indexed in ElasticPress based on what was
	 * configured. If no restrictions were specified, uses the default list.
	 *
	 * @param array $post_statuses Indexabled post statuses.
	 * @return string[] The modified list of post statuses to index.
	 */
	public function filter__ep_indexable_post_statuses( $post_statuses ): array {

		// Determine whether we should filter the list or not.
		$restricted_post_statuses = $this->get_restricted_post_statuses();

		if ( empty( $restricted_post_statuses ) ) {
			return $post_statuses;
		}

		return array_unique( array_merge( $post_statuses, $restricted_post_statuses ) );
	}

	/**
	 * A callback for the ep_post_mapping filter. Adds the 'search_suggest'
	 * field to the mapping if search suggestions are enabled.
	 *
	 * @param array $mapping Post mapping.
	 * @return array Updated mapping.
	 */
	public function filter__ep_post_mapping( $mapping ) {
		if ( $this->get_enable_search_suggestions() ) {
			$mapping['mappings']['properties']['search_suggest'] = [
				'type'     => 'search_as_you_type',

				/*
				 * The 'ewp_word_delimiter' analyzer included by default in ElasticPress is not compatible with
				 * this field type. See https://github.com/10up/ElasticPress/pull/3237.
				 */
				'analyzer' => 'standard',
			];
		}

		return $mapping;
	}

	/**
	 * A callback for the ep_post_sync_args_post_prepare_meta filter. Indexes
	 * text for the 'search_suggest' field.
	 *
	 * @param array $post_args Post data to be indexed.
	 * @param int   $post_id   Post ID.
	 * @return array Updated data to index.
	 */
	public function filter__ep_post_sync_args_post_prepare_meta( $post_args, $post_id ) {
		if ( $this->get_enable_search_suggestions() ) {
			$post = get_post( $post_id );

			if ( $post ) {
				$restrict = $this->get_restricted_search_suggestions_post_types();

				if ( ! $restrict || in_array( $post->post_type, $restrict, true ) ) {
					/**
					 * Filters the text to be indexed in the search suggestions field for a post.
					 *
					 * @param string $text    Text to index. Default post title.
					 * @param int    $post_id Post ID.
					 */
					$post_args['search_suggest'] = apply_filters(
						'elasticsearch_extensions_post_search_suggestions_text',
						$post->post_title,
						$post_id,
					);
				}
			}
		}

		return $post_args;
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
		// Phrase matching. This only adds phrase matching if the feature is enabled.
		$formatted_args = $this->add_phrase_matching_to_es_args( $formatted_args );

		// Add aggs to query.
		$formatted_args = $this->add_aggs_to_es_query( $formatted_args );

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
		foreach ( $this->get_aggregations() as $aggregation ) {
			$request = $aggregation->request();
			if ( ! empty( $request ) ) {
				$dsl['aggs'][ $aggregation->get_query_var() ] = $request;
			}
		}

		// Re-encode the body into the request args.
		$request_args['body'] = wp_json_encode( $dsl );

		return $request_args;
	}

	/**
	 * A callback for the ep_searchable_post_types filter hook. Filters the list
	 * of post types that should be searched in ElasticPress based on a filter.
	 * If no restrictions were specified via the filter, uses the default list
	 * from ElasticPress, which includes all indexed post types.
	 *
	 * @param array $post_types An associative array of post type slugs.
	 *
	 * @return array The modified list of post types to include in searches.
	 */
	public function filter__ep_searchable_post_types( $post_types ) {
		return $this->get_searchable_post_types( $post_types );
	}

	/**
	 * A callback for the vip_search_post_meta_allow_list filter hook.
	 * Filters the list of post meta fields that should be indexed in
	 * ElasticPress based on what was configured.
	 *
	 * @param array $post_meta A list of meta keys.
	 *
	 * @return array The modified list of meta fields to index.
	 */
	public function filter__vip_search_post_meta_allow_list( $post_meta ) {
		return $this->get_restricted_post_meta() ?: $post_meta;
	}

	/**
	 * A callback for the vip_search_post_taxonomies_allow_list filter hook.
	 * Filters the list of taxonomies that should be indexed in ElasticPress
	 * based on what was configured. If no restrictions were specified, uses the
	 * default list (all public taxonomies).
	 *
	 * @param array $taxonomies A list of taxonomy slugs.
	 *
	 * @return array The modified list of taxonomies to index.
	 */
	public function filter__vip_search_post_taxonomies_allow_list( $taxonomies ) {
		return $this->get_restricted_taxonomies() ?: $taxonomies;
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
					'ID',
				],
			];

			$result = \ElasticPress\Indexables::factory()->get( 'post' )->query_es( $query, [] );

			if (
				isset( $result['documents'] )
				&& is_array( $result['documents'] )
				&& isset( $result['found_documents']['value'] )
			) {
				$out = [
					array_column( $result['documents'], 'ID' ),
					$result['found_documents']['value'],
				];
			}
		}

		return $out;
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
	 * Gets the post index name for the current site.
	 *
	 * @param string $slug Indexable slug. Defaults to 'post'.
	 * @return string The index name.
	 */
	private function get_site_index( string $slug = 'post' ): string {
		$post_indexable = \ElasticPress\Indexables::factory()->get( $slug );
		$index_name = $post_indexable->get_index_name( get_current_blog_id() );
		return ! empty( $index_name ) ? $index_name : '';
	}

	/**
	 * Registers action and/or filter hooks with WordPress.
	 */
	public function hook(): void {
		// Register action hooks.
		add_action( 'ep_valid_response', [ $this, 'action__ep_valid_response' ] );

		// Register filter hooks.
		add_filter( 'ep_elasticpress_enabled', [ $this, 'filter__ep_elasticpress_enabled' ], 10, 2 );
		add_filter( 'ep_indexable_post_status', [ $this, 'filter__ep_indexable_post_statuses' ] );
		add_filter( 'ep_indexable_post_types', [ $this, 'filter__ep_indexable_post_types' ] );
		add_filter( 'ep_post_mapping', [ $this, 'filter__ep_post_mapping' ] );
		add_filter( 'ep_post_sync_args_post_prepare_meta', [ $this, 'filter__ep_post_sync_args_post_prepare_meta' ], 10, 2 );
		add_filter( 'ep_post_formatted_args', [ $this, 'filter__ep_post_formatted_args' ], 10, 2 );
		add_filter( 'ep_query_request_args', [ $this, 'filter__ep_query_request_args' ] );
		add_filter( 'ep_searchable_post_types', [ $this, 'filter__ep_searchable_post_types' ] );
		add_filter( 'vip_search_post_meta_allow_list', [ $this, 'filter__vip_search_post_meta_allow_list' ] );
		add_filter( 'vip_search_post_taxonomies_allow_list', [ $this, 'filter__vip_search_post_taxonomies_allow_list' ] );
		add_filter( 'wp_rest_search_handlers', [ $this, 'filter__wp_rest_search_handlers' ] );
	}

	/**
	 * Query Elasticsearch directly.
	 *
	 * @param array $es_args       Formatted es query arguments.
	 * @return array|object The raw Elasticsearch response.
	 *
	 * @see \Elasticsearch_Extensions\Adapters\Adapter::query_es()
	 */
	public function search( array $es_args ): array|object {
		// Get Elasticsearch instance from EP.
		$elasticsearch = \ElasticPress\Elasticsearch::factory();
		$type          = 'post';
		$index         = $this->get_site_index( $type );

		// Execute the query.
		$response = \ElasticPress\Elasticsearch::factory()->query( $index, $type, $es_args, [] );
		return ! empty( $response ) ? $response : [];
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
		remove_filter( 'ep_indexable_post_status', [ $this, 'filter__ep_indexable_post_statuses' ] );
		remove_filter( 'ep_indexable_post_types', [ $this, 'filter__ep_indexable_post_types' ] );
		remove_filter( 'ep_post_mapping', [ $this, 'filter__ep_post_mapping' ] );
		remove_filter( 'ep_post_sync_args_post_prepare_meta', [ $this, 'filter__ep_post_sync_args_post_prepare_meta' ] );
		remove_filter( 'ep_post_formatted_args', [ $this, 'filter__ep_post_formatted_args' ] );
		remove_filter( 'ep_query_request_args', [ $this, 'filter__ep_query_request_args' ] );
		remove_filter( 'ep_searchable_post_types', [ $this, 'filter__ep_searchable_post_types' ] );
		remove_filter( 'vip_search_post_meta_allow_list', [ $this, 'filter__vip_search_post_meta_allow_list' ] );
		remove_filter( 'vip_search_post_taxonomies_allow_list', [ $this, 'filter__vip_search_post_taxonomies_allow_list' ] );
		remove_filter( 'wp_rest_search_handlers', [ $this, 'filter__wp_rest_search_handlers' ] );
	}
}
