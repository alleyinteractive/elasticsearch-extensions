<?php
/**
 * Elasticsearch Extensions: Post_Date Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use DateInterval;
use DateTime;
use Elasticsearch_Extensions\DSL;
use Exception;

/**
 * Post date aggregation class. Responsible for building the DSL and requests
 * for aggregations as well as holding the result of the aggregation after a
 * response was received.
 */
class Post_Date extends Aggregation {

	/**
	 * The interval that the date aggregation should use. Defaults to year.
	 * Other options are 'quarter', 'month', 'week', 'day', 'hour', and
	 * 'minute'.
	 *
	 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-datehistogram-aggregation.html
	 *
	 * @var string
	 */
	protected string $interval = 'year';

	/**
	 * Configure the Post Date aggregation.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args ) {
		$this->label     = __( 'Date', 'elasticsearch-extensions' );
		$this->query_var = 'post_date';
		parent::__construct( $dsl, $args );
	}

	/**
	 * Get the date range for a queried date based on the interval.
	 * Since a post cannot be published at multiple DateTimes, only one (the first)
	 * queried post_date value is applied to the DSL.
	 *
	 * @param string $queried_date The queried date value.
	 *
	 * @return array An array containing timestamps for from and to. Null if no query is present.
	 */
	private function get_date_range( string $queried_date ): array {
		switch ( $this->interval ) {
			case 'year':
				// Queried year.
				// $queried_date format: 'Y'. e.g. 1970.
				$from = $this->format_date( $queried_date . '-01-01 00:00:00' );
				$to   = $this->get_formatted_to_date( $from, 'P1Y' );
				break;
			case 'quarter':
				// Queried quarter. Starts on first month of the quarter: 01, 04, 07, 10.
				// $queried_date format: 'Y-m'. e.g. 1970-11.
				$from = $this->format_date( $queried_date . '-01 00:00:00' );
				$to   = $this->get_formatted_to_date( $from, 'P3M' );
				break;
			case 'month':
				// Queried month.
				// $queried_date format: 'Y-m'. e.g. 1970-11.
				$from = $this->format_date( $queried_date . '-01 00:00:00' );
				$to   = $this->get_formatted_to_date( $from, 'P1M' );
				break;
			case 'week':
				// Queried week. Starts on Monday of first full week in the year.
				// $queried_date format: 'Y-m-d'. e.g. 1970-11-25.
				$from = $this->format_date( $queried_date . ' 00:00:00' );
				$to   = $this->get_formatted_to_date( $from, 'P6D' );
				break;
			case 'day':
				// Queried day.
				// $queried_date format: 'Y-m-d'. e.g. 1970-11-25.
				$from = $this->format_date( $queried_date . ' 00:00:00' );
				$to   = $this->get_formatted_to_date( $from, 'P1D' );
				break;
			case 'hour':
				// Queried hour.
				// $queried_date format: 'Y-m-d H:i:s'. e.g. 1970-11-25 22:45:45.
				$from = $this->format_date( $queried_date );
				$to   = $this->get_formatted_to_date( $from, 'PT1H' );
				break;
			case 'minute':
				// Queried minute.
				// $queried_date format: 'Y-m-d H:i:s'. e.g. 1970-11-25 22:45:45.
				$from = $this->format_date( $queried_date );
				$to   = $this->get_formatted_to_date( $from, 'PT1M' );
				break;
			default:
				break;
		}

		// If any required vars aren't properly configured by the switch, return empty.
		if ( empty( $from ) || empty( $to ) ) {
			$from = '';
			$to   = '';
		}

		return [
			'from' => $from,
			'to'   => $to,
		];
	}

	/**
	 * Format date string.
	 *
	 * @param string $date_str Date string.
	 * @return string
	 */
	protected function format_date( string $date_str ): string {
		try {
			$date = new DateTime( $date_str, wp_timezone() );

			return $date->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			return '';
		}
	}

	/**
	 * Get formatted "to date".
	 *
	 * @param string $from_date_str Formatted "from" datetime string.
	 * @param string $add_duration  Full "Duration" string value to add to the "from" date. Typically +1 interval.
	 * @param string $sub_duration  Full "Duration" string value to subtract from the "add_duration". Defaults to 1 second: 'PT1S'.
	 * @return string Formatted "to" date based on interval from "from" date.
	 */
	protected function get_formatted_to_date( string $from_date_str, string $add_duration, string $sub_duration = 'PT1S' ): string {
		try {
			$to = new DateTime( $from_date_str, wp_timezone() );

			return $to->add( new DateInterval( $add_duration ) )
					->sub( new DateInterval( $sub_duration ) )
					->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			return '';
		}
	}

	/**
	 * Gets an array of DSL representing each filter for this aggregation that
	 * should be applied in the query in order to match the requested values.
	 *
	 * @return array Array of DSL fragments to apply.
	 */
	public function filter(): array {
		return ! empty( $this->query_values[0] )
			? [
				$this->dsl->range(
					'post_date',
					$this->get_date_range( (string) $this->query_values[0] )
				),
			] : [];
	}

	/**
	 * Given a raw array of Elasticsearch aggregation buckets, parses it into
	 * Bucket objects and saves them in this object.
	 *
	 * @param array $buckets The raw aggregation buckets from Elasticsearch.
	 */
	public function parse_buckets( array $buckets ): void {
		$bucket_objects = [];
		foreach ( $buckets as $bucket ) {
			/*
			 * Elasticsearch returns the key for date_histograms as a 64-bit number
			 * representing a timestamp in ms. To standardize this key and label to
			 * something more familiar to users, use Elasticsearch's "key_as_string",
			 * which represents the key as a formatted date string that adheres to the
			 * "format" key passed to the Elasticsearch query.
			 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-datehistogram-aggregation.html#datehistogram-aggregation-keys
			*/
			$key = $bucket['key_as_string'] ?? $bucket['key'];

			/**
			 * Allows the label for a date aggregation to be filtered. For
			 * example, can be used to convert "2022-04" to "April 2022".
			 *
			 * @param string $label The label to use.
			 */
			$label            = apply_filters( 'elasticsearch_extensions_aggregation_date_label', $key );
			$bucket_objects[] = new Bucket(
				$key,
				$bucket['doc_count'],
				$label,
				$this->is_selected( $bucket['key'] ),
			);
		}
		$this->set_buckets( $bucket_objects );
	}

	/**
	 * Get DSL for the aggregation to add to the Elasticsearch request object.
	 * Instructs Elasticsearch to return buckets for this aggregation in the
	 * response.
	 *
	 * @return array DSL fragment.
	 */
	public function request(): array {
		return $this->dsl->aggregate_date_histogram(
			$this->query_var,
			$this->dsl->map_field( 'post_date' ),
			$this->interval
		);
	}
}
