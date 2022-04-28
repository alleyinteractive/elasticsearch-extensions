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
			$this->adapter->add_aggregation_config(
				wp_parse_args(
					$args,
					[
						'count'             => 1000,
						'calendar_interval' => 'year',
						'label'             => __( 'Date', 'elasticsearch-extensions' ),
						'type'              => 'post_date',
					]
				)
			);
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
			$this->adapter->add_aggregation_config(
				wp_parse_args(
					$args,
					[
						'count' => 1000,
						'label' => __( 'Content Type', 'elasticsearch-extensions' ),
						'type'  => 'post_type',
					]
				)
			);
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
			$this->adapter->add_aggregation_config(
				wp_parse_args(
					$args,
					[
						'count'    => 1000,
						// TODO: Get taxonomy label based on slug.
						'label'    => $taxonomy,
						'taxonomy' => $taxonomy,
						'type'     => 'taxonomy',
					]
				)
			);
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
	public function get_aggregation_by_name( string $label = '' ) {
		return $this->adapter->get_aggregation_by( 'label', $label );
	}

	/**
	 * Get a specific aggregation from the adapter by its query var.
	 *
	 * @param string $query_var Query variable.
	 *
	 * @return Aggregation|null
	 */
	public function get_aggregation_by_query_var( string $query_var = '' ) {
		return $this->adapter->get_aggregation_by( 'query_var', $query_var );
	}

	/**
	 * Get all aggregations from the adapter.
	 *
	 * @return array
	 */
	public function get_aggregations(): array {
		return $this->adapter->get_aggregations();
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

	/**
	 * Map a given field to the Elasticsearch index.
	 *
	 * @param string $field The field to map.
	 *
	 * @return string The mapped field.
	 */
	public function map_field( $field ) {
		return $this->adapter->map_field( $field );
	}

	/**
	 * Map a meta field. This will swap in the data type.
	 *
	 * @param string $meta_key Meta key to map.
	 * @param string $type Data type to map.
	 *
	 * @return string The mapped field.
	 */
	public function map_meta_field( string $meta_key, string $type = '' ): string {
		return $this->adapter->map_meta_field( $meta_key, $type );
	}

	/**
	 * Map a taxonomy field. This will swap in the taxonomy name.
	 *
	 * @param string $taxonomy Taxonomy to map.
	 * @param string $field Field to map.
	 *
	 * @return string The mapped field.
	 */
	public function map_tax_field( string $taxonomy, string $field ): string {
		return $this->adapter->map_tax_field( $taxonomy, $field );
	}
}
