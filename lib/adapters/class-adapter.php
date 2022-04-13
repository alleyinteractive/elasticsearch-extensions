<?php
/**
 * Elasticsearch Extensions Adapters: Adapter Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

/**
 * An abstract class that establishes base functionality and sets requirements
 * for implementing classes.
 *
 * @package Elasticsearch_Extensions
 */
abstract class Adapter {

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
	 * Enables an aggregation based on post type.
	 */
	public function enable_post_type_aggregation(): void {
		$this->aggregate_post_types = true;
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
	 * Gets the list of taxonomies to be aggregated.
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
	protected function get_aggregations(): array {
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
