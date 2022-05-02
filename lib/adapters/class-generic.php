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
	 * @return array The field map.
	 */
	public function get_field_map(): array {
		return [];
	}
}
