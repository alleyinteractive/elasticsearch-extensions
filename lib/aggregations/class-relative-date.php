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
	 * Whether to enable the use of a custom range or not. Defaults to false.
	 *
	 * @var bool
	 */
	protected bool $custom = false;

	/**
	 * The intervals that the relative date aggregation should use in whole
	 * numbers of days. Defaults to past 7, 30, and 365 days (week, month,
	 * year).
	 *
	 * @var int[]
	 */
	protected array $intervals = [ 7, 30, 365 ];

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
	 * Get DSL for filters that should be applied in the DSL in order to match
	 * the requested values.
	 *
	 * @return array|null DSL fragment or null if no filters to apply.
	 */
	public function filter(): ?array {
		// TODO.
		return null;
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
			$date = new DateTime( 'tomorrow', wp_timezone() );
			$to   = $date->format( 'Y-m-d H:i:s' );
			$date->sub( new DateInterval( 'P' . ( $offset + 1 ) . 'D' ) );
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
	 * Given a raw array of Elasticsearch aggregation buckets, parses it into
	 * Bucket objects and saves them in this object.
	 *
	 * @param array $buckets The raw aggregation buckets from Elasticsearch.
	 */
	public function parse_buckets( array $buckets ): void {
		$bucket_objects = [];
		foreach ( $buckets as $bucket ) {
			$label = 'custom' === $bucket['key']
				? __( 'Custom range', 'elasticsearch-extensions' )
				: sprintf(
					// translators: number of days.
					__( 'Past %s days', 'elasticsearch-extensions' ),
					$bucket['key']
				);
			$bucket_objects[] = new Bucket(
				$bucket['key'],
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

		// Convert the relative date ranges from configuration to DSL.
		$intervals = [];
		foreach ( $this->intervals as $interval ) {
			$intervals[] = array_merge(
				[ 'key' => $interval ],
				$this->get_relative_date( $interval )
			);
		}

		// If a custom date was requested, add it as well.
		if ( $this->custom && in_array( 'custom', $this->get_query_values(), true ) ) {
			$from = $this->extract_query_values( $this->get_query_var() . '_from' )[0] ?? '';
			$to   = $this->extract_query_values( $this->get_query_var() . '_to' )[0] ?? '';
			if ( ! empty( $from ) && ! empty( $to ) ) {
				$intervals[] = [
					'key'  => 'custom',
					'from' => $from,
					'to'   => $to,
				];
			}
		}

		return $this->dsl->aggregate_date_range(
			$this->dsl->map_field( 'post_date' ),
			$intervals,
		);
	}
}
