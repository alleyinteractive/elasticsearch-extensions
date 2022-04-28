<?php
/**
 * Post type facet type
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Facets;

use Elasticsearch_Extensions\DSL;

/**
 * Post type facet type. Responsible for building
 * the DSL and requests for post type facets.
 */
class Post_Type extends Facet_Type {
	/**
	 * The query var this facet should use.
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
