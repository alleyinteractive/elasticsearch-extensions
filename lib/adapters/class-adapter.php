<?php
/**
 * Elasticsearch Extensions Adapters: Adapter Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

/**
 * An abstract class that establishes base functionality and sets requirements
 * for implementing classes.
 *
 * @package Elasticsearch_Extensions
 */
abstract class Adapter {

	/**
	 * Whether post type aggregations are active or not.
	 *
	 * @var bool
	 */
	private bool $aggregate_post_types = false;

	/**
	 * Stores an array of taxonomy slugs that should be added to aggregations.
	 *
	 * @var array
	 */
	private array $aggregate_taxonomies = [];

	/**
	 * Stores aggregation data from the Elasticsearch response.
	 *
	 * @var array
	 */
	private array $aggregations = [];

	/**
	 * Holds a reference to the singleton instance.
	 *
	 * @var Adapter
	 */
	private static Adapter $instance;

	/**
	 * Enables an aggregation based on post type.
	 *
	 * @return Adapter The instance of the class to allow for chaining.
	 */
	public function enable_post_type_aggregation(): Adapter {
		$this->aggregate_post_types = true;

		return $this;
	}

	/**
	 * A function to enable an aggregation for a specific taxonomy.
	 *
	 * @param string $taxonomy The taxonomy slug for which to enable an aggregation.
	 *
	 * @return Adapter The instance of the class to allow for chaining.
	 */
	public function enable_taxonomy_aggregation( string $taxonomy ): Adapter {
		$this->aggregate_taxonomies[] = $taxonomy;

		return $this;
	}

	/**
	 * Gets the flag for whether post types should be aggregated or not.
	 *
	 * @return bool Whether post types should be aggregated or not.
	 */
	protected function get_aggregate_post_types(): bool {
		return $this->aggregate_post_types;
	}

	/**
	 * Gets the list of taxonomies to be aggregated.
	 *
	 * @return array The list of taxonomy slugs to be aggregated.
	 */
	protected function get_aggregate_taxonomies(): array {
		return $this->aggregate_taxonomies;
	}

	/**
	 * Gets aggregation results.
	 *
	 * @return array The aggregation results.
	 */
	protected function get_aggregations(): array {
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
	 * Sets aggregation results.
	 *
	 * @param array $aggregations An array of aggregation results to be stored.
	 *
	 * @return Adapter The instance of the class to allow for chaining.
	 */
	protected function set_aggregations( array $aggregations ): Adapter {
		$this->aggregations = $aggregations;

		return $this;
	}

	/**
	 * Sets up the singleton by registering action and filter hooks.
	 */
	abstract public function setup(): void;
}
