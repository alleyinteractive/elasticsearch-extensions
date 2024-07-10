<?php
/**
 * Elasticsearch Extensions Adapters: Adapter Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\Aggregations\Aggregation;
use Elasticsearch_Extensions\Aggregations\CAP_Author;
use Elasticsearch_Extensions\Aggregations\Custom_Date_Range;
use Elasticsearch_Extensions\Aggregations\Post_Date;
use Elasticsearch_Extensions\Aggregations\Post_Meta;
use Elasticsearch_Extensions\Aggregations\Post_Type;
use Elasticsearch_Extensions\Aggregations\Relative_Date;
use Elasticsearch_Extensions\Aggregations\Taxonomy;
use Elasticsearch_Extensions\Aggregations\Term;
use Elasticsearch_Extensions\DSL;
use Elasticsearch_Extensions\Interfaces\Hookable;

/**
 * An abstract class that establishes base functionality and sets requirements
 * for implementing classes.
 *
 * @package Elasticsearch_Extensions
 */
abstract class Adapter implements Hookable {

	/**
	 * Stores aggregation data from the Elasticsearch response.
	 *
	 * @var Aggregation[]
	 */
	private array $aggregations = [];

	/**
	 * Whether to allow empty searches (no keyword set).
	 *
	 * @var bool
	 */
	private bool $allow_empty_search = false;

	/**
	 * Whether to enable phrase matching in queries.
	 *
	 * Under the hood, this uses a multi_match query with the type set to
	 * "phrase" for each phrase matched part of the search string. This
	 * allows for more precise matching of phrases in the search string. For
	 * example, when active, a search for "foo bar" will match "foo bar" but not "foo
	 * baz bar".
	 *
	 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
	 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase.html
	 *
	 * @var bool
	 */
	private bool $enable_phrase_matching = false;

	/**
	 * Whether to index search suggestions.
	 *
	 * @var bool
	 */
	private bool $enable_search_suggestions = false;

	/**
	 * Whether to make search suggestions available over the REST API.
	 *
	 * @var bool
	 */
	private bool $show_search_suggestions_in_rest = true;

	/**
	 * Holds an instance of the DSL class with the field map from this adapter
	 * injected into it.
	 *
	 * @var DSL
	 */
	protected DSL $dsl;

	/**
	 * An optional array of meta fields to restrict search to.
	 *
	 * @var string[]
	 */
	private array $restricted_post_meta = [];

	/**
	 * An optional array of post types to restrict search to.
	 *
	 * @var string[]
	 */
	private array $restricted_post_types = [];

	/**
	 * An optional array of indexable post statuses to restrict to.
	 *
	 * @var string[]
	 */
	private array $restricted_post_statuses = [];

	/**
	 * An optional array of taxonomies to restrict search to.
	 *
	 * @var string[]
	 */
	private array $restricted_taxonomies = [];

	/**
	 * An optional array of post types to restrict search suggestions to.
	 *
	 * @var string[]
	 */
	private array $restricted_search_suggestions_post_types = [];

	/**
	 * Constructor. Sets up the DSL object with this adapter's field map.
	 */
	public function __construct() {
		$this->dsl = new DSL( $this->get_field_map() );
	}

	/**
	 * Adds an Aggregation to the list of active aggregations.
	 *
	 * @param Aggregation $aggregation The aggregation to add.
	 */
	public function add_aggregation( Aggregation $aggregation ): void {
		$this->aggregations[ $aggregation->get_query_var() ] = $aggregation;
	}

	/**
	 * Adds a new Co-Authors Plus author aggregation to the list of active aggregations.
	 *
	 * @param array{label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'display_name'|'first_name'|'key'|'label'|'last_name', query_var?: string, relation?: 'AND'|'OR', term_field?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $label      Optional. The human-readable name for this aggregation. Defaults to 'Author'.
	 *     @type string $order      Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                              Defaults to 'DESC'.
	 *     @type string $orderby    Optional. The field to order results by. Valid options are 'count', 'display_name',
	 *                              'first_name', 'key', 'label', 'last_name'. Defaults to 'count'.
	 *     @type string $query_var  Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                              'taxonomy_author'.
	 *     @type string $relation   Optional. The logical relationship between each selected author when there is more
	 *                              than one. Valid options are 'AND', 'OR'. Defaults to 'AND'.
	 *     @type string $term_field Optional. The term field to use in the DSL for this aggregation. Defaults to the
	 *                              value of the taxonomy name for the 'author' taxonomy, as looked up in the DSL map.
	 * }
	 */
	public function add_cap_author_aggregation( array $args = [] ): void {
		$this->add_aggregation( new CAP_Author( $this->dsl, $args ) );
	}

