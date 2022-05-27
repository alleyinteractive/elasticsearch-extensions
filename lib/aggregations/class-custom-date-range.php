<?php
/**
 * Elasticsearch Extensions: Custom_Date_Range Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use DateInterval;
use DateTime;
use Elasticsearch_Extensions\DSL;
use Exception;

/**
 * Custom date range aggregation class. Responsible for building the DSL and
 * requests for aggregations as well as holding the result of the aggregation
 * after a response was received.
 */
class Custom_Date_Range extends Aggregation {

	/**
	 * Configure the Custom Date Range aggregation.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args ) {
		$this->label     = __( 'Custom Date Range', 'elasticsearch-extensions' );
		$this->query_var = 'custom_date_range';

		parent::__construct( $dsl, $args );
	}

	/**
	 * Get DSL for filters that should be applied in the DSL in order to match
	 * the requested values.
	 *
	 * @return array|null DSL fragment or null if no filters to apply.
	 */
	public function filter(): ?array {
		return ! empty( $this->query_values[0] )
			&& ! empty( $this->query_values[1] )
			&& is_string( $this->query_values[0] )
			&& is_string( $this->query_values[1] )
			&& count( $this->query_values ) === 2
				? $this->dsl->range(
					'post_date',
					$this->get_date_range( $this->query_values[0], $this->query_values[1] )
				) : null;
	}

	/**
	 * Given a start and end date in ISO-8601 format, constructs a from/to date
	 * range suitable for use in Elasticsearch DSL.
	 *
	 * @param string $from The start date, in ISO-8601 format.
	 * @param string $to   The end date, in ISO-8601 format.
	 *
	 * @return array An array containing timestamps for from and to.
	 */
	private function get_date_range( string $from, string $to ) : array {
		try {
			$from_datetime = DateTime::createFromFormat( DATE_W3C, $from );
			$to_datetime   = DateTime::createFromFormat( DATE_W3C, $to );
			return $from_datetime && $to_datetime
				? $this->dsl->build_range( $from_datetime, $to_datetime )
				: [];
		} catch ( Exception $e ) {
			return [];
		}
	}

	/**
	 * Since there are no aggregation parameters sent with the request, we do
	 * not need to parse the buckets on the response.
	 *
	 * @param array $buckets The raw aggregation buckets from Elasticsearch.
	 */
	public function parse_buckets( array $buckets ): void {}

	/**
	 * This aggregation works a bit differently than the others, since it's more
	 * of a filter based on user-supplied values, so we don't need to add any
	 * aggregation parameters to the request.
	 *
	 * @return array DSL fragment.
	 */
	public function request(): array {
		return [];
	}
}
