<?php
/**
 * Elasticsearch Extensions: Aggregation_Type Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use Elasticsearch_Extensions\Controller;

/**
 * Aggregation type abstract class. Responsible for building the DSL and
 * requests for aggregations.
 */
abstract class Aggregation_Type {
	/**
	 * The logic mode this aggregation should use. One of 'and', 'or'.
	 *
	 * @var string
	 */
	protected string $logic = 'or';

	/**
	 * The query var this aggregation should use.
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
	 * Build the aggregation type object.
	 */
	public function __construct() {
		$this->controller = Controller::instance();
	}

	/**
	 * Build the aggregation request.
	 *
	 * @return array
	 */
	abstract public function request(): array;

	/**
	 * Get the request filter DSL clause.
	 *
	 * @param array $values Values to pass to filter.
	 *
	 * @return array The filtered DSL.
	 */
	abstract public function filter( array $values ): array;

	/**
	 * Get the logic mode for this aggregation.
	 *
	 * @return string 'and' or 'or'.
	 */
	public function logic(): string {
		return $this->logic;
	}

	/**
	 * Get the query var for this aggregation.
	 *
	 * @return string
	 */
	public function query_var(): string {
		return $this->query_var;
	}
}
