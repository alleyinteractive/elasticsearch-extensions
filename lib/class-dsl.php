<?php
/**
 * ES integration.
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

/**
 * Elasticsearch controller.
 */
class DSL {

	/**
	 * ES Controller.
	 *
	 * @var Controller
	 */
	public static Controller $controller;

	/**
	 * Build a "filter" bool fragment for an array of terms.
	 *
	 * @param  string $taxonomy Taxonomy.
	 * @param  string $field  WP field.
	 * @param  array  $values Values to match.
	 * @return array DSL fragment.
	 */
	public static function all_terms( string $taxonomy, string $field, array $values ): array {
		$field = self::$controller->map_tax_field( $taxonomy, $field );
		$queries = [];
		foreach ( $values as $value ) {
			$queries[] = [
				'term' => [
					$field => $value,
				],
			];
		}

		return [
			'bool' => [
				'filter' => $queries,
			],
		];
	}

	/**
	 * Build an exists DSL fragment.
	 *
	 * @param  string $field WP field.
	 * @return array DSL fragment.
	 */
	public static function exists( string $field ): array {
		return [
			'exists' => [
				'field' => self::$controller->map_field( $field ),
			],
		];
	}

	/**
	 * Build a match DSL fragment.
	 *
	 * @param  string $field WP field.
	 * @param  string $value Value to match against.
	 * @param  array  $args  Optional. Additional DSL arguments.
	 * @return array DSL fragment.
	 */
	public static function match( $field, $value, $args = [] ) {
		$field = self::$controller->map_field( $field );
		return [
			'match' => array_merge(
				[
					$field => $value,
				],
				$args
			),
		];
	}

	/**
	 * Build a missing DSL fragment.
	 *
	 * @param  string $field ES field.
	 * @param  array  $args  Optional. Additional DSL arguments.
	 * @return array DSL fragment.
	 */
	public static function missing( $field, $args = [] ) {
		return [
			'bool' => [
				'must_not' => [
					'exists' => array_merge(
						[
							'field' => self::$controller->map_field( $field ),
						],
						$args
					),
				],
			],
		];
	}

	/**
	 * Build a multi_match DSL fragment.
	 *
	 * @param  array  $fields ES Fields, must be mapped.
	 * @param  string $query  Search phrase to query.
	 * @param  array  $args   Optional. Additional DSL arguments.
	 * @return array DSL fragment.
	 */
	public static function multi_match( array $fields, string $query, array $args = [] ): array {
		return [
			'multi_match' => array_merge(
				[
					'query'  => $query,
					'fields' => $fields,
				],
				$args
			),
		];
	}

	/**
	 * Build a range DSL fragment.
	 *
	 * @param  string $field WP field.
	 * @param  array  $args  Optional. Additional DSL arguments.
	 * @return array  DSL fragment.
	 */
	public static function range( $field, $args ) {
		$field = self::$controller->map_field( $field );
		return [
			'range' => [
				$field => $args,
			],
		];
	}

	/**
	 * Given a search term, return the query DSL for the search.
	 *
	 * @param  string $s Search term.
	 * @return array DSL fragment.
	 */
	public static function search_query( string $s ): array {
		/**
		 * Filter the Elasticsearch fields to search. The fields should already
		 * be mapped (use `Controller::map_field()`, `Controller::map_tax_field()`, or
		 * `Controller::map_meta_field()` to map a field).
		 *
		 * @var array
		 */
		$fields = apply_filters(
			'es_extensions_searchable_fields',
			[
				self::$controller->map_field( 'post_title.analyzed' ) . '^3',
				self::$controller->map_field( 'post_excerpt' ),
				self::$controller->map_field( 'post_content.analyzed' ),
				self::$controller->map_field( 'post_author.display_name' ),
				self::$controller->map_meta_field( '_wp_attachment_image_alt', 'analyzed' ),
			]
		);

		return self::multi_match(
			$fields,
			$s,
			[
				'operator' => 'and',
				'type'     => 'cross_fields',
			]
		);
	}

	/**
	 * Setter for the controller.
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public static function set_es_controller( Controller $controller ) {
		self::$controller = $controller;
	}

	/**
	 * Build a term or terms DSL fragment.
	 *
	 * @param  string $field  WP Field, e.g. post_type, post_meta, etc.
	 * @param  mixed  $values Value(s) to query/filter.
	 * @param  array  $args   Optional. Additional DSL arguments.
	 * @return array DSL fragment.
	 */
	public static function terms( $field, $values, $args = [] ) {
		$field = self::$controller->map_field( $field );
		$type = is_array( $values ) ? 'terms' : 'term';

		return [
			$type => array_merge(
				[
					$field => $values,
				],
				$args
			),
		];
	}
}
