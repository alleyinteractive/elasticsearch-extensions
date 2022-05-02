<?php
/**
 * Elasticsearch Extensions: Controller
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

use Elasticsearch_Extensions\Adapters\Adapter;
use Elasticsearch_Extensions\Adapters\Generic;
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
	 * Constructor. Dynamically loads an Adapter based on environment settings.
	 */
	public function __construct() {
		if ( defined( 'VIP_ENABLE_VIP_SEARCH' ) && VIP_ENABLE_VIP_SEARCH ) {
			$this->adapter = new VIP_Enterprise_Search();
		} else {
			$this->adapter = new Generic();
		}

		// Add action hooks.
		add_action( 'init', [ $this, 'action__init' ], 99 );
	}

	/**
	 * A callback for the init action hook. Invokes a custom hook for this
	 * plugin to make it easier to configure within other themes and plugins.
	 * Runs at a later priority to ensure that other actions that are run as
	 * part of init (especially taxonomy registration) are complete before this
	 * action runs, since it depends on registrations being done already.
	 */
	public function action__init(): void {
		do_action( 'elasticsearch_extensions_config', $this );
	}

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
			? $this->adapter->get_aggregation_by_label( $label )
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
			? $this->adapter->get_aggregation_by_query_var( $query_var )
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
}
