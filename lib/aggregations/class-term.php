<?php
/**
 * Elasticsearch Extensions: Term Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

/**
 * Generic Term aggregation class. Responsible for building the DSL and requests
 * for aggregations as well as holding the result of the aggregation after a
 * response was received.
 */
class Term extends Aggregation {

	/**
	 * The logical relationship between each selected term when there is more
	 * than one. If set to AND, all specified terms must be present on a single
	 * post in order for it to be included in the results. If set to OR, only
	 * one of the specified terms needs to be present on a single post in order
	 * for it to be included in the results. Defaults to AND so that selecting
	 * additional terms makes the result set smaller, not larger.
	 *
	 * @var string
	 */
	protected string $relation = 'AND';

	/**
	 * The term field to use for this aggregation.
	 *
	 * @var string
	 */
	protected string $term_field = '';

	/**
	 * Gets an array of DSL representing each filter for this aggregation that
	 * should be applied in the query in order to match the requested values.
	 *
	 * @return array Array of DSL fragments to apply.
	 */
	public function filter(): array {
		if ( empty( $this->query_values ) ) {
			return [];
		}

		// Fork for AND vs. OR logic.
		$filters = [];
		if ( 'OR' === $this->relation ) {
			$filters[] = $this->dsl->terms( $this->term_field, $this->query_values );
		} else {
			foreach ( $this->query_values as $query_value ) {
				$filters[] = $this->dsl->terms( $this->term_field, $query_value );
			}
		}

		return $filters;
	}

	/**
	 * Given a raw array of Elasticsearch aggregation buckets, parses it into
	 * Bucket objects and saves them in this object.
	 *
	 * @param array $buckets The raw aggregation buckets from Elasticsearch.
	 */
	public function parse_buckets( array $buckets ): void {
		$bucket_objects = [];
		foreach ( $buckets as $bucket ) {
			$bucket_objects[] = new Bucket(
				$bucket['key'],
				$bucket['doc_count'],
				$bucket['key'],
				$this->is_selected( $bucket['key'] ),
			);
		}
		$this->set_buckets( $bucket_objects );
	}

	/**
	 * Get DSL for the aggregation to add to the Elasticsearch request object.
	 * Instructs Elasticsearch to return buckets for this aggregation in the
	 * response.
	 *
	 * @return array DSL fragment.
	 */
	public function request(): array {
		return $this->dsl->aggregate_terms( $this->query_var, $this->term_field );
	}
}
