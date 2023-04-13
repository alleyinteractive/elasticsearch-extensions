<?php
/**
 * Elasticsearch Extensions: Taxonomy Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use Elasticsearch_Extensions\DSL;
use WP_Taxonomy;

/**
 * Taxonomy aggregation class. Responsible for building the DSL and requests
 * for aggregations as well as holding the result of the aggregation after a
 * response was received.
 */
class Taxonomy extends Aggregation {

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
	 * A reference to the taxonomy this aggregation is associated with.
	 *
	 * @var WP_Taxonomy
	 */
	protected WP_Taxonomy $taxonomy;

	/**
	 * Configure the Taxonomy aggregation.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args ) {
		// Try to get a taxonomy object based on the provided taxonomy slug.
		$taxonomy = get_taxonomy( $args['taxonomy'] ?? null );
		if ( ! empty( $taxonomy ) ) {
			$this->taxonomy  = $taxonomy;
			$this->label     = $taxonomy->labels->singular_name;
			$this->query_var = 'taxonomy_' . $taxonomy->name;
		}

		// Remove the taxonomy slug from arguments before passing them to the constructor so we don't overwrite $this->taxonomy.
		if ( isset( $args['taxonomy'] ) ) {
			unset( $args['taxonomy'] );
		}

		parent::__construct( $dsl, $args );
	}

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
		$field   = $this->dsl->map_tax_field( $this->taxonomy->name, $this->get_term_field() );
		if ( 'OR' === $this->relation ) {
			$filters[] = $this->dsl->terms( $field, $this->query_values );
		} else {
			foreach ( $this->query_values as $query_value ) {
				$filters[] = $this->dsl->terms( $field, $query_value );
			}
		}

		return $filters;
	}

	/**
	 * Gets the WP Taxonomy Object for this aggregation.
	 *
	 * @return \WP_Taxonomy The aggregation Taxonomy .
	 */
	public function get_taxonomy(): WP_Taxonomy {
		return $this->taxonomy;
	}

	/**
	 * Provides a central place for the term field to be filtered. Defaults to
	 * 'term_slug' but can be modified via the filter to operate on term IDs,
	 * names, etc.
	 *
	 * @return string The term field name.
	 */
	private function get_term_field(): string {
		/**
		 * Filters the unmapped field name used in a taxonomy aggregation.
		 *
		 * @param string      $field    The field to aggregate.
		 * @param WP_Taxonomy $taxonomy The taxonomy for this aggregation.
		 */
		return apply_filters( 'elasticsearch_extensions_aggregation_taxonomy_field', 'term_slug', $this->taxonomy );
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
			$term = get_term_by( 'slug', $bucket['key'], $this->taxonomy->name );
			if ( ! empty( $term ) ) {
				$bucket_objects[] = new Bucket(
					$bucket['key'],
					$bucket['doc_count'],
					$term->name,
					$this->is_selected( $bucket['key'] ),
				);
			}
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
		return $this->dsl->aggregate_terms(
			$this->query_var,
			$this->dsl->map_tax_field( $this->taxonomy->name, $this->get_term_field() )
		);
	}
}
