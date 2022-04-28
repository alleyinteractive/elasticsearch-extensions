<?php
/**
 * Facet types abstract class.
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Facets;

use Elasticsearch_Extensions\Controller;

/**
 * Facet types abstract class. Responsible for building
 * the DSL and requests for facets.
 */
abstract class Facet_Type {
	/**
	 * The logic mode this facet should use. 'and' or 'or'.
	 *
	 * @var string
	 */
	protected string $logic = 'or';

	/**
	 * The query var this facet should use.
	 *
	 * @var string
	 */
	protected string $query_var;

	/**
	 * A reference to the ES controller.
	 *
	 * @var Controller
	 */
	protected Controller $controller;

	/**
	 * Build the facet type object.
	 */
	public function __construct() {
		$this->controller = Controller::instance();
	}

	/**
	 * Build the facet request.
	 *
	 * @return array
	 */
	abstract public function request(): array;

	/**
	 * Get the request filter DSL clause.
	 *
	 * @param  array $values Values to pass to filter.
	 * @return array
	 */
	abstract public function filter( array $values ): array;

	/**
	 * Get the logic mode for this facet.
	 *
	 * @return string 'and' or 'or'.
	 */
	public function logic(): string {
		return $this->logic;
	}

	/**
	 * Get the query var for this facet.
	 *
	 * @return string
	 */
	public function query_var(): string {
		return $this->query_var;
	}
}
