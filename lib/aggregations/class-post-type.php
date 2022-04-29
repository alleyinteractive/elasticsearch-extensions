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
