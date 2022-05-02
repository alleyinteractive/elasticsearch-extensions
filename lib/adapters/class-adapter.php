<?php
/**
 * Elasticsearch Extensions Adapters: Adapter Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\Aggregations\Aggregation;
use Elasticsearch_Extensions\Aggregations\Post_Date;
use Elasticsearch_Extensions\Aggregations\Post_Type;
use Elasticsearch_Extensions\Aggregations\Taxonomy;
use Elasticsearch_Extensions\DSL;

/**
 * An abstract class that establishes base functionality and sets requirements
 * for implementing classes.
 *
 * @package Elasticsearch_Extensions
 */
abstract class Adapter {

	/**
	 * Stores aggregation data from the Elasticsearch response.
	 *
	 * @var Aggregation[]
	 */
	protected array $aggregations = [];

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
	 * Adds a new taxonomy aggregation to the list of active aggregations.
	 *
	 * @param string $taxonomy The taxonomy slug to add (e.g., category, post_tag).
	 * @param array  $args     Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_taxonomy_aggregation( string $taxonomy, array $args = [] ): void {
		$this->add_aggregation( new Taxonomy( $this->dsl, wp_parse_args( $args, [ 'taxonomy' => $taxonomy ] ) ) );
	}

	/**
	 * Get an aggregation by a field key and value.
	 *
	 * @param string $field Field key.
	 * @param string $value Field value.
	 *
	 * @return Aggregation|null
	 */
	public function get_aggregation_by( string $field = '', string $value = '' ): ?Aggregation {
		foreach ( $this->aggregations as $aggregation ) {
			/** Type hinting. @var Aggregation $aggregation */
			if ( isset( $aggregation->$field ) && $value === $aggregation->$field ) {
				return $aggregation;
			}
		}

		return null;
	}

	/**
	 * Get the aggregation configuration.
	 *
	 * @return array
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
	 * Sets the value for allow_empty_search.
	 *
	 * @param bool $allow_empty_search Whether to allow empty search or not.
	 */
	public function set_allow_empty_search( bool $allow_empty_search ): void {
		$this->allow_empty_search = $allow_empty_search;
	}
}
