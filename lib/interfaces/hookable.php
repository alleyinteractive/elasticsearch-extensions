<?php
/**
 * Elasticsearch Extensions Interfaces: Hookable
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Interfaces;

/**
 * An interface for classes that need to register and unregister hooks for
 * integrating with WordPress.
 *
 * @package Elasticsearch_Extensions
 */
interface Hookable {

	/**
	 * Registers action and/or filter hooks with WordPress.
	 */
	public function hook(): void;

	/**
	 * Unregisters action and/or filter hooks that were registered in the hook
	 * method.
	 */
	public function unhook(): void;
}
