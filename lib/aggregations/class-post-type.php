<?php
/**
 * Elasticsearch Extensions: Post Type Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use Elasticsearch_Extensions\DSL;

/**
 * Post type aggregation class. Responsible for building the DSL and requests
 * for aggregations as well as holding the result of the aggregation after a
 * response was received.
 */
class Post_Type extends Aggregation {

	/**
	 * Configure the Post Type aggregation.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args ) {
		$this->label     = __( 'Content Type', 'elasticsearch-extensions' );
		$this->query_var = 'post_type';
		parent::__construct( $dsl, $args );
	}

	/**
	 * Given a raw array of Elasticsearch aggregation buckets, parses it into
	 * Bucket objects and saves them in this object.
	 *
	 * @param array $buckets The raw aggregation buckets from Elasticsearch.
	 */
	public function parse_buckets( array $buckets ): void {
		/**
		 * Allows the label field for a post type aggregation to be filtered.
		 * For example, this filter could be used to use the plural form of the
		 * label instead of the singular.
		 *
		 * @param string $label The slug of the label to use. See get_post_type_labels() for a full list of options.
		 */
		$label = apply_filters( 'elasticsearch_extensions_aggregation_post_type_label', 'singular_name' );

		foreach ( $buckets as $bucket ) {
			$post_type = get_post_type_object( $bucket['key'] );
			$this->buckets[] = new Bucket(
				$bucket['key'],
				$bucket['doc_count'],
				$post_type->labels->$label,
				$this->is_selected( $bucket['key'] ),
			);
		}
	}

	/**
	 * Get DSL for the aggregation to add to the Elasticsearch request object.
	 * Instructs Elasticsearch to return buckets for this aggregation in the
	 * response.
	 *
	 * @return array DSL fragment.
	 */
	public function request(): array {
		return $this->dsl->aggregate_terms(
			$this->query_var,
			$this->dsl->map_field( 'post_type' )
		);
	}
}
