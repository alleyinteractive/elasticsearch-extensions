<?php
/**
 * Elasticsearch Extensions Adapters: Adapter Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\Facet;

/**
 * An abstract class that establishes base functionality and sets requirements
 * for implementing classes.
 *
 * @package Elasticsearch_Extensions
 */
abstract class Adapter {

	/**
	 * Whether WP Category aggregations are active or not.
	 *
	 * @var bool
	 */
	private bool $aggregate_categories = false;

	/**
	 * Whether WP Tag aggregations are active or not.
	 *
	 * @var bool
	 */
	private bool $aggregate_tags = false;

	/**
	 * Whether post type aggregations are active or not.
	 *
	 * @var bool
	 */
	private bool $aggregate_post_types = false;

	/**
	 * Stores an array of taxonomy slugs that should be added to aggregations.
	 *
	 * @var array
	 */
	private array $aggregate_taxonomies = [];

	/**
	 * Stores aggregation data from the Elasticsearch response.
	 *
	 * @var array
	 */
	private array $aggregations = [];

	/**
	 * Facets.
	 *
	 * @var array
	 */
	public array $facets = [];

	/**
	 * Facets.
	 *
	 * @var array
	 */
	public array $facets_config = [];

	/**
	 * HTTP Response from last query.
	 *
	 * @var array HTTP Response.
	 */
	public array $results;

	/**
	 * Holds a reference to the singleton instance.
	 *
	 * @var Adapter
	 */
	private static Adapter $instance;

	/**
	 * Map core fields to the ES index.
	 *
	 * @var array|string[]
	 */
	protected array $field_map = [
		'post_meta'              => 'post_meta.%s',
		'post_meta.analyzed'     => 'post_meta.%s.analyzed',
		'post_meta.long'         => 'post_meta.%s.long',
		'post_meta.double'       => 'post_meta.%s.double',
		'post_meta.binary'       => 'post_meta.%s.boolean',
		'post_meta.date'         => 'post_meta.%s.date',
		'post_meta.datetime'     => 'post_meta.%s.datetime',
		'post_meta.time'         => 'post_meta.%s.time',
		'post_meta.signed'       => 'post_meta.%s.signed',
		'post_meta.unsigned'     => 'post_meta.%s.unsigned',
		'term_id'                => 'terms.%s.term_id',
		'term_slug'              => 'terms.%s.slug',
		'term_name'              => 'terms.%s.name',
		'term_name.analyzed'     => 'terms.%s.name.analyzed',
		'term_tt_id'             => 'terms.%s.term_taxonomy_id',
		'category_id'            => 'terms.category.term_id',
		'category_slug'          => 'terms.category.slug',
		'category_name'          => 'terms.category.name',
		'category_name.analyzed' => 'terms.category.name.analyzed',
		'category_tt_id'         => 'terms.category.term_taxonomy_id',
		'tag_id'                 => 'terms.post_tag.term_id',
		'tag_slug'               => 'terms.post_tag.slug',
		'tag_name'               => 'terms.post_tag.name',
		'tag_name.analyzed'      => 'terms.post_tag.name.analyzed',
		'tag_tt_id'              => 'terms.post_tag.term_taxonomy_id',
	];

	/**
	 * Configures facets.
	 *
	 * TODO Add param DocBloc with full set of possible array keys.
	 *
	 * @param array $facets_config
	 * @return void
	 */
	public function set_facets_config( array $facets_config ) {
		$config = [];
		foreach ( $facets_config as $label => $facet_config ) {
			if (
				'taxonomy' === $facet_config['type']
				&& 'taxonomy_' !== substr( $label, 0, 9 )
			) {
				$label = "taxonomy_{$label}";
			}

			$config[ $label ] = $facet_config;
		}
		$this->facets_config = $config;
	}

	/**
	 * Get the configured facets.
	 * @return array
	 */
	public function get_facet_config(): array {
		return $this->facets_config;
	}

