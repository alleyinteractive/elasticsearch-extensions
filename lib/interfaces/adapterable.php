<?php
/**
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Interfaces;

interface Adapterable {
	/**
	 * Returns a map of generic field names and types to the specific field
	 * path used in the mapping of the Elasticsearch plugin that is in use.
	 * Implementing classes need to provide this map, as it will be different
	 * between each plugin's Elasticsearch implementation, and use the result
	 * of this function when initializing the DSL class in the constructor.
	 *
	 * @return array The field map.
	 */
	public function get_field_map(): array;

	/**
	 * Declare the features the adapter supports.
	 *
	 * @return string[] The list of supported features.
	 */
	public function supports(): array;
}
