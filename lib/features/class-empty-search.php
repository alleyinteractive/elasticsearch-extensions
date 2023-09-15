<?php
/**
 * Empty_Search class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Features;

use Elasticsearch_Extensions\Interfaces\Featurable;

/**
 * Feature to allow empty searches.
 */
class Empty_Search implements Featurable {

	/**
	 * Whether to allow empty searches (no keyword set).
	 *
	 * @var bool
	 */
	private bool $allow_empty_search = false;

	/**
	 * @inheritDoc
	 */
	public function activate(): void {
		// TODO: Implement activate() method.
	}

	/**
	 * @inheritDoc
	 */
	public function deactivate(): void {
		// TODO: Implement deactivate() method.
	}

	/**
	 * @inheritDoc
	 */
	public function is_active(): bool {
		// TODO: Implement is_active() method.
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
	 * Sets the value for allow_empty_search.
	 *
	 * @param bool $allow_empty_search Whether to allow empty search or not.
	 */
	public function set_allow_empty_search( bool $allow_empty_search ): void {
		$this->allow_empty_search = $allow_empty_search;
	}
}
