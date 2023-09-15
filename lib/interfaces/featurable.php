<?php
/**
 * Featurable interface file
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Interfaces;

/**
 * Interface for features that can be activated or deactivated.
 */
interface Featurable {
	/**
	 * Activate the feature.
	 *
	 * @param array $args Optional. Additional arguments passed to the feature.
	 */
	public function activate( array $args = [] ): void;

	/**
	 * Deactivate the feature.
	 *
	 * @param array $args Optional. Additional arguments passed to the feature.
	 */
	public function deactivate( array $args = [] ): void;

	/**
	 * Returns whether the feature is active.
	 *
	 * @return bool
	 */
	public function is_active(): bool;
}
