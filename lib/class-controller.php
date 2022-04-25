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
	 * Get facet data from adapter.
	 *
	 * @return array
	 */
	public function get_facet_config(): array {
		return $this->adapter->get_facet_config();
	}

	/**
	 * Get facet data from adapter.
	 *
	 * @return array
	 */
	public function get_facet_data(): array {
		return $this->adapter->get_facet_data();
	}

	/**
	 * Get facet data by from adapter.
	 *
	 * @return Facet|null
	 */
	public function get_facet_data_by_name( $name = '' ) {
		return $this->adapter->get_facet_data_by( 'name', $name );
	}

	/**
	 * Get facet data by from adapter.
	 *
	 * @return Facet|null
	 */
	public function get_facet_data_by_query_var( $query_var = '' ) {
		return $this->adapter->get_facet_data_by( 'query_var', $query_var );
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
	 * @param  string $field The field to map.
	 * @return string The mapped field.
	 */
	public function map_field( $field ) {
		return $this->adapter->map_field( $field );
	}

	/**
	 * Map a meta field. This will swap in the data type.
	 *
	 * @param  string $meta_key Meta key to map.
	 * @param  string $type Data type to map.
	 * @return string The mapped field.
	 */
	public function map_meta_field( string $meta_key, string $type = '' ): string {
		return $this->adapter->map_meta_field( $meta_key, $type );
	}

	/**
	 * Map a taxonomy field. This will swap in the taxonomy name.
	 *
	 * @param  string $taxonomy Taxonomy to map.
	 * @param  string $field Field to map.
	 * @return string The mapped field.
	 */
	public function map_tax_field( string $taxonomy, string $field ): string  {
		return $this->adapter->map_tax_field( $taxonomy, $field );
	}

	/**
	 * Configures facets in ES Extensions.
	 * Necessary to set up faceting.
	 *
	 * TODO Build out the array options in the DocBloc.
	 */
	public function set_facets_config( $facets_config ) {
		$this->adapter->set_facets_config( $facets_config );
	}
}
