<?php
/**
 * Elasticsearch Extensions Adapters: Adapter Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\Aggregation;
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
	 * @var array
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
	 * Holds a reference to the singleton instance.
	 *
	 * @var Adapter
	 */
	private static Adapter $instance;

	/**
	 * Get an aggregation by a field key and value.
	 *
	 * @param string $field Field key.
	 * @param string $value Field value.
	 *
	 * @return Aggregation|null
	 */
	public function get_aggregation_by( string $field = '', string $value = '' ) {
		foreach ( $this->aggregations as $aggregation ) {
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
	public function get_aggregation_config(): array {
		return $this->aggregation_config;
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
	 * of this function when initializing the DSL class in the setup method.
	 *
	 * @return array The field map.
	 */
	abstract protected function get_field_map(): array;

	/**
	 * Sets the value for allow_empty_search.
	 *
	 * @param bool $allow_empty_search Whether to allow empty search or not.
	 */
	public function set_allow_empty_search( bool $allow_empty_search ): void {
		$this->allow_empty_search = $allow_empty_search;
	}

	/**
	 * Sets up the singleton by registering action and filter hooks and loading
	 * the DSL class with the field map.
	 */
	abstract public function setup(): void;

	// TODO: Refactor line.

	/**
	 * Gets aggregation results.
	 *
	 * @return array The aggregation results.
	 */
	public function get_aggregations(): array {
		return $this->aggregations;
	}

	/**
	 * Get an instance of the class.
	 *
	 * @return Adapter
	 */
	public static function instance(): Adapter {
		$class_name = get_called_class();
		if ( ! isset( self::$instance ) ) {
			self::$instance = new $class_name();
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Pull the facets out of the ES response.
	 * Filters `ep_valid_response`.
	 *
	 * @see \ElasticPress\Elasticsearch
	 */
	public function parse_facets() {
		$this->facets = apply_filters( 'elasticsearch_extensions_parse_facets', [] );
		if ( empty( $this->facets ) ) {
			if ( ! empty( $this->results['aggregations'] ) ) {
				foreach ( $this->results['aggregations'] as $label => $buckets ) {
					if ( empty( $buckets['buckets'] ) ) {
						continue;
					}
					$this->facets[ $label ] = new Facet( $label, $buckets['buckets'], $this->facets_config[ $label ] );
				}
			}
		}
	}

	/**
	 * Sets aggregation results.
	 *
	 * @param array $aggregations An array of aggregation results to be stored.
	 */
	protected function set_aggregations( array $aggregations ): void {
		$this->aggregations = $aggregations;
	}
}
