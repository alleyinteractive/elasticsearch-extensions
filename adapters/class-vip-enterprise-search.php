<?php
/**
 * Elasticsearch Extensions Adapters: VIP Enterprise Search Adapter
 *
 * @package Elasticsearch_Extensions
 */

use Elasticsearch_Extensions\Adapters\Adapter;

/**
 * An adapter for WordPress VIP Enterprise Search.
 *
 * @package Elasticsearch_Extensions
 */
class Elasticsearch_Extensions extends Adapter {

	/**
	 * Enables an aggregation based on post type.
	 */
	public static function enable_post_type_aggregation(): void {
		// TODO.
	}

	/**
	 * A function to enable an aggregation for a specific taxonomy.
	 *
	 * @param string $taxonomy The taxonomy slug for which to enable an aggregation.
	 */
	public static function enable_taxonomy_aggregation( string $taxonomy ): void {
		// TODO.
	}
}
