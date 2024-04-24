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
	 * Disables phrase matching for search queries.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function disable_phrase_matching(): Controller {
		$this->adapter->set_enable_phrase_matching( false );

		return $this;
	}

	/**
	 * Enables an aggregation for Co-Authors Plus authors.
	 *
	 * @param array{label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'display_name'|'first_name'|'key'|'label'|'last_name', query_var?: string, relation?: 'AND'|'OR', term_field?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $label      Optional. The human-readable name for this aggregation. Defaults to 'Author'.
	 *     @type string $order      Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                              Defaults to 'DESC'.
	 *     @type string $orderby    Optional. The field to order results by. Valid options are 'count', 'display_name',
	 *                              'first_name', 'key', 'label', 'last_name'. Defaults to 'count'.
	 *     @type string $query_var  Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                              'taxonomy_author'.
	 *     @type string $relation   Optional. The logical relationship between each selected author when there is more
	 *                              than one. Valid options are 'AND', 'OR'. Defaults to 'AND'.
	 *     @type string $term_field Optional. The term field to use in the DSL for this aggregation. Defaults to the
	 *                              value of the taxonomy name for the 'author' taxonomy, as looked up in the DSL map.
	 * }
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
	 * @param array{label?: string, query_var?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $label     Optional. The human-readable name for this aggregation. Defaults to 'Custom Date
	 *                             Range'.
	 *     @type string $query_var Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                             'custom_date_range'.
	 * }
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
	 * Enables phrase matching for search queries.
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_phrase_matching(): Controller {
		$this->adapter->set_enable_phrase_matching( true );

		return $this;
	}

	/**
	 * Enables an aggregation based on post dates.
	 *
	 * @param array{interval?: 'year'|'quarter'|'month'|'week'|'day'|'hour'|'minute', label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', query_var?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $interval  Optional. The unit of time to aggregate results by. Valid options are 'year',
	 *                             'quarter', 'month', 'week', 'day', 'hour', 'minute'. Defaults to 'year'.
	 *     @type string $label     Optional. The human-readable name for this aggregation. Defaults to 'Date'.
	 *     @type string $order     Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                             Defaults to 'DESC'.
	 *     @type string $orderby   Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                             Defaults to 'count'.
	 *     @type string $query_var Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                             'post_date'.
	 * }
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_post_date_aggregation( array $args = [] ): Controller {
		$this->adapter->add_post_date_aggregation( $args );

		return $this;
	}

	/**
	 * Enables an aggregation based on post meta.
	 *
	 * @param string $meta_key The meta key to aggregate on.
	 * @param array{data_type?: string, label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', query_var?: string, relation?: 'AND'|'OR', term_field?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $data_type  Optional. The data type of the meta key, if the meta key is indexed using multiple
	 *                              data types (e.g., 'long'). Defaults to empty and uses the "raw" postmeta value.
	 *     @type string $label      Optional. The human-readable name for this aggregation. Defaults to a halfhearted
	 *                              attempt at turning the meta key into a title case string.
	 *     @type string $order      Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                              Defaults to 'DESC'.
	 *     @type string $orderby    Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                              Defaults to 'count'.
	 *     @type string $query_var  Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                              'post_meta_%s' where %s is the meta key.
	 *     @type string $relation   Optional. The logical relationship between each selected meta value when there is
	 *                              more than one. Valid options are 'AND', 'OR'. Defaults to 'AND'.
	 *     @type string $term_field Optional. The term field to use in the DSL for this aggregation. Defaults to the
	 *                              value of the post meta key, as looked up in the DSL map.
	 * }
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_post_meta_aggregation( string $meta_key, array $args = [] ): Controller {
		$this->adapter->add_post_meta_aggregation( $meta_key, $args );

		return $this;
	}

	/**
	 * Enables an aggregation based on post type.
	 *
	 * @param array{label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', query_var?: string, relation?: 'AND'|'OR', term_field?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $label      Optional. The human-readable name for this aggregation. Defaults to 'Content Type'.
	 *     @type string $order      Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                              Defaults to 'DESC'.
	 *     @type string $orderby    Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                              Defaults to 'count'.
	 *     @type string $query_var  Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                              'post_type'.
	 *     @type string $relation   Optional. The logical relationship between each selected author when there is more
	 *                              than one. Valid options are 'AND', 'OR'. Defaults to 'AND'.
	 *     @type string $term_field Optional. The term field to use in the DSL for this aggregation. Defaults to the
	 *                              'post_type' field, as looked up in the DSL map.
	 * }
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
	 * @param array{intervals?: int[], label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', query_var?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type int[]  $intervals Optional. The number of days prior to the current date to include in each bucket.
	 *                             Accepts an array of integers. Defaults to `[7, 30, 90]`.
	 *     @type string $label     Optional. The human-readable name for this aggregation. Defaults to 'Relative Date'.
	 *     @type string $order     Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                             Defaults to 'DESC'.
	 *     @type string $orderby   Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                             Defaults to 'count'.
	 *     @type string $query_var Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                             'relative_date'.
	 * }
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
	 * @param array{post_types?: string[], show_in_rest?: bool} $args {
	 *     Optional. An array of arguments.
	 *
	 *     @type string[] $post_types   Optional. Limit suggestions to this subset of all indexed post types. Accepts an
	 *                                  array of strings containing post type slugs. Defaults to all post types.
	 *     @type bool     $show_in_rest Optional. Whether to register REST API search handlers for querying suggestions.
	 *                                  Default true.
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
	 * @param array{label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', query_var?: string, relation?: 'AND'|'OR', term_field?: string} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $label      Optional. The human-readable name for this aggregation. Defaults to the singular
	 *                              name of the taxonomy (e.g., 'Category').
	 *     @type string $order      Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                              Defaults to 'DESC'.
	 *     @type string $orderby    Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                              Defaults to 'count'.
	 *     @type string $query_var  Optional. The query var to use in the URL. Accepts any URL-safe string. Defaults to
	 *                              'taxonomy_%s' where %s is the taxonomy slug.
	 *     @type string $relation   Optional. The logical relationship between each term when there is more than one.
	 *                              Valid options are 'AND', 'OR'. Defaults to 'AND'.
	 *     @type string $term_field Optional. The term field to use in the DSL for this aggregation. Defaults to the
	 *                              taxonomy's slug field, as looked up in the DSL map.
	 * }
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_taxonomy_aggregation( string $taxonomy, array $args = [] ): Controller {
		$this->adapter->add_taxonomy_aggregation( $taxonomy, $args );

		return $this;
	}

	/**
	 * A function to enable a generic Elasticsearch 'term' aggregation. Users must provide an
	 * Elasticsearch term field to aggregate on and a query var to use. This function should only
	 * be used if a more specific term-type aggregation (e.g., taxonomy, post type) is not
	 * available for the kind of aggregation you want to create.
	 *
	 * @param string $label The human-readable label for this aggregation.
	 * @param string $term_field The term field to aggregate on.
	 * @param string $query_var The query var to use for this aggregation for filters on the front-end.
	 * @param array{label?: string, order?: 'ASC'|'DESC', orderby?: 'count'|'key'|'label', relation?: 'AND'|'OR'} $args {
	 *     Optional. Arguments to pass to the adapter's aggregation configuration.
	 *
	 *     @type string $order      Optional. How to sort by the `orderby` field. Valid options are 'ASC', 'DESC'.
	 *                              Defaults to 'DESC'.
	 *     @type string $orderby    Optional. The field to order results by. Valid options are 'count', 'key', 'label'.
	 *                              Defaults to 'count'.
	 *     @type string $relation   Optional. The logical relationship between each term when there is more than one.
	 *                              Valid options are 'AND', 'OR'. Defaults to 'AND'.
	 * }
	 *
	 * @return Controller The instance of the class to allow for chaining.
	 */
	public function enable_term_aggregation( string $label, string $term_field, string $query_var, array $args = [] ): Controller {
		$this->adapter->add_term_aggregation( $label, $term_field, $query_var, $args );

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
	 * @return Aggregation[] An array of aggregation data grouped by aggregation type.
	 */
	public function get_aggregations(): array {
		return $this->adapter->get_aggregations();
	}

	/**
	 * Get value of adapter's enable_phrase_matching property.
	 *
	 * @return bool Whether phrase matching is enabled.
	 */
	public function get_enable_phrase_matching(): bool {
		return $this->adapter->get_enable_phrase_matching();
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
		} elseif ( defined( 'SP_VERSION' ) ) {
			$this->adapter = Factory::searchpress_adapter();
		} else {
			$this->adapter = Factory::generic_adapter();
		}
	}

	/**
	 * Directly query Elasticsearch.
	 *
	 * @param array $es_args The query to send to Elasticsearch.
	 *
	 * @return array The response from Elasticsearch.
	 */
	public function search( array $es_args ): array {
		return $this->adapter->search( $es_args );
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
}
