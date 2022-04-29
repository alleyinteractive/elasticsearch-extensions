<?php
/**
 * Elasticsearch Extensions: Aggregation Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use Elasticsearch_Extensions\DSL;

/**
 * Aggregation abstract class. Responsible for building the DSL and requests
 * for aggregations as well as holding the result of the aggregation after a
 * response was received.
 */
abstract class Aggregation {

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
	 * A reference to the DSL class, initialized with the map from the adapter.
	 *
	 * @var DSL
	 */
	protected DSL $dsl;

	/**
	 * Build the aggregation type object.
	 *
	 * @param DSL $dsl The DSL object, initialized with the map from the adapter.
	 */
	public function __construct( DSL $dsl ) {
		$this->dsl = $dsl;
	}

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

	/**
	 * Build the aggregation request.
	 *
	 * @return array
	 */
	abstract public function request(): array;
}
