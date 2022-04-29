<?php
/**
 * Elasticsearch Extensions: Post_Date Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use Elasticsearch_Extensions\DSL;

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
