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
	 * A reference to the taxonomy this aggregation is associated with.
	 *
	 * @var WP_Taxonomy
	 */
	protected WP_Taxonomy $taxonomy;

	/**
	 * Configure the Post Type aggregation.
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
			$this->query_var = 'taxonomy_' . $taxonomy->query_var;
		}

		// Remove the taxonomy slug from arguments before passing them to the constructor so we don't overwrite $this->taxonomy.
		if ( isset( $args['taxonomy'] ) ) {
			unset( $args['taxonomy'] );
		}

		parent::__construct( $dsl, $args );
	}

	/**
	 * Get DSL for filters that should be applied in the DSL in order to match
	 * the requested values.
	 *
	 * @return array|null DSL fragment or null if no filters to apply.
	 */
	public function filter(): ?array {
		return ! empty( $this->query_values )
			? $this->dsl->terms(
				$this->dsl->map_tax_field( $this->taxonomy->name, $this->get_term_field() ),
				$this->query_values
			) : null;
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
		foreach ( $buckets as $bucket ) {
			$term            = get_term_by( 'slug', $bucket['key'] );
			$this->buckets[] = new Bucket(
				$bucket['key'],
				$bucket['doc_count'],
				$term->name,
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
			$this->dsl->map_tax_field( $this->taxonomy->name, $this->get_term_field() )
		);
	}
}
