<?php
/**
 * Elasticsearch Extensions: Co-Authors Plus Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use Elasticsearch_Extensions\DSL;

/**
 * Co-Authors Plus authors aggregation class. Responsible for building the DSL
 * and requests for aggregations as well as holding the result of the
 * aggregation after a response was received.
 */
class CAP_Author extends Taxonomy {

	/**
	 * Configure the Co-Authors Plus Author aggregation.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args ) {
		$args['taxonomy'] = 'author';

		parent::__construct( $dsl, $args );
	}

	/**
	 * Given a raw array of Elasticsearch aggregation buckets, parses it into
	 * Bucket objects and saves them in this object.
	 *
	 * @param array $buckets The raw aggregation buckets from Elasticsearch.
	 */
	public function parse_buckets( array $buckets ): void {
		// TODO: Update this to get the CAP author name.
		foreach ( $buckets as $bucket ) {
			$term = get_term_by( 'slug', $bucket['key'], $this->taxonomy->name );
			if ( ! empty( $term ) ) {
				$this->buckets[] = new Bucket(
					$bucket['key'],
					$bucket['doc_count'],
					$term->name,
					$this->is_selected( $bucket['key'] ),
				);
			}
		}
	}
}
