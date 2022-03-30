<?php
/**
 * Elasticsearch Extensions Adapters: VIP Enterprise Search Adapter
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Adapters;

/**
 * An adapter for WordPress VIP Enterprise Search.
 *
 * @package Elasticsearch_Extensions
 */
class VIP_Enterprise_Search extends Adapter {

	/**
	 * Filters ElasticPress request query args to apply registered customizations.
	 *
	 * @param array  $request_args Request arguments.
	 * @param string $path         Request path.
	 * @param string $index        Index name.
	 * @param string $type         Index type.
	 * @param array  $query        Prepared Elasticsearch query.
	 * @param array  $query_args   Query arguments.
	 * @param mixed  $query_object Could be WP_Query, WP_User_Query, etc.
	 *
	 * @return array New request arguments.
	 */
	public function filter_ep_query_request_args( $request_args, $path, $index, $type, $query, $query_args, $query_object ): array {
		return $request_args;
	}

	/**
	 * Setup function. Registers action and filter hooks.
	 */
	public function setup(): void {
		add_filter( 'ep_query_request_args', [ $this, 'filter_ep_query_request_args' ], 10, 7 );
	}
}
