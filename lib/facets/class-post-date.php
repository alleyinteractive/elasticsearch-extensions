<?php
/**
 * Post date facet type.
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Facets;

use Elasticsearch_Extensions\DSL;

/**
 * Post date facet type. Responsible for building
 * the DSL and requests for post date facets.
 */
class Post_Date extends Facet_Type {
	/**
	 * The query var this facet should use.
	 *
	 * @var string
	 */
	protected string $query_var = 'post_date';

	/**
	 * Calendar intervals.
	 *
	 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-datehistogram-aggregation.html
	 * @var string
	 */
	private static string $calendar_interval = 'month';

	/**
	 * Set calendar interval.
	 *
	 * @param string $calendar_interval
	 * @return void
	 */
	public static function set_calendar_interval( string $calendar_interval ) {
		self::$calendar_interval = $calendar_interval;
	}

	/**
	 * Build the facet request.
	 *
	 * @return array
	 */
	public function request(): array {
		return [
			'post_date' => [
				'date_histogram' => [
					'field'             => $this->controller->map_field( 'post_date' ),
					'calendar_interval' => self::$calendar_interval,
					'format'            => 'yyyy-MM',
					'min_doc_count'     => 2,
					'order'             => [
						'_key' => 'desc',
					],
				],
			],
		];
	}

	/**
	 * Get the request filter DSL clause.
	 *
	 * TODO update DSL for use with other calendar intervals.
	 *
	 * @param  array $values Values to pass to filter.
	 * @return array
	 */
	public function filter( array $values ): array {
		$should = [];
		foreach ( $values as $date ) {
			$gte      = date( 'Y-m-d H:i:s', $date ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$lt       = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d', $date ) . ' + 1 month' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$should[] = DSL::range(
				'post_date',
				[
					'gte' => $gte,
					'lt'  => $lt,
				]
			);
		}

		return [ 'bool' => [ 'should' => $should ] ];
	}
}
