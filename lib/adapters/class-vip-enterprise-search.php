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
	 *
	 * @return array New request arguments.
	 */
	public function filter_ep_query_request_args( $request_args, $path, $index, $type ): array {
		// Try to convert the request body to an array so we can work with it.
		$dsl = json_decode( $request_args['body'], true );
		if ( ! is_array( $dsl ) ) {
			return $request_args;
		}

		// Add our aggregations.
		if ( $this->get_aggregate_post_types() ) {
			$dsl['aggs']['post_type'] = [
				'terms' => [
					'field' => 'post_type.raw',
				],
			];
		}

		// Re-encode the body into the request args.
		$request_args['body'] = wp_json_encode( $dsl );

		return $request_args;
	}

	/**
	 * Setup function. Registers action and filter hooks.
	 */
	public function setup(): void {
		add_filter( 'ep_query_request_args', [ $this, 'filter_ep_query_request_args' ], 10, 4 );
	}
}