	/**
	 * Parse the raw facet data from Elasticsearch into a constructive format.
	 *
	 * Specifically:
	 *
	 *     array(
	 *         'Label' => array(
	 *             'type'     => [type requested],
	 *             'count'    => [count requested],
	 *             'taxonomy' => [taxonomy requested, if applicable],
	 *             'interval' => [interval requested, if applicable],
	 *             'field'    => [field requested, if applicable],
	 *             'items'    => array(
	 *                 array(
	 *                     'query_vars' => array( [query_var] => [value] ),
	 *                     'name' => [formatted string for this facet],
	 *                     'count' => [number of results in this facet],
	 *                 )
	 *             )
	 *         )
	 *     )
	 *
	 * The returning array is mostly the data as requested in the WP args, with
	 * the addition of the 'items' key. This is an array of arrays, each one
	 * being a term in the facet response. The 'query_vars' can be used to
	 * generate links/form fields. The name is suitable for display, and the
	 * count is useful for your facet UI.
	 *
	 * @param array $options {
	 *     Optional. Options for getting facet data.
	 *
	 *     @type boolean $exclude_current If true, excludes the currently-selected
	 *                                    facets in the list. This is most helpful
	 *                                    when outputting a list of links, but
	 *                                    should probably be disabled if outputting
	 *                                    a list of checkboxes. Defaults to true.
	 * }
	 * @return array|Facet[] See above for further details.
	 */
	public function get_facet_data( array $options = [] ): array {
		if ( empty( $this->facets ) ) {
			return [];
		}

		$facets = $this->results['aggregations'];

		if ( empty( $facets ) ) {
			return [];
		}

		$options = wp_parse_args(
			$options,
			array(
				'exclude_current'     => true,
				'join_existing_terms' => true,
				'join_terms_logic'    => [],
			)
		);

		$facet_data = [];

		foreach ( $facets as $label => $facet ) {
			// At this point, $this->facets is an array of Facet objects...
			if ( empty( $this->facets[ $label ] ) ) {
				continue;
			}

			$facet_data[ $label ]          = $this->facets[ $label ];
			$facet_data[ $label ]->items = [];

			/*
			 * All taxonomy terms are going to have the same query_var, so run
			 * this before the loop.
			 */
			if ( 'taxonomy' === $this->facets[ $label ]->type ) {
				// TODO There is no class value set for `taxonomy`. Why not? Also, these should be arrays, not classes as per ES Admin. Why are these facet objects? I suspect this is a root issue.
				$tax_query_var = $this->get_taxonomy_query_var( $this->facets[ $label ]->query_var );

				if ( ! $tax_query_var ) {
					continue;
				}

				$existing_term_slugs = ( get_query_var( $tax_query_var ) ) ? explode( ',', get_query_var( $tax_query_var ) ) : [];
			}

			$items = [];
			if ( ! empty( $facet['buckets'] ) ) {
				$items = (array) $facet['buckets'];
			}

			// Some facet types like date_histogram don't support the max results parameter.
			if ( count( $items ) > $this->facets[ $label ]->count ) {
				$items = array_slice( $items, 0, $this->facets[ $label ]->count );
			}

			foreach ( $items as $item ) {
				$datum = apply_filters( 'es_extensions_facet_datum', false, $item, $this->facets );
				if ( false === $datum ) {
					$query_vars = [];
					$selected   = false;

					switch ( $this->facets[ $label ]->type ) {
						case 'taxonomy':
							$term = get_term_by( 'slug', $item['key'], $this->facets[ $label ]->query_var );

							if ( ! $term ) {
								continue 2; // switch() is considered a looping structure.
							}

							// Don't allow refinement on a term we're already refining on.
							$selected = in_array( $term->slug, $existing_term_slugs, true );
							if ( $options['exclude_current'] && $selected ) {
								continue 2;
							}

							$slugs = [ $term->slug ];
							if ( $options['join_existing_terms'] ) {
								$slugs = array_merge( $existing_term_slugs, $slugs );
							}

							$join_logic = ',';
							if (
								isset( $options['join_terms_logic'][ $this->facets[ $label ]->query_var ] )
								&& '+' === $options['join_terms_logic'][ $this->facets[ $label ]->query_var ]
							) {
								$join_logic = '+';
							}

							$query_vars = [
								$tax_query_var => implode( $join_logic, $slugs ),
							];
							$name       = $term->name;

							break;

						case 'post_type':
							$post_type = get_post_type_object( $item['key'] );

							if ( ! $post_type || $post_type->exclude_from_search ) {
								continue 2;  // switch() is considered a looping structure.
							}

							$query_vars = [ 'post_type' => $item['key'] ];
							$name       = $post_type->labels->singular_name;

							break;

						case 'author':
							$user = get_user_by( 'login', $item['key'] );

							if ( ! $user ) {
								continue 2;
							}

							$name       = $user->display_name;
							$query_vars = [ 'author' => $user->ID ];

							break;

						case 'date_histogram':
							$timestamp = $item['key'] / 1000;

							switch ( $this->facets[ $label ]->interval ) {
								case 'year':
									$query_vars = [
										'year' => gmdate( 'Y', $timestamp ),
									];
									$name       = gmdate( 'Y', $timestamp );
									break;

								case 'month':
									$query_vars = [
										'year'     => gmdate( 'Y', $timestamp ),
										'monthnum' => gmdate( 'n', $timestamp ),
									];
									$name       = gmdate( 'F Y', $timestamp );
									break;

								case 'day':
									$query_vars = [
										'year'     => gmdate( 'Y', $timestamp ),
										'monthnum' => gmdate( 'n', $timestamp ),
										'day'      => gmdate( 'j', $timestamp ),
									];
									$name       = gmdate( 'F j, Y', $timestamp );
									break;

								default:
									continue 3; // switch() is considered a looping structure.
							}

							break;

						default:
							// continue 2; // switch() is considered a looping structure.
					}

					$datum = [
						'query_vars' => $query_vars,
						'name'       => $name,
						'count'      => $item['doc_count'],
						'selected'   => $selected,
					];
				}

				$facet_data[ $label ]->items[] = $datum;
			}
		}

		return apply_filters( 'es_extensions_facet_data', $facet_data );
	}

