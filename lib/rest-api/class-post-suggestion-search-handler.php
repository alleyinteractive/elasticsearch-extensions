<?php
/**
 * Elasticsearch Extensions Adapters: Post Suggestion REST API Search Handler
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\REST_API;

use Elasticsearch_Extensions\Adapters\Adapter;
use WP_REST_Request;
use WP_REST_Search_Controller;
use WP_REST_Search_Handler;

/**
 * Suggest posts over the REST API.
 */
class Post_Suggestion_Search_Handler extends WP_REST_Search_Handler {
	/**
	 * Search adapter.
	 *
	 * @var Adapter
	 */
	private Adapter $adapter;

	/**
	 * Set up.
	 *
	 * @param Adapter $adapter Search adapter.
	 */
	public function __construct( Adapter $adapter ) {
		$this->adapter = $adapter;
		$this->type    = 'post-suggestion';

		/*
		 * Since adapters don't yet have a consistent way to supply the list of indexed post types,
		 * allow any public type to be requested, even though the response might not include it.
		 */
		$this->subtypes = array_values(
			get_post_types(
				[
					'public' => true,
				],
			),
		);
	}

	/**
	 * Searches the object type content for a given search request.
	 *
	 * @param WP_REST_Request $request Full REST request.
	 * @return array Associative array containing an `WP_REST_Search_Handler::RESULT_IDS` containing
	 *               an array of found IDs and `WP_REST_Search_Handler::RESULT_TOTAL` containing the
	 *               total count for the matching search results.
	 */
	public function search_items( WP_REST_Request $request ) {
		$args = [
			'subtypes' => $request[ WP_REST_Search_Controller::PROP_SUBTYPE ],
			'page'     => $request['page'],
			'per_page' => $request['per_page'],
			'include'  => $request['include'],
			'exclude'  => $request['exclude'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		];

		if ( in_array( WP_REST_Search_Controller::TYPE_ANY, $args['subtypes'], true ) ) {
			unset( $args['subtypes'] );
		}

		[ $ids, $total ] = $this->adapter->query_post_suggestions( (string) $request['search'], $args );

		return [
			self::RESULT_IDS   => $ids,
			self::RESULT_TOTAL => $total,
		];
	}

	/**
	 * Prepares the search result for a given ID.
	 *
	 * @param int|string $id     Item ID.
	 * @param array      $fields Fields to include for the item.
	 * @return array Associative array containing all fields for the item.
	 */
	public function prepare_item( $id, array $fields ) {
		$item = [];
		$post = get_post( $id );

		if ( $post ) {
			if ( rest_is_field_included( WP_REST_Search_Controller::PROP_ID, $fields ) ) {
				$item[ WP_REST_Search_Controller::PROP_ID ] = $id;
			}

			if ( rest_is_field_included( WP_REST_Search_Controller::PROP_TITLE, $fields ) ) {
				$item[ WP_REST_Search_Controller::PROP_TITLE ] = $post->post_title;
			}

			if ( rest_is_field_included( WP_REST_Search_Controller::PROP_URL, $fields ) ) {
				$item[ WP_REST_Search_Controller::PROP_URL ] = get_permalink( $post );
			}

			if ( rest_is_field_included( WP_REST_Search_Controller::PROP_TYPE, $fields ) ) {
				$item[ WP_REST_Search_Controller::PROP_TYPE ] = $this->get_type();
			}

			if ( rest_is_field_included( WP_REST_Search_Controller::PROP_SUBTYPE, $fields ) ) {
				$item[ WP_REST_Search_Controller::PROP_SUBTYPE ] = $post->post_type;
			}
		}

		return $item;
	}

	/**
	 * Prepares links for the search result of a given ID.
	 *
	 * @param int|string $id Item ID.
	 * @return array Links for the given item.
	 */
	public function prepare_item_links( $id ) {
		$links = [];

		$item_route = rest_get_route_for_post( $id );

		if ( $item_route ) {
			$links['self'] = [
				'href'       => rest_url( $item_route ),
				'embeddable' => true,
			];
		}

		return $links;
	}
}
