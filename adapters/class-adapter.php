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
	 * Stores aggregation data from the Elasticsearch response.
	 *
	 * @var array
	 */
	private static array $aggregations = [];

	/**
	 * Enables an aggregation based on post type.
	 */
	abstract public static function enable_post_type_aggregation(): void;

	/**
	 * A function to enable an aggregation for a specific taxonomy.
	 *
	 * @param string $taxonomy The taxonomy slug for which to enable an aggregation.
	 */
	abstract public static function enable_taxonomy_aggregation( string $taxonomy ): void;

	/**
	 * Gets aggregation results.
	 *
	 * @return array The aggregation results.
	 */
	public static function get_aggregations(): array {
		return self::$aggregations;
	}

	/**
	 * Sets aggregation results.
	 *
	 * @param array $aggregations An array of aggregation results to be stored.
	 */
	protected static function set_aggregations( array $aggregations ): void {
		self::$aggregations = $aggregations;
	}
}
