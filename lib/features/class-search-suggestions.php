<?php
/**
 * Search_Suggestions class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Features;

use Elasticsearch_Extensions\Interfaces\Featurable;

/**
 * Search Suggestions feature.
 */
class Search_Suggestions implements Featurable {

	/**
	 * Whether to index search suggestions.
	 *
	 * @var bool
	 */
	private bool $is_active = false;

	/**
	 * Whether to make search suggestions available over the REST API.
	 *
	 * @var bool
	 */
	private bool $show_search_suggestions_in_rest = true;

	/**
	 * An optional array of post types to restrict search suggestions to.
	 *
	 * @var string[]
	 */
	private array $restricted_search_suggestions_post_types = [];

	public function activate( array $args = [] ): void {
		$args = wp_parse_args(
			$args,
			[
				'post_types'   => [],
				'show_in_rest' => true,
			]
		);

		$this->is_active                       = true;
		$this->show_search_suggestions_in_rest = (bool) $args['show_in_rest'];
		$this->restricted_search_suggestions_post_types = array_filter( (array) $args['post_types'] );
	}

	public function deactivate( array $args = [] ): void {
		$this->is_active = false;
	}

	/**
	 * Indicates if search suggestions are enabled or not.
	 *
	 * @return bool Whether search suggestions are enabled.
	 */
	public function is_active(): bool {
		return $this->is_active;
	}

	/**
	 * Gets the list of restricted post types for search suggestions.
	 *
	 * @return string[] The list of restricted post type slugs.
	 */
	public function get_restricted_search_suggestions_post_types(): array {
		return $this->restricted_search_suggestions_post_types;
	}

	/**
	 * Returns whether support for a REST API search handler for suggestions is enabled.
	 *
	 * @return bool
	 */
	public function is_show_search_suggestions_in_rest_enabled(): bool {
		return $this->is_active && $this->show_search_suggestions_in_rest;
	}

	/**
	 * Suggest posts that match the given search term.
	 *
	 * @param string $search Search string.
	 * @param array  $args   {
	 *     Optional. An array of arguments.
	 *
	 *     @type string[] $subtypes Limit suggestions to this subset of all post
	 *                              types that support search suggestions.
	 *     @type int      $page     Page of results.
	 *     @type int      $per_page Results per page. Default 10.
	 *     @type int[]    $include  Search within these post IDs.
	 *     @type int[]    $exclude  Exclude these post IDs from results.
	 * }
	 * @return int[] Post IDs in this page of results and total number of results.
	 */
	public function query_post_suggestions( string $search, array $args = [] ): array {
		return [ [], 0 ];
	}
}
