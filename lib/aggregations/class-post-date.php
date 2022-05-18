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
	 * @return array|null An array containing timestamps for from and to. Null if no query is present.
	 */
	private function get_date_range( string $queried_date ) : ?array {
		switch ( $this->interval ) {
			case 'year':
				// Since we want all the months in a single year, set interval to months and offset by eleven months.
				$interval         = 'M';
				$offset           = 11;
				$to_unformatted   = $queried_date . '-12-01 00:00:00';
				break;
			case 'quarter':
				// TODO Write calculation logic for defining quarterly periods.
				break;
			case 'month':
				// TODO Define monthly period config.
				break;
			case 'week':
				// TODO Define weekly period config.
				break;
			case 'day':
				// TODO Define daily period config.
				break;
			case 'hour':
				// TODO Define hourly period config.
				break;
			case 'minute':
				// TODO Define "minute-ly" period config.
				break;
			default:
				break;
		}

		// If any required vars aren't set by the switch, bail.
		if ( ! isset( $interval, $offset, $to_unformatted ) ) {
			return [
				'from' => '',
				'to'   => '',
			];
		}

		// Calculate date range using DateTime and DateInterval.
		try {
			$date = new DateTime( $to_unformatted, wp_timezone() );
			$to   = $date->format( 'Y-m-d H:i:s' );
			$date->sub( new DateInterval( 'P' . $offset . $interval ) );
			$from = $date->format( 'Y-m-d H:i:s' );
			return [
				'from' => $from,
				'to'   => $to,
			];
		} catch ( Exception $e ) {
			return [
				'from' => '',
				'to'   => '',
			];
		}
	}

	/**
	 * Get DSL for filters that should be applied in the DSL in order to match
	 * the requested values.
	 *
	 * @return array|null DSL fragment or null if no filters to apply.
	 */
	public function filter(): ?array {
		return ! empty( $this->query_values[0] )
			? $this->dsl->range(
				'post_date',
				$this->get_date_range( (int) $this->query_values[0] )
			) : null;
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
			 *  Elasticsearch returns the key for date_histograms as a 64 bit number
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
