<?php
/**
 * Elasticsearch Extensions: Post_Meta Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use Elasticsearch_Extensions\DSL;

/**
 * Post_Meta aggregation class. Responsible for building the DSL and requests
 * for aggregations as well as holding the result of the aggregation after a
 * response was received.
 */
class Post_Meta extends Term {

	/**
	 * The postmeta key this aggregation is associated with.
	 *
	 * @var string
	 */
	protected string $meta_key;

	/**
	 * Configure the Post_Meta aggregation.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args ) {
		if ( ! empty( $args['meta_key'] ) ) {
			$this->label      = ucwords( str_replace( [ '-', '_' ], ' ', $args['meta_key'] ) );
			$this->meta_key   = $args['meta_key'];
			$this->query_var  = 'post_meta_' . $args['meta_key'];
			$this->term_field = $dsl->map_meta_field( $args['meta_key'], $args['data_type'] ?? '' );
			unset( $args['meta_key'] );
		}

		parent::__construct( $dsl, $args );
	}
}
