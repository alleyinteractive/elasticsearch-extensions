<?php
/**
 * Elasticsearch Extensions: Controller
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

use Elasticsearch_Extensions\Adapters\Adapter;
use Elasticsearch_Extensions\Aggregations\Aggregation;
use Elasticsearch_Extensions\Exceptions\Invalid_Feature_Exception;
use Elasticsearch_Extensions\Exceptions\Unsupported_Feature_Exception;
use Elasticsearch_Extensions\Interfaces\Adapterable;
use Elasticsearch_Extensions\Interfaces\Featurable;
use Elasticsearch_Extensions\Interfaces\Hookable;

/**
 * The controller class, which is responsible for loading adapters and
 * configuration.
 *
 * @package Elasticsearch_Extensions
 * @method Controller disable_empty_search() Disable empty search query strings.
 * @method Controller enable_cap_author_aggregation( array $args = [] ) Enables an aggregation for Co-Authors Plus authors.
 * @method Controller enable_custom_date_range_aggregation( array $args = [] ) Enables a custom date range aggregation.
 * @method Controller enable_empty_search() Enable empty search query strings.
 * @method Controller enable_post_date_aggregation( array $args = [] ) Enables an aggregation based on post dates.
 * @method Controller enable_post_type_aggregation( array $args = [] ) Enables an aggregation based on post type.
 * @method Controller enable_relative_date_aggregation( array $args = [] ) Enables an aggregation based on relative dates.
 * @method Controller enable_search_suggestions( array $args = [] ) Enables search-as-you-type suggestions.
 * @method Controller enable_taxonomy_aggregation( string $taxonomy, array $args = [] ) A function to enable an aggregation for a specific taxonomy.
 *
 */
class Controller implements Hookable {

	/**
	 * The active adapter.
	 *
	 * @var Adapter
	 */
	private Adapterable $adapter;

	/**
	 * An array of features.
	 *
	 * @var Featurable[]
	 */
	private array $features = [];

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
	 * Magic method which fires an action to enable or disable a feature if the adapter supports it.
	 *
	 * @throws Invalid_Feature_Exception     If the feature doesn't exist.
	 * @throws Unsupported_Feature_Exception If the current adapter doesn't support the feature.
	 *
	 * @param string $name The name of the method being called.
	 * @param array $arguments The arguments passed to the method.
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function __call( string $name, array $arguments ): Controller {
		// If the name starts with "enable_" or "disable_", check if the adapter is capable of the feature.
		if ( preg_match( '/^(enable|disable)_(.*)$/', $name, $matches ) ) {
			$enabling = 'enable' === $matches[1];

			if ( str_ends_with( $matches[2], '_aggregation' ) ) {
				$feature_name = 'Aggregations';
			} else {
				// Titlecase the feature, e.g. empty_search -> Empty_Search.
				$feature_name = mb_convert_case( $matches[2], MB_CASE_TITLE );
			}

			$feature_class = "\Elasticsearch_Extensions\Features\{$feature_name}";

			// Ensure the feature is valid.
			if ( ! class_exists( $feature_class ) ) {
				throw new Invalid_Feature_Exception();
			}

			// Ensure the adapter supports the feature.
			if ( ! $this->adapter_supports( $feature_class ) ) {
				throw new Unsupported_Feature_Exception();
			}

			// Instantiate the feature if it doesn't exist.
			if ( ! isset( $this->features[ $feature_class ] ) ) {
				$this->features[ $feature_class ] = new $feature_class( $arguments );
			}
			$feature = $this->features[ $feature_class ];

			// Normalize the arguments.
			$arguments = $arguments[0] ?? [];

			// Activate or deactivate the feature.
			if ( $enabling ) {
				$feature->activate( $arguments );
			} else {
				$feature->deactivate( $arguments );
			}

			/**
			 * Fire an action to enable or disable a feature.
			 *
			 * @param array      $arguments  The arguments passed to the method.
			 * @param Featurable $feature    The feature being enabled or disabled.
			 * @param Controller $controller This controller instance.
			 */
			do_action( "elasticsearch_extensions_{$name}", $arguments, $feature, $this );

			return $this;
		}

		throw new \BadMethodCallException( "Call to undefined method {$name}" );
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
	 * Restricts indexable meta to the provided list.
	 *
	 * @param string[] $post_meta The array of meta fields to restrict to.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function restrict_post_meta( array $post_meta ): Controller {
		$this->adapter->restrict_post_meta( $post_meta );

		return $this;
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
		remove_action( 'init', [ $this, 'action__init' ], 1000 );
	}

	/**
	 * Check if the adapter supports a given feature.
	 *
	 * @param string $feature The feature (classname) to check.
	 * @return bool True if the adapter supports the feature, false otherwise.
	 */
	public function adapter_supports( string $feature ): bool {
		return in_array( $feature, $this->adapter->supports(), true );
	}
}
