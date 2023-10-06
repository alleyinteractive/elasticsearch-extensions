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
	private function add_aggregation( Aggregation $aggregation ): void {
		$this->aggregations[ $aggregation->get_query_var() ] = $aggregation;
	}

	/**
	 * Adds a new Co-Authors Plus author aggregation to the list of active aggregations.
	 *
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_cap_author_aggregation( array $args = [] ): void {
		$this->add_aggregation( new CAP_Author( $this->dsl, $args ) );
	}

	/**
	 * Adds a new custom date range aggregation to the list of active aggregations.
	 *
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_custom_date_range_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Custom_Date_Range( $this->dsl, $args ) );
	}

	/**
	 * Adds a new post date aggregation to the list of active aggregations.
	 *
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_post_date_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Post_Date( $this->dsl, $args ) );
	}

	/**
	 * Adds a new post meta aggregation to the list of active aggregations.
	 *
	 * @param string $meta_key The meta key to aggregate on.
	 * @param array  $args     Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_post_meta_aggregation( string $meta_key, array $args = [] ): void {
		$this->add_aggregation( new Post_Meta( $this->dsl, wp_parse_args( $args, [ 'meta_key' => $meta_key ] ) ) );
	}

	/**
	 * Adds a new post type aggregation to the list of active aggregations.
	 *
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_post_type_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Post_Type( $this->dsl, $args ) );
	}

	/**
	 * Adds a new relative date aggregation to the list of active aggregations.
	 *
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_relative_date_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Relative_Date( $this->dsl, $args ) );
	}

	/**
	 * Adds a new taxonomy aggregation to the list of active aggregations.
	 *
	 * @param string $taxonomy The taxonomy slug to add (e.g., category, post_tag).
	 * @param array  $args     Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_taxonomy_aggregation( string $taxonomy, array $args = [] ): void {
		$this->add_aggregation( new Taxonomy( $this->dsl, wp_parse_args( $args, [ 'taxonomy' => $taxonomy ] ) ) );
	}

	/**
	 * Adds a new generic term aggregation to the list of active aggregations.
	 *
	 * @param string $term_field The term field to aggregate on.
	 * @param string $query_var  The query var to use for this aggregation for filters on the front-end.
	 * @param array  $args       Arguments to pass to the adapter's aggregation configuration.
	 */
	public function add_term_aggregation( string $term_field, string $query_var, array $args = [] ): void {
		$this->add_aggregation(
			new Term(
				$this->dsl,
				wp_parse_args(
					$args,
					[
						'query_var'  => $query_var,
						'term_field' => $this->dsl->map_field( $term_field ),
					]
				)
			)
		);
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
	 * @return array The field map.
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
	 * @param array $post_types The default list of post type slugs from the adapter.
	 *
	 * @return array An array of searchable post type slugs.
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
	 * @param array $aggregations Aggregations from the Elasticsearch response.
	 */
	protected function parse_aggregations( array $aggregations ): void {
		foreach ( $aggregations as $aggregation_key => $aggregation ) {
			if ( isset( $this->aggregations[ $aggregation_key ] ) ) {
				$this->aggregations[ $aggregation_key ]->parse_buckets( $aggregation['buckets'] ?? [] );
			}
		}
	}

	/**
	 * Suggest posts that match the given search term.
	 *
	 * @param string $search Search string.
	 * @param array  $args   {
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
