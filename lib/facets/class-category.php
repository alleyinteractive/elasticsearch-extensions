<?php
/**
 * Category facet type
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Facets;

use Elasticsearch_Extensions\DSL;

/**
 * Category facet type. Responsible for building
 * the DSL and requests for category facets.
 */
class Category extends Facet_Type {
	/**
	 * The query var this facet should use.
	 *
	 * @var string
	 */
	protected string $query_var = 'category';

	/**
	 * The logic mode this facet should use. 'and' or 'or'.
	 *
	 * @var string
	 */
	protected string $logic = 'and';

	/**
	 * Build the facet request.
	 *
	 * @return array
	 */
	public function request(): array {
		return [
			'taxonomy_category' => [
				'terms' => [
					'field' => $this->controller->map_tax_field( 'category', 'category_slug' ),
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
		return DSL::all_terms( 'category', 'category_slug', $values );
	}
}