	/**
	 * Get Facet by field
	 *
	 * @param string $field Facet field. See get_facet_data for acceptable values.
	 * @param string $value
	 * @return Facet|null
	 */
	public function get_facet_data_by( string $field = '', string $value = '' ) {
		$facet_data = $this->get_facet_data();
		foreach ( $facet_data as $facet ) {
			if ( isset( $facet[ $field ] ) && $value === $facet[ $field ] ) {
				return $facet;
			}
		}
		return null;
	}

	/**
	 * Get the query var for a given taxonomy name.
	 *
	 * @access protected
	 *
	 * @param  string $taxonomy_name A valid taxonomy.
	 * @return string The query var for the given taxonomy.
	 */
	protected function get_taxonomy_query_var( string $taxonomy_name ) {
		$taxonomy = get_taxonomy( $taxonomy_name );

		if ( ! $taxonomy || is_wp_error( $taxonomy ) ) {
			return false;
		}

		return $taxonomy->query_var;
	}

	/**
	 * Enables an aggregation based on WP Category.
	 */
	public function enable_category_aggregation(): void {
		$this->aggregate_categories = true;
	}

	/**
	 * Enables an aggregation based on post type.
	 */
	public function enable_post_type_aggregation(): void {
		$this->aggregate_post_types = true;
	}

	/**
	 * Enables an aggregation based on WP Tags.
	 */
	public function enable_tag_aggregation(): void {
		$this->aggregate_tags = true;
	}

