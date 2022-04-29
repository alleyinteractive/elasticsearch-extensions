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
	 * Results for this aggregation from Elasticsearch. An array of Bucket objects.
	 *
	 * @var array
	 */
	protected array $buckets = [];

	/**
	 * A reference to the DSL class, initialized with the map from the adapter.
	 *
	 * @var DSL
	 */
	protected DSL $dsl;

	/**
	 * The human-readable label for this aggregation.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * The query var this aggregation should use.
	 *
	 * @var string
	 */
	protected string $query_var = '';

	/**
	 * The values for the query var for this aggregation.
	 *
	 * @var array
	 */
	protected array $query_values = [];

	/**
	 * Build the aggregation type object.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args = [] ) {
		$this->dsl = $dsl;
		foreach ( $args as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		// Extract selected values from the query var.
		$fs                 = get_query_var( 'fs' );
		$selected           = $fs[ $this->query_var ] ?? [];
		$this->query_values = array_values( array_filter( $selected ) );
	}

	/**
	 * Get DSL for filters that should be applied in the DSL in order to match
	 * the requested values.
	 *
	 * @return array|null DSL fragment or null if no filters to apply.
	 */
	abstract public function filter(): ?array;

	/**
	 * Gets a list of results for this aggregation.
	 *
	 * @return array An array of Bucket objects.
	 */
	public function get_buckets(): array {
		return $this->buckets;
	}

	/**
	 * Gets the human-readable label for this aggregation.
	 *
	 * @return string The human-readable label for this aggregation.
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Get the query var for this aggregation.
	 *
	 * @return string The query var for this aggregation.
	 */
	public function get_query_var(): string {
		return $this->query_var;
	}

	/**
	 * Get the values for the query var for this aggregation.
	 *
	 * @return array The values for the query var.
	 */
	public function get_query_values(): array {
		return $this->query_values;
	}

	/**
	 * Determines whether the specified key is selected in the query for this
	 * aggregation.
	 *
	 * @param string $key The key to check.
	 *
	 * @return bool True if selected, false if not.
	 */
	protected function is_selected( string $key ): bool {
		return in_array( $key, $this->query_values, true );
	}

	/**
	 * Given a raw array of Elasticsearch aggregation buckets, parses it into
	 * Bucket objects and saves them in this object.
	 *
	 * @param array $buckets The raw aggregation buckets from Elasticsearch.
	 */
	abstract public function parse_buckets( array $buckets ): void;

	/**
	 * Get DSL for the aggregation to add to the Elasticsearch request object.
	 * Instructs Elasticsearch to return buckets for this aggregation in the
	 * response.
	 *
	 * @return array DSL fragment.
	 */
	abstract public function request(): array;
}