	/**
	 * Adds a new custom date range aggregation to the list of active aggregations.
	 *
	 * @param array{label?: string, query_var?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $label     Optional. The human-readable name for this aggregation. Defaults to 'Custom Date
	 *                             Range'.
	 *     @type string $query_var Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                             'custom_date_range'.
	 * }
	 */
	public function add_custom_date_range_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Custom_Date_Range( $this->dsl, $args ) );
	}

	/**
	 * A callback to filter es args.
	 * Adds phrase matching to the request args if it is enabled.
	 *
	 * @param array $es_args The request args to be filtered.
	 * @return array The filtered request args.
	 */
	public function add_phrase_matching_to_es_args( array $es_args ): array {
		if ( ! $this->get_enable_phrase_matching() ) {
			return $es_args;
		}

		// Get search query from query vars.
		$search = get_query_var( 's' );

		// Bail early if this isn't a search.
		if ( empty( $search ) ) {
			return $es_args;
		}

		// Break down the search string into the desired "phrase matched" parts.
		$phrase_match_delineator = '"';
		if ( ! preg_match_all( '/' . $phrase_match_delineator . '(.*?)' . $phrase_match_delineator . '/', $search, $matches ) ) {
			return $es_args;
		}

		// Get the search query without the "phrase matched" parts.
		$unmatched = implode(
			' ',
			array_filter(
				explode( ' ', preg_replace( '/' . $phrase_match_delineator . '.*?' . $phrase_match_delineator . '/', '', $search ) )
			)
		);

		// Replace the main multi_match query with the "unmatched" string.
		$es_arg = $this->find_multimatch_query( $es_args );
		if ( ! empty( $es_arg['multi_match']['query'] ) ) {
			if ( ! empty( $unmatched ) ) {
				$es_arg['multi_match']['query'] = $unmatched;
			} else {
				unset( $es_arg );
			}
		}

		/**
		 * Filters the list of multimatch fields.
		 *
		 * @since 0.1.0
		 *
		 * @param array $default_multi_match_fields The array of multimatch fields with weighting to be included in phrase matching queries.
		 */
		$default_multi_match_fields = apply_filters(
			'elasticsearch_extensions_phrase_match_multimatch_fields',
			[
				'post_title^3',
				'post_excerpt^2',
				'post_content',
				'post_author.display_name',
				'terms.author.name',
			]
		);

		// Loop over phrase matches and add each.
		foreach ( $matches[1] as $query ) {
			$es_args['query']['function_score']['query']['bool']['must'][] = [
				'multi_match' => [
					'fields' => $default_multi_match_fields,
					'query'  => $query,
					'type'   => 'phrase',
				],
			];
		}

		return $es_args;
	}

	/**
	 * Adds a new post date aggregation to the list of active aggregations.
	 *
	 * @param array{interval?: 'year'|'quarter'|'month'|'week'|'day'|'hour'|'minute', label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', query_var?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $interval  Optional. The unit of time to aggregate results by. Valid options are 'year',
	 *                             'quarter', 'month', 'week', 'day', 'hour', 'minute'. Defaults to 'year'.
	 *     @type string $label     Optional. The human-readable name for this aggregation. Defaults to 'Date'.
	 *     @type string $order     Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                             Defaults to 'DESC'.
	 *     @type string $orderby   Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                             Defaults to 'count'.
	 *     @type string $query_var Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                             'post_date'.
	 * }
	 */
	public function add_post_date_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Post_Date( $this->dsl, $args ) );
	}

	/**
	 * Adds a new post meta aggregation to the list of active aggregations.
	 *
	 * @param string $meta_key The meta key to aggregate on.
	 * @param array{data_type?: string, label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', query_var?: string, relation?: 'AND'|'OR', term_field?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $data_type  Optional. The data type of the meta key, if the meta key is indexed using multiple
	 *                              data types (e.g., 'long'). Defaults to empty and uses the "raw" postmeta value.
	 *     @type string $label      Optional. The human-readable name for this aggregation. Defaults to a halfhearted
	 *                              attempt at turning the meta key into a title case string.
	 *     @type string $order      Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                              Defaults to 'DESC'.
	 *     @type string $orderby    Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                              Defaults to 'count'.
	 *     @type string $query_var  Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                              'post_meta_%s' where %s is the meta key.
	 *     @type string $relation   Optional. The logical relationship between each selected meta value when there is
	 *                              more than one. Valid options are 'AND', 'OR'. Defaults to 'AND'.
	 *     @type string $term_field Optional. The term field to use in the DSL for this aggregation. Defaults to the
	 *                              value of the post meta key, as looked up in the DSL map.
	 * }
	 */
	public function add_post_meta_aggregation( string $meta_key, array $args = [] ): void {
		$this->add_aggregation( new Post_Meta( $this->dsl, wp_parse_args( $args, [ 'meta_key' => $meta_key ] ) ) );
	}

	/**
	 * Adds a new post type aggregation to the list of active aggregations.
	 *
	 * @param array{label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', query_var?: string, relation?: 'AND'|'OR', term_field?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $label      Optional. The human-readable name for this aggregation. Defaults to 'Content Type'.
	 *     @type string $order      Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                              Defaults to 'DESC'.
	 *     @type string $orderby    Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                              Defaults to 'count'.
	 *     @type string $query_var  Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                              'post_type'.
	 *     @type string $relation   Optional. The logical relationship between each selected author when there is more
	 *                              than one. Valid options are 'AND', 'OR'. Defaults to 'AND'.
	 *     @type string $term_field Optional. The term field to use in the DSL for this aggregation. Defaults to the
	 *                              'post_type' field, as looked up in the DSL map.
	 * }
	 */
	public function add_post_type_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Post_Type( $this->dsl, $args ) );
	}

	/**
	 * Adds a new relative date aggregation to the list of active aggregations.
	 *
	 * @param array{intervals?: int[], label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', query_var?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type int[]  $intervals Optional. The number of days prior to the current date to include in each bucket.
	 *                             Accepts an array of integers. Defaults to `[7, 30, 90]`.
	 *     @type string $label     Optional. The human-readable name for this aggregation. Defaults to 'Relative Date'.
	 *     @type string $order     Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                             Defaults to 'DESC'.
	 *     @type string $orderby   Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                             Defaults to 'count'.
	 *     @type string $query_var Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                             'relative_date'.
	 * }
	 */
	public function add_relative_date_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Relative_Date( $this->dsl, $args ) );
	}

	/**
	 * Adds a new taxonomy aggregation to the list of active aggregations.
	 *
	 * @param string $taxonomy The taxonomy slug for which to enable an aggregation.
	 * @param array{label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', query_var?: string, relation?: 'AND'|'OR', term_field?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $label      Optional. The human-readable name for this aggregation. Defaults to the singular
	 *                              name of the taxonomy (e.g., 'Category').
	 *     @type string $order      Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                              Defaults to 'DESC'.
	 *     @type string $orderby    Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                              Defaults to 'count'.
	 *     @type string $query_var  Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                              'taxonomy_%s' where %s is the taxonomy slug.
	 *     @type string $relation   Optional. The logical relationship between each term when there is more than one.
	 *                              Valid options are 'AND', 'OR'. Defaults to 'AND'.
	 *     @type string $term_field Optional. The term field to use in the DSL for this aggregation. Defaults to the
	 *                              taxonomy's slug field, as looked up in the DSL map.
	 * }
	 */
	public function add_taxonomy_aggregation( string $taxonomy, array $args = [] ): void {
		$this->add_aggregation( new Taxonomy( $this->dsl, wp_parse_args( $args, [ 'taxonomy' => $taxonomy ] ) ) );
	}

	/**
	 * Adds a new generic term aggregation to the list of active aggregations.
	 *
	 * @param string $label The human-readable label for this aggregation.
	 * @param string $term_field The term field to aggregate on.
	 * @param string $query_var The query var to use for this aggregation for filters on the front-end.
	 * @param array{label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', relation?: 'AND'|'OR'} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $order      Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                              Defaults to 'DESC'.
	 *     @type string $orderby    Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                              Defaults to 'count'.
	 *     @type string $relation   Optional. The logical relationship between each term when there is more than one.
	 *                              Valid options are 'AND', 'OR'. Defaults to 'AND'.
	 * }
	 */
	public function add_term_aggregation( string $label, string $term_field, string $query_var, array $args = [] ): void {
		$this->add_aggregation(
			new Term(
				$this->dsl,
				wp_parse_args(
					$args,
					[
						'label'      => $label,
						'query_var'  => $query_var,
						'term_field' => $this->dsl->map_field( $term_field ),
					]
				)
			)
		);
	}

	/**
	 * Finds the first multi_match query and returns a reference to it.
	 *
	 * @param array $es_args Elasticsearch DSL to filter.
	 *
	 * @return ?array A reference to the multi_match array on success, null on failure.
	 */
	protected function find_multimatch_query( array $es_args ): ?array {

		// Skip if this is the wrong type of query.
		if (
			empty( $es_args['query']['function_score']['query']['bool']['must'] )
			|| ! is_array( $es_args['query']['function_score']['query']['bool']['must'] )
		) {
			return null;
		}

		// Loop over the query arguments to try to find multi_match queries.
		foreach ( $es_args['query']['function_score']['query']['bool']['must'] as &$es_arg ) {
			// If this is a multi_match query, return a reference to it.
			if ( ! empty( $es_arg['multi_match'] ) ) {
				return $es_arg;
			}
		}

		return null;
	}

	/**
	 * Get an aggregation by its label.
	 *
	 * @param string $label Aggregation label.
	 *
	 * @return ?Aggregation The aggregation, if found, or null if not.
	 */
	public function get_aggregation_by_label( string $label ): ?Aggregation {
		foreach ( $this->aggregations as $aggregation ) {
			if ( $label === $aggregation->get_label() ) {
				return $aggregation;
			}
		}

		return null;
	}

	/**
	 * Get an aggregation by its query var.
	 *
	 * @param string $query_var Aggregation query var.
	 *
	 * @return ?Aggregation The aggregation, if found, or null if not.
	 */
	public function get_aggregation_by_query_var( string $query_var ): ?Aggregation {
		foreach ( $this->aggregations as $aggregation ) {
			if ( $query_var === $aggregation->get_query_var() ) {
				return $aggregation;
			}
		}

		return null;
	}

	/**
	 * Get the aggregation configuration.
	 *
	 * @return Aggregation[]
	 */
	public function get_aggregations(): array {
		return $this->aggregations;
	}

	/**
	 * Gets the value for allow_empty_search.
	 *
	 * @return bool Whether to allow empty search or not.
	 */
	public function get_allow_empty_search(): bool {
		return $this->allow_empty_search;
	}

	/**
	 * Gets the value for enable_phrase_matching.
	 *
	 * @return bool Whether phrase matching is enabled.
	 */
	public function get_enable_phrase_matching(): bool {
		return $this->enable_phrase_matching;
	}

	/**
	 * Gets the value for enable_search_suggestions.
	 *
	 * @return bool Whether search suggestions are enabled.
	 */
	public function get_enable_search_suggestions(): bool {
		return $this->enable_search_suggestions;
	}

	/**
	 * Returns a map of generic field names and types to the specific field
	 * path used in the mapping of the Elasticsearch plugin that is in use.
	 * Implementing classes need to provide this map, as it will be different
	 * between each plugin's Elasticsearch implementation, and use the result
	 * of this function when initializing the DSL class in the constructor.
	 *
	 * @return array<string, string> The field map.
	 */
	abstract protected function get_field_map(): array;

	/**
	 * Gets the list of restricted meta.
	 *
	 * @return string[] The list of restricted meta slugs.
	 */
	protected function get_restricted_post_meta(): array {
		return $this->restricted_post_meta;
	}

	/**
	 * Gets the list of restricted post types.
	 *
	 * @return string[] The list of restricted post type slugs.
	 */
	protected function get_restricted_post_types(): array {
		return $this->restricted_post_types;
	}

	/**
	 * Gets the list of restricted post statuses.
	 *
	 * @return string[] The list of restricted post statuses.
	 */
	protected function get_restricted_post_statuses(): array {
		return $this->restricted_post_statuses;
	}

	/**
	 * Gets the list of restricted taxonomies.
	 *
	 * @return string[] The list of restricted taxonomy slugs.
	 */
	protected function get_restricted_taxonomies(): array {
		return $this->restricted_taxonomies;
	}

	/**
	 * Gets the list of restricted post types for search suggestions.
	 *
	 * @return string[] The list of restricted post type slugs.
	 */
	protected function get_restricted_search_suggestions_post_types(): array {
		return $this->restricted_search_suggestions_post_types;
	}

	/**
	 * Returns a list of searchable post types. Defaults come from the filter
	 * function used in the adapter. Allows for applying different logic
	 * depending on the context (e.g., main search vs. custom search
	 * interfaces).
	 *
	 * @param string[] $post_types The default list of post type slugs from the adapter.
	 *
	 * @return string[] An array of searchable post type slugs.
	 */
	protected function get_searchable_post_types( array $post_types ): array {
		/**
		 * Filters the list of searchable post types.
		 *
		 * Defaults to the list of searchable post type slugs from the adapter.
		 *
		 * @since 0.1.0
		 *
		 * @param array $post_types The array of post type slugs to include in search.
		 */
		return apply_filters( 'elasticsearch_extensions_searchable_post_types', $post_types );
	}

	/**
	 * Returns whether support for a REST API search handler for suggestions is enabled.
	 *
	 * @return bool
	 */
	public function is_show_search_suggestions_in_rest_enabled(): bool {
		return $this->get_enable_search_suggestions() && $this->show_search_suggestions_in_rest;
	}

	/**
	 * Parses aggregations from an aggregations object in an Elasticsearch
	 * response into the loaded aggregations.
	 *
	 * @param array<string, mixed> $aggregations Aggregations from the Elasticsearch response.
	 */
	protected function parse_aggregations( array $aggregations ): void {
		foreach ( $aggregations as $aggregation_key => $aggregation ) {
			if ( isset( $this->aggregations[ $aggregation_key ] ) ) {
				$this->aggregations[ $aggregation_key ]->parse_buckets( $aggregation['buckets'] ?? [] );
			}
		}
	}

	/**
	 * Query Elasticsearch directly. Must be implemented in child classes for specific adapters.
	 *
	 * @param array $es_args Arguments to pass to the Elasticsearch server.
	 *
	 * @return array The response from the Elasticsearch server.
	 */
	abstract public function search( array $es_args ): array;

	/**
	 * Suggest posts that match the given search term.
	 *
	 * @param string $search Search string.
	 * @param array{subtypes?: string[], page?: int, per_page?: int, include?: int[], exclude?: int[]} $args   {
	 *     Optional. An array of arguments.
	 *
	 *     @type string[] $subtypes Limit suggestions to this subset of all post
	 *                              types that support search suggestions.
	 *     @type int      $page     Page of results.
	 *     @type int      $per_page Results per page. Default 10.
	 *     @type int[]    $include  Search within these post IDs.
	 *     @type int[]    $exclude  Exclude these post IDs from results.
	 * }
	 * @return int[] Post IDs in this page of results and total number of results.
	 */
	public function query_post_suggestions( string $search, array $args = [] ): array {
		return [ [], 0 ];
	}

	/**
	 * Restricts indexable post meta to the provided list.
	 *
	 * @param string[] $post_meta The array of meta fields to restrict to.
	 */
	public function restrict_post_meta( array $post_meta ): void {
		$this->restricted_post_meta = $post_meta;
	}

	/**
	 * Restricts indexable post types to the provided list.
	 *
	 * @param string[] $post_types The array of post types to restrict to.
	 */
	public function restrict_post_types( array $post_types ): void {
		$this->restricted_post_types = $post_types;
	}

	/**
	 * Restricts indexable post statuses to the provided list.
	 *
	 * @param string[] $post_statuses The array of indexabled post statuses to restrict to.
	 */
	public function restrict_post_statuses( array $post_statuses ): void {
		$this->restricted_post_statuses = $post_statuses;
	}

	/**
	 * Restricts searchable taxonomies to the provided list.
	 *
	 * @param string[] $taxonomies The array of taxonomies to restrict search to.
	 */
	public function restrict_taxonomies( array $taxonomies ): void {
		$this->restricted_taxonomies = $taxonomies;
	}

	/**
	 * Restricts post types indexed for search suggestions to the provided list.
	 *
	 * @param string[] $post_types The array of post types to restrict to.
	 */
	public function restrict_search_suggestions_post_types( array $post_types ): void {
		$this->restricted_search_suggestions_post_types = $post_types;
	}

	/**
	 * Sets the value for allow_empty_search.
	 *
	 * @param bool $allow_empty_search Whether to allow empty search or not.
	 */
	public function set_allow_empty_search( bool $allow_empty_search ): void {
		$this->allow_empty_search = $allow_empty_search;
	}

	/**
	 * Enables phrase matching for the main search query.
	 *
	 * @param bool $enable_phrase_matching Whether to enable phrase matching.
	 */
	public function set_enable_phrase_matching( bool $enable_phrase_matching ): void {
		$this->enable_phrase_matching = $enable_phrase_matching;
	}

	/**
	 * Sets the value for enable_search_suggestions.
	 *
	 * @param bool $enable_search_suggestions Whether to enable search suggestions.
	 */
	public function set_enable_search_suggestions( bool $enable_search_suggestions ): void {
		$this->enable_search_suggestions = $enable_search_suggestions;
	}

	/**
	 * Sets the value for show_search_suggestions_in_rest.
	 *
	 * @param bool $show_in_rest Whether to make search suggestions available over the REST API.
	 */
	public function set_show_search_suggestions_in_rest( bool $show_in_rest ): void {
		$this->show_search_suggestions_in_rest = $show_in_rest;
	}
}
