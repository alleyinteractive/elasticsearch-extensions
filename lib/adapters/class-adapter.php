<?php
/**
 * Elasticsearch Extensions Adapters: Adapter Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

use Elasticsearch_Extensions\Aggregations\Aggregation;
use Elasticsearch_Extensions\Aggregations\CAP_Author;
use Elasticsearch_Extensions\Aggregations\Custom_Date_Range;
use Elasticsearch_Extensions\Aggregations\Post_Date;
use Elasticsearch_Extensions\Aggregations\Post_Type;
use Elasticsearch_Extensions\Aggregations\Relative_Date;
use Elasticsearch_Extensions\Aggregations\Taxonomy;
use Elasticsearch_Extensions\DSL;
use Elasticsearch_Extensions\Interfaces\Hookable;

/**
 * An abstract class that establishes base functionality and sets requirements
 * for implementing classes.
 *
 * @package Elasticsearch_Extensions
 */
abstract class Adapter implements Hookable {

	/**
	 * An optional array of meta fields to restrict search to.
	 *
	 * @var string[]
	 */
	private array $restricted_post_meta = [];

	/**
	 * An optional array of post types to restrict search to.
	 *
	 * @var string[]
	 */
	private array $restricted_post_types = [];

	/**
	 * An optional array of taxonomies to restrict search to.
	 *
	 * @var string[]
	 */
	private array $restricted_taxonomies = [];

	/**
	 * Constructor. Sets up the DSL object with this adapter's field map.
	 */
	public function __construct() {
		$this->dsl = new DSL( $this->get_field_map() );
	}

	/**
	 * Returns a map of generic field names and types to the specific field
	 * path used in the mapping of the Elasticsearch plugin that is in use.
	 * Implementing classes need to provide this map, as it will be different
	 * between each plugin's Elasticsearch implementation, and use the result
	 * of this function when initializing the DSL class in the constructor.
	 *
	 * @return array The field map.
	 */
	abstract protected function get_field_map(): array;

	/**
	 * Gets the list of restricted meta.
	 *
	 * @return string[] The list of restricted meta slugs.
	 */
	protected function get_restricted_post_meta(): array {
		return $this->restricted_post_meta;
	}

	/**
	 * Gets the list of restricted post types.
	 *
	 * @return string[] The list of restricted post type slugs.
	 */
	protected function get_restricted_post_types(): array {
		return $this->restricted_post_types;
	}

	/**
	 * Gets the list of restricted taxonomies.
	 *
	 * @return string[] The list of restricted taxonomy slugs.
	 */
	protected function get_restricted_taxonomies(): array {
		return $this->restricted_taxonomies;
	}

	/**
	 * Returns a list of searchable post types. Defaults come from the filter
	 * function used in the adapter. Allows for applying different logic
	 * depending on the context (e.g., main search vs. custom search
	 * interfaces).
	 *
	 * @param array $post_types The default list of post type slugs from the adapter.
	 *
	 * @return array An array of searchable post type slugs.
	 */
	protected function get_searchable_post_types( array $post_types ): array {
		/**
		 * Filters the list of searchable post types.
		 *
		 * Defaults to the list of searchable post type slugs from the adapter.
		 *
		 * @since 0.1.0
		 *
		 * @param array $post_types The array of post type slugs to include in search.
		 */
		return apply_filters( 'elasticsearch_extensions_searchable_post_types', $post_types );
	}

	/**
	 * Restricts indexable post meta to the provided list.
	 *
	 * @param string[] $post_meta The array of meta fields to restrict to.
	 */
	public function restrict_post_meta( array $post_meta ): void {
		$this->restricted_post_meta = $post_meta;
	}

	/**
	 * Restricts indexable post types to the provided list.
	 *
	 * @param string[] $post_types The array of post types to restrict to.
	 */
	public function restrict_post_types( array $post_types ): void {
		$this->restricted_post_types = $post_types;
	}

	/**
	 * Restricts searchable taxonomies to the provided list.
	 *
	 * @param string[] $taxonomies The array of taxonomies to restrict search to.
	 */
	public function restrict_taxonomies( array $taxonomies ): void {
		$this->restricted_taxonomies = $taxonomies;
	}
}
