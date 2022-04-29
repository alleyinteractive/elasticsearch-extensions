<?php
/**
 * Elasticsearch Extensions: Taxonomy Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use Elasticsearch_Extensions\DSL;

/**
 * Taxonomy aggregation class. Responsible for building the DSL and requests
 * for aggregations as well as holding the result of the aggregation after a
 * response was received.
 */
class Taxonomy extends Aggregation {

	/**
	 * Get the query var for a given taxonomy name.
	 *
	 * @param string $taxonomy_name The taxonomy slug.
	 *
	 * @return string The query var for the given taxonomy or an empty string on failure.
	 */
	private function get_taxonomy_query_var( string $taxonomy_name ) {
		$taxonomy = get_taxonomy( $taxonomy_name );

		return $taxonomy->query_var ?? '';
	}

	// TODO: REFACTOR LINE

	/**
	 * The query var this aggregation should use.
	 *
	 * @var string
	 */
	protected string $query_var = 'post_type';

	/**
	 * Build the facet request.
	 *
	 * @return array
	 */
	public function request(): array {
		return [
			'post_type' => [
				'terms' => [
					'field' => $this->controller->map_field( 'post_type' ),
				],
			],
		];
	}

	/**
	 * Get the request filter DSL clause.
	 *
	 * @param  array $values Values to pass to filter.
	 * @return array
	 */
	public function filter( array $values ): array {
		return DSL::terms( 'post_type', $values );
	}
}