	/**
	 * A function to enable an aggregation for a specific taxonomy.
	 *
	 * @param string $taxonomy The taxonomy slug for which to enable an aggregation.
	 */
	public function enable_taxonomy_aggregation( string $taxonomy ): void {
		$this->aggregate_taxonomies[] = $taxonomy;
	}

	/**
	 * Gets the flag for whether post types should be aggregated or not.
	 *
	 * @return bool Whether post types should be aggregated or not.
	 */
	protected function get_aggregate_post_types(): bool {
		return $this->aggregate_post_types;
	}

	/**
	 * Gets the flag for whether WP Categories should be aggregated or not.
	 *
	 * @return bool Whether WP Categories should be aggregated or not.
	 */
	protected function get_aggregate_categories(): bool {
		return $this->aggregate_categories;
	}

	/**
	 * Gets the flag for whether WP Tags should be aggregated or not.
	 *
	 * @return bool Whether WP Tags should be aggregated or not.
	 */
	protected function get_aggregate_tags(): bool {
		return $this->aggregate_tags;
	}

	/**
	 * Gets the list of custom taxonomies to be aggregated.
	 *
	 * @return array The list of taxonomy slugs to be aggregated.
	 */
	protected function get_aggregate_taxonomies(): array {
		return $this->aggregate_taxonomies;
	}

	/**
	 * Gets aggregation results.
	 *
	 * @return array The aggregation results.
	 */
	public function get_aggregations(): array {
		return $this->aggregations;
	}

	/**
	 * Get an instance of the class.
	 *
	 * @return Adapter
	 */
	public static function instance(): Adapter {
		$class_name = get_called_class();
		if ( ! isset( self::$instance ) ) {
			self::$instance = new $class_name();
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Map a core field to the indexed counterpart in Elasticsearch.
	 *
	 * @param  string $field The core field to map.
	 * @return string The mapped field reference.
	 */
	public function map_field( string $field ): string {
		if ( ! empty( $this->field_map[ $field ] ) ) {
			return $this->field_map[ $field ];
		} else {
			return $field;
		}
	}

	/**
	 * Map a meta field. This will swap in the data type.
	 *
	 * @param  string $meta_key Meta key to map.
	 * @param  string $type Data type to map.
	 * @return string The mapped field.
	 */
	public function map_meta_field( string $meta_key, string $type = '' ): string {
		if ( ! empty( $type ) ) {
			return sprintf( $this->map_field( 'post_meta.' . $type ), $meta_key );
		} else {
			return sprintf( $this->map_field( 'post_meta' ), $meta_key );
		}
	}

	/**
	 * Map a taxonomy field. This will swap in the taxonomy name.
	 *
	 * @param  string $taxonomy Taxonomy to map.
	 * @param  string $field Field to map.
	 * @return string The mapped field.
	 */
	public function map_tax_field( string $taxonomy, string $field ): string  {
		if ( 'post_tag' === $taxonomy ) {
			$field = str_replace( 'term_', 'tag_', $field );
		} elseif ( 'category' === $taxonomy ) {
			$field = str_replace( 'term_', 'category_', $field );
		}
		return sprintf( $this->map_field( $field ), $taxonomy );
	}

	/**
	 * Pull the facets out of the ES response.
	 */
	public function parse_facets() {
		$this->facets = apply_filters( 'es_extensions_parse_facets', [] );
		if ( empty( $this->facets ) ) {
			if ( ! empty( $this->results['aggregations'] ) ) {
				foreach ( $this->results['aggregations'] as $label => $buckets ) {
					if ( empty( $buckets['buckets'] ) ) {
						continue;
					}
					$this->facets[ $label ] = new Facet( $label, $buckets['buckets'] );
				}
			}
		}
	}

	/**
	 * Sets aggregation results.
	 *
	 * @param array $aggregations An array of aggregation results to be stored.
	 */
	protected function set_aggregations( array $aggregations ): void {
		$this->aggregations = $aggregations;
	}

	/**
	 * Sets up the singleton by registering action and filter hooks.
	 */
	abstract public function setup(): void;
}
