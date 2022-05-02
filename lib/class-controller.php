<?php
/**
 * Elasticsearch Extensions: Controller
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

use Elasticsearch_Extensions\Adapters\Adapter;
use Elasticsearch_Extensions\Aggregations\Aggregation;
use Elasticsearch_Extensions\Interfaces\Hookable;

/**
 * The controller class, which is responsible for loading adapters and
 * configuration.
 *
 * @package Elasticsearch_Extensions
 */
class Controller implements Hookable {

	/**
	 * The active adapter.
	 *
	 * @var Adapter
	 */
	private Adapter $adapter;

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
		$this->adapter->set_allow_empty_search( false );

		return $this;
	}

	/**
	 * Enable support for Co-Authors Plus. Registers a taxonomy handler for
	 * the "author" taxonomy and adds some special logic to the author
	 * taxonomy aggregation to get the display names for the authors.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_cap_support(): Controller {
		$this->adapter->add_cap_author_aggregation();

		return $this;
	}

	/**
	 * Enable empty search query strings.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_empty_search(): Controller {
		$this->adapter->set_allow_empty_search( true );

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
		$this->adapter->add_post_date_aggregation( $args );

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
		$this->adapter->add_post_type_aggregation( $args );

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
		$this->adapter->add_taxonomy_aggregation( $taxonomy, $args );

		return $this;
	}

	/**
	 * Get a specific aggregation from the adapter by its label.
	 *
	 * @param string $label Label for the aggregation.
	 *
	 * @return ?Aggregation The matching aggregation, or null on failure.
	 */
	public function get_aggregation_by_label( string $label = '' ): ?Aggregation {
		return $this->adapter->get_aggregation_by_label( $label );
	}

	/**
	 * Get a specific aggregation from the adapter by its query var.
	 *
	 * @param string $query_var Query variable.
	 *
	 * @return ?Aggregation The matching aggregation, or null on failure.
	 */
	public function get_aggregation_by_query_var( string $query_var = '' ): ?Aggregation {
		return $this->adapter->get_aggregation_by_query_var( $query_var );
	}

	/**
	 * Get all aggregations from the adapter.
	 *
	 * @return array An array of aggregation data grouped by aggregation type.
	 */
	public function get_aggregations(): array {
		return $this->adapter->get_aggregations();
	}

	/**
	 * Registers action and/or filter hooks with WordPress.
	 */
	public function hook(): void {
		add_action( 'init', [ $this, 'action__init' ], 1000 );
	}

	/**
	 * Loads an adapter, either using the given adapter, or dynamically based
	 * on environment settings.
	 *
	 * @param ?Adapter $adapter Optional. The adapter to load. Defaults to dynamic load.
	 */
	public function load_adapter( ?Adapter $adapter = null ): void {
		if ( ! is_null( $adapter ) ) {
			$this->adapter = $adapter;
		} elseif ( defined( 'VIP_ENABLE_VIP_SEARCH' ) && VIP_ENABLE_VIP_SEARCH ) {
			$this->adapter = Factory::vip_enterprise_search_adapter();
		} else {
			$this->adapter = Factory::generic_adapter();
		}
	}

	/**
	 * Restricts searchable post types to the provided list.
	 *
	 * @param string[] $post_types The array of post types to restrict search to.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function restrict_post_types( array $post_types ): Controller {
		$this->adapter->restrict_post_types( $post_types );

		return $this;
	}

	/**
	 * Restricts searchable taxonomies to the provided list.
	 *
	 * @param string[] $taxonomies The array of taxonomies to restrict search to.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function restrict_taxonomies( array $taxonomies ): Controller {
		$this->adapter->restrict_taxonomies( $taxonomies );

		return $this;
	}

	/**
	 * Unregisters action and/or filter hooks with WordPress.
	 */
	public function unhook(): void {
		remove_action( 'init', [ $this, 'action__init' ], 99 );
	}
}
