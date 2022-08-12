<?php
/**
 * Elasticsearch Extensions: Relative_Date Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use DateInterval;
use DateTime;
use Elasticsearch_Extensions\DSL;
use Exception;

/**
 * Relative date aggregation class. Responsible for building the DSL and
 * requests for aggregations as well as holding the result of the aggregation
 * after a response was received.
 */
class Relative_Date extends Aggregation {

	/**
	 * The intervals that the relative date aggregation should use in whole
	 * numbers of days. Defaults to past 7, 30, and 90 days (week, month,
	 * quarter).
	 *
	 * @var int[]
	 */
	protected array $intervals = [ 7, 30, 90 ];

	/**
	 * Configure the Relative Date aggregation.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args ) {
		$this->label     = __( 'Relative Date', 'elasticsearch-extensions' );
		$this->query_var = 'relative_date';

		parent::__construct( $dsl, $args );
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
					$this->get_relative_date( (int) $this->query_values[0] )
				),
			] : [];
	}

	/**
	 * Given an offset from the current day, constructs a relative date string
	 * in the site's configured timezone.
	 *
	 * @param int $offset The number of days to offset from the current day.
	 *
	 * @return array An array containing timestamps for from and to.
	 */
	private function get_relative_date( int $offset ) : array {
		try {
			$to   = new DateTime( 'tomorrow', wp_timezone() );
			$from = new DateTime( 'tomorrow', wp_timezone() );
			$from->sub( new DateInterval( 'P' . ( $offset + 1 ) . 'D' ) );
			return $this->dsl->build_range( $from, $to );
		} catch ( Exception $e ) {
			return [];
		}
	}

	/**
	 * Overrides the default input function for aggregations to use a select
	 * input for this aggregation.
	 */
	public function input(): void {
		$this->select();
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
			$bucket_objects[] = new Bucket(
				$bucket['key'],
				$bucket['doc_count'],
				sprintf(
					// translators: number of days.
					__( 'Past %s days', 'elasticsearch-extensions' ),
					$bucket['key']
				),
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

		// Convert the relative date ranges from configuration to DSL.
		$intervals = [];
		foreach ( $this->intervals as $interval ) {
			$intervals[] = array_merge(
				[ 'key' => (string) $interval ],
				$this->get_relative_date( $interval )
			);
		}

		return $this->dsl->aggregate_date_range(
			$this->dsl->map_field( 'post_date' ),
			$intervals,
		);
	}
}
