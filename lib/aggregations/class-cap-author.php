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
		$args['label']    = __( 'Author', 'elasticsearch-extensions' );
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

		// Check for the existence of the global coauthors_plus object.
		global $coauthors_plus;
		if ( empty( $coauthors_plus ) ) {
			return;
		}

		// Loop over each term and map it to the CAP display name.
		foreach ( $buckets as $bucket ) {
			$coauthor_slug = preg_replace( '#^cap-#', '', $bucket['key'] );
			$coauthor      = $coauthors_plus->get_coauthor_by( 'user_nicename', $coauthor_slug );
			if ( ! empty( $coauthor ) ) {
				$this->buckets[] = new Bucket(
					$bucket['key'],
					$bucket['doc_count'],
					$coauthor->display_name ?: $coauthor->user_login,
					$this->is_selected( $bucket['key'] ),
				);
			}
		}

		// Allow the buckets to be filtered.
		$this->filter_buckets();
	}
}
