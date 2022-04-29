<?php
/**
 * Elasticsearch Extensions: Controller
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

use Elasticsearch_Extensions\Adapters\Adapter;
use Elasticsearch_Extensions\Adapters\VIP_Enterprise_Search;
use Elasticsearch_Extensions\Aggregations\Aggregation;

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
	 * Disable empty search query strings.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function disable_empty_search(): Controller {
		if ( isset( $this->adapter ) ) {
			$this->adapter->set_allow_empty_search( false );
		}
		return $this;
	}

	/**
	 * Enable empty search query strings.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_empty_search(): Controller {
		if ( isset( $this->adapter ) ) {
			$this->adapter->set_allow_empty_search( true );
		}
		return $this;
	}

	/**
	 * Enables an aggregation based on post dates.
	 *
	 * @param array $args Arguments to pass to the adapter's aggregation configuration.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_post_date_aggregation( array $args = [] ): Controller {
		if ( isset( $this->adapter ) ) {
			$this->adapter->add_post_date_aggregation( $args );
		}

		return $this;
	}

	/**
	 * Enables an aggregation based on post type.
	 *
	 * @param array $args Arguments to pass to the adapter's aggregation configuration.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_post_type_aggregation( array $args = [] ): Controller {
		if ( isset( $this->adapter ) ) {
			$this->adapter->add_post_type_aggregation( $args );
		}

		return $this;
	}

	/**
	 * A function to enable an aggregation for a specific taxonomy.
	 *
	 * @param string $taxonomy The taxonomy slug for which to enable an aggregation.
	 * @param array  $args     Arguments to pass to the adapter's aggregation configuration.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_taxonomy_aggregation( string $taxonomy, array $args = [] ): Controller {
		if ( isset( $this->adapter ) ) {
			$this->adapter->add_taxonomy_aggregation( $taxonomy, $args );
		}

		return $this;
	}

	/**
	 * Get a specific aggregation from the adapter by its label.
	 *
	 * @param string $label Label for the aggregation.
	 *
	 * @return Aggregation|null
	 */
	public function get_aggregation_by_label( string $label = '' ): ?Aggregation {
		return isset( $this->adapter )
			? $this->adapter->get_aggregation_by( 'label', $label )
			: null;
	}

	/**
	 * Get a specific aggregation from the adapter by its query var.
	 *
	 * @param string $query_var Query variable.
	 *
	 * @return Aggregation|null
	 */
	public function get_aggregation_by_query_var( string $query_var = '' ): ?Aggregation {
		return isset( $this->adapter )
			? $this->adapter->get_aggregation_by( 'query_var', $query_var )
			: null;
	}

	/**
	 * Get all aggregations from the adapter.
	 *
	 * @return array An array of aggregation data grouped by aggregation type.
	 */
	public function get_aggregations(): array {
		return isset( $this->adapter )
			? $this->adapter->get_aggregations()
			: [];
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
	 * Dynamically loads an instance of an Adapter based on environment settings.
	 */
	public function load_adapter(): void {
		if ( defined( 'VIP_ENABLE_VIP_SEARCH' ) && VIP_ENABLE_VIP_SEARCH ) {
			$this->adapter = VIP_Enterprise_Search::instance();
		}
	}
}
