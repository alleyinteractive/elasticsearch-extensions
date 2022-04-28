<?php
/**
 * Elasticsearch Extensions Adapters: Adapter Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\Aggregation;

/**
 * An abstract class that establishes base functionality and sets requirements
 * for implementing classes.
 *
 * @package Elasticsearch_Extensions
 */
abstract class Adapter {

	/**
	 * Aggregation configuration to be added to the Elasticsearch request.
	 *
	 * @var array
	 */
	private array $aggregation_config = [];

	/**
	 * Stores aggregation data from the Elasticsearch response.
	 *
	 * @var array
	 */
	private array $aggregations = [];

	/**
	 * Whether to allow empty searches (no keyword set).
	 *
	 * @var bool
	 */
	private bool $allow_empty_search = false;

	/**
	 * Holds a reference to the singleton instance.
	 *
	 * @var Adapter
	 */
	private static Adapter $instance;

	/**
	 * Get an aggregation by a field key and value.
	 *
	 * @param string $field Field key.
	 * @param string $value Field value.
	 *
	 * @return Aggregation|null
	 */
	public function get_aggregation_by( string $field = '', string $value = '' ) {
		foreach ( $this->aggregations as $aggregation ) {
			if ( isset( $aggregation->$field ) && $value === $aggregation->$field ) {
				return $aggregation;
			}
		}

		return null;
	}

	/**
	 * Get the aggregation configuration.
	 *
	 * @return array
	 */
	public function get_aggregation_config(): array {
		return $this->aggregation_config;
	}

	/**
	 * Gets the value for allow_empty_search.
	 *
	 * @return bool Whether to allow empty search or not.
	 */
	public function get_allow_empty_search(): bool {
		return $this->allow_empty_search;
	}

	/**
	 * Returns a map of generic field names and types to the specific field
	 * path used in the mapping of the Elasticsearch plugin that is in use.
	 * Implementing classes need to provide this map, as it will be different
	 * between each plugin's Elasticsearch implementation.
	 *
	 * @return array The field map.
	 */
	abstract protected function get_field_map(): array;

	/**
	 * Sets the value for allow_empty_search.
	 *
	 * @param bool $allow_empty_search Whether to allow empty search or not.
	 */
	public function set_allow_empty_search( bool $allow_empty_search ): void {
		$this->allow_empty_search = $allow_empty_search;
	}

	// TODO: Refactor line.

	/**
	 * Configures facets.
	 *
	 * TODO Add param DocBloc with full set of possible array keys.
	 *
	 * @param array $facet_config Array configuration for a facet.
	 * @return void
	 */
	public function add_facet_config( array $facet_config ) {
		$config = [];
		$label  = $facet_config['type'];
		if (
			'taxonomy' === $facet_config['type']
			&& ! empty( $facet_config['taxonomy'] )
			&& 'taxonomy_' !== substr( $facet_config['taxonomy'], 0, 9 )
		) {
			$label = "taxonomy_{$facet_config['taxonomy']}";
		}

		$config[ $label ]    = $facet_config;
		$this->facets_config = array_merge( $this->facets_config, $config );
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
			[
				'exclude_current'     => true,
				'join_existing_terms' => true,
				'join_terms_logic'    => [],
			]
		);

		$facet_data = [];

		foreach ( $facets as $label => $facet ) {
			// At this point, $this->facets is an array of Facet objects...
			if ( empty( $this->facets[ $label ] ) ) {
				continue;
			}

			$facet_data[ $label ]        = $this->facets[ $label ];
			$facet_data[ $label ]->items = [];

			/*
			 * All taxonomy terms are going to have the same query_var, so run
			 * this before the loop.
			 */
			if ( 'taxonomy' === $this->facets[ $label ]->type ) {
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
			// TODO Refactor this. The `count` attribute doesn't exist at this point.
			if ( count( $items ) > $this->facets[ $label ]->count ) {
				$items = array_slice( $items, 0, $this->facets[ $label ]->count );
			}

			foreach ( $items as $item ) {
				$datum = apply_filters( 'elasticsearch_extensions_facet_datum', false, $item, $this->facets );
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

							// TODO Refactor to handle this better?
							// TODO Adapter::get_taxonomy_query_var() sets tags query vars as 'tag', which, without this edit, gives rise to a mismatch between the query var set in Facet::parse_type().
							if ( 'tag' === $tax_query_var ) {
								$query_vars = [
									'post_tag' => implode( $join_logic, $slugs ),
								];
							} else {
								$query_vars = [
									$tax_query_var => implode( $join_logic, $slugs ),
								];
							}
							$name = $term->name;

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

						case 'post_date':
							$timestamp = $item['key'] / 1000;

							switch ( $this->facets[ $label ]->config['calendar_interval'] ) {
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

		return apply_filters( 'elasticsearch_extensions_facet_data', $facet_data );
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
	 * Maps a field key to the Elasticsearch mapped field path.
	 *
	 * @param string $field The field key to map.
	 *
	 * @return string The mapped field reference.
	 */
	public function map_field( string $field ): string {
		return $this->get_field_map()[ $field ] ?? $field;
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
	public function map_tax_field( string $taxonomy, string $field ): string {
		if ( 'post_tag' === $taxonomy ) {
			$field = str_replace( 'term_', 'tag_', $field );
		} elseif ( 'category' === $taxonomy ) {
			$field = str_replace( 'term_', 'category_', $field );
		}
		return sprintf( $this->map_field( $field ), $taxonomy );
	}

	/**
	 * Pull the facets out of the ES response.
	 * Filters `ep_valid_response`.
	 *
	 * @see \ElasticPress\Elasticsearch
	 */
	public function parse_facets() {
		$this->facets = apply_filters( 'elasticsearch_extensions_parse_facets', [] );
		if ( empty( $this->facets ) ) {
			if ( ! empty( $this->results['aggregations'] ) ) {
				foreach ( $this->results['aggregations'] as $label => $buckets ) {
					if ( empty( $buckets['buckets'] ) ) {
						continue;
					}
					$this->facets[ $label ] = new Facet( $label, $buckets['buckets'], $this->facets_config[ $label ] );
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
