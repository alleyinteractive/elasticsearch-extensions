<?php
/**
 * Elasticsearch Extensions Adapters: Generic Adapter
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

/**
 * A generic adapter for when no other suitable adapter can be found.
 *
 * @package Elasticsearch_Extensions
 */
class Generic extends Adapter {
	/**
	 * Gets the field map for this adapter.
	 *
	 * @return array<string, string> The field map.
	 */
	public function get_field_map(): array {
		return [];
	}

	/**
	 * Registers action and/or filter hooks with WordPress.
	 */
	public function hook(): void {}

	/**
	 * Query Elasticsearch directly. Must be implemented in child classes for specific adapters.
	 *
	 * @param array $es_args Arguments to pass to the Elasticsearch server.
	 *
	 * @return array The response from the Elasticsearch server.
	 */
	public function search( array $es_args ) {
		return [];
	}

	/**
	 * Unregisters action and/or filter hooks that were registered in the hook
	 * method.
	 */
	public function unhook(): void {}
}
