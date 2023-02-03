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
		/**
		 * An action hook that fires after this plugin is initialized and is
		 * ready for configuration.
		 *
		 * @param Controller $controller The Elasticsearch Extensions controller class.
		 */
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
	 * Enables an aggregation for Co-Authors Plus authors.
	 *
	 * @param array $args Arguments to pass to the adapter's aggregation configuration.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_cap_author_aggregation( array $args = [] ): Controller {
		$this->adapter->add_cap_author_aggregation( $args );

		return $this;
	}

	/**
	 * Enables a custom date range aggregation.
	 *
	 * @param array $args Arguments to pass to the adapter's aggregation configuration.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_custom_date_range_aggregation( array $args = [] ): Controller {
		$this->adapter->add_custom_date_range_aggregation( $args );

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
	 * Enables an aggregation based on relative dates.
	 *
	 * @param array $args Arguments to pass to the adapter's aggregation configuration.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_relative_date_aggregation( array $args = [] ): Controller {
		$this->adapter->add_relative_date_aggregation( $args );

		return $this;
	}

	/**
	 * Enables search-as-you-type suggestions.
	 *
	 * @param array $args {
	 *     Optional. An array of arguments.
	 *
	 *     @type string[] $post_types   Limit suggestions to this subset of all
	 *                                  indexed post types.
	 *     @type bool     $show_in_rest Whether to register REST API search handlers
	 *                                  for querying suggestions. Default true.
	 * }
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_search_suggestions( array $args = [] ): Controller {
		$args = wp_parse_args(
			$args,
			[
				'post_types'   => [],
				'show_in_rest' => true,
			]
		);

		$args['post_types'] = array_filter( (array) $args['post_types'] );

		$this->adapter->set_enable_search_suggestions( true );
		$this->adapter->set_show_search_suggestions_in_rest( (bool) $args['show_in_rest'] );
		$this->adapter->restrict_search_suggestions_post_types( $args['post_types'] );

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
	 * Restricts indexable post types to the provided list.
	 *
	 * @param string[] $post_types The array of post types to restrict to.
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
