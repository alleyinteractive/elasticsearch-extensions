<?php
/**
 * Elasticsearch Extensions: Controller
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

use Elasticsearch_Extensions\Adapters\Adapter;

/**
 * The controller class, which is responsible for loading adapters and
 * configuration.
 *
 * @package Elasticsearch_Extensions
 */
class Controller {

	/**
	 * The active adapter.
	 *
	 * @var Adapter
	 */
	private Adapter $adapter;

	/**
	 * Holds a reference to the singleton instance.
	 *
	 * @var Controller
	 */
	private static Controller $instance;

	/**
	 * Enables an aggregation based on post type.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_post_type_aggregation(): Controller {
		if ( isset( $this->adapter ) ) {
			$this->adapter->enable_post_type_aggregation();
		}

		return $this;
	}

	/**
	 * A function to enable an aggregation for a specific taxonomy.
	 *
	 * @param string $taxonomy The taxonomy slug for which to enable an aggregation.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_taxonomy_aggregation( string $taxonomy ): Controller {
		if ( isset( $this->adapter ) ) {
			$this->adapter->enable_taxonomy_aggregation( $taxonomy );
		}

		return $this;
	}

	/**
	 * Get an instance of the class.
	 *
	 * @return Controller
	 */
	public static function instance(): Controller {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Loads an instance of an Adapter into the controller.
	 *
	 * @param Adapter $adapter The adapter to load.
	 */
	public function load_adapter( Adapter $adapter ): void {
		$this->adapter = $adapter;
	}
}
