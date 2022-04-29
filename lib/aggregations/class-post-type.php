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
