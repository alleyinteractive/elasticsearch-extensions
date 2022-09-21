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
use Elasticsearch_Extensions\Aggregations\Post_Type;
use Elasticsearch_Extensions\Aggregations\Relative_Date;
use Elasticsearch_Extensions\Aggregations\Taxonomy;
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
	 * Holds an instance of the DSL class with the field map from this adapter
	 * injected into it.
	 *
	 * @var DSL
	 */
	protected DSL $dsl;

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
	 * Returns a list of searchable post types. Defaults to all indexed post
	 * types by returning an empty array. Allows for applying different logic
	 * depending on the context (e.g., main search vs. custom search
	 * interfaces).
	 *
	 * @param array $post_types The default list of post type slugs from the adapter.
	 *
	 * @return array An array of searchable post types, or an empty array for all post types.
	 */
	protected function get_searchable_post_types( array $post_types ): array {
		/**
		 * Filters the list of searchable post types.
		 *
		 * Defaults to an empty array, which indicates that all indexed post
		 * types should be searched.
		 *
		 * @since 0.1.0
		 *
		 * @param array $post_types The array of post type slugs to include in search, or an empty array to include all.
		 */
		return apply_filters( 'elasticsearch_extensions_searchable_post_types', $post_types );
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
	 * Sets the value for allow_empty_search.
	 *
	 * @param bool $allow_empty_search Whether to allow empty search or not.
	 */
	public function set_allow_empty_search( bool $allow_empty_search ): void {
		$this->allow_empty_search = $allow_empty_search;
	}
}
