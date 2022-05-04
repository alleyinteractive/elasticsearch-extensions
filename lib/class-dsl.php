<?php
/**
 * Elasticsearch Extensions: DSL Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

/**
 * Handles DSL creation and modification for Elasticsearch queries.
 */
class DSL {

	/**
	 * A map of generic field names to specific field names based on the
	 * particular Elasticsearch plugin and mapping in use. Injected at
	 * construction time.
	 *
	 * @var array
	 */
	private array $field_map;

	/**
	 * Constructor function. Sets the field map from the adapter.
	 *
	 * @param array $field_map The field map to use.
	 */
	public function __construct( array $field_map ) {
		$this->field_map = $field_map;
	}

	/**
	 * Returns DSL for a date_histogram aggregation on a given field key at a
	 * given interval.
	 *
	 * @param string $aggregation  The aggregation slug to use for grouping.
	 * @param string $mapped_field The mapped field to use for the date aggregation.
	 * @param string $interval     The interval to aggregate (year, month, etc).
	 *
	 * @return array DSL fragment.
	 */
	public function aggregate_date_histogram( string $aggregation, string $mapped_field, string $interval ): array {
		// Negotiate format based on interval.
		switch ( $interval ) {
			case 'year':
				$format = 'yyyy';
				break;
			case 'quarter':
			case 'month':
				$format = 'yyyy-MM';
				break;
			case 'week':
			case 'day':
				$format = 'yyyy-MM-dd';
				break;
			default:
				$format = 'yyyy-MM-dd\'T\'HH:mm:ss.SSSZ';
				break;
		}

		/**
		 * Filters the Elasticsearch date format string used in the
		 * date_histogram aggregation.
		 *
		 * @param string $format       The format to use.
		 * @param string $interval     The interval to aggregate (year, month, etc).
		 * @param string $aggregation  The aggregation slug to use for grouping.
		 * @param string $mapped_field The mapped field to use for the date aggregation.
		 */
		$format = apply_filters( 'elasticsearch_extensions_aggregation_date_format', $format, $interval, $aggregation, $mapped_field );

		return [
			'date_histogram' => [
				'calendar_interval' => $interval,
				'field'             => $mapped_field,
				'format'            => $format,
				'order'             => [
					'_key' => 'desc',
				],
			],
		];
	}

	/**
	 * Returns DSL for a date_range aggregation on a given field key at a series
	 * of given ranges.
	 *
	 * @param string $mapped_field The mapped field to use for the date aggregation.
	 * @param array  $ranges       An array of arrays specifying the from and to dates.
	 *
	 * @return array DSL fragment.
	 */
	public function aggregate_date_range( string $mapped_field, array $ranges ): array {
		return [
			'date_range' => [
				'field'  => $mapped_field,
				'ranges' => $ranges,
			],
		];
	}

	/**
	 * Returns DSL for a terms aggregation on a given field key.
	 *
	 * @param string $aggregation  The aggregation slug to use for grouping.
	 * @param string $mapped_field The mapped field to use for the term aggregation.
	 *
	 * @return array DSL fragment.
	 */
	public function aggregate_terms( string $aggregation, string $mapped_field ): array {
		/**
		 * Allows the `size` property of a terms aggregation to be filtered. By
		 * default, Elasticsearch Extensions will return up to 1000 different
		 * terms on a terms aggregation, but this value can be increased for
		 * completeness or decreased for performance.
		 *
		 * @param int    $size        The maximum number of terms to return.
		 * @param string $aggregation The unique aggregation slug.
		 */
		$size = apply_filters( 'elasticsearch_extensions_aggregation_term_size', 1000, $aggregation );

		return [
			'terms' => [
				'field' => $mapped_field,
				'size'  => $size,
			],
		];
	}

	/**
	 * Build a "filter" bool fragment for an array of terms.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $field    Taxonomy field to check against.
	 * @param array  $values   Values to match.
	 *
	 * @return array DSL fragment.
	 */
	public function all_terms( string $taxonomy, string $field, array $values ): array {
		$field   = $this->map_tax_field( $taxonomy, $field );
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
	 * @param string $field Field to check for existence.
	 *
	 * @return array DSL fragment.
	 */
	public function exists( string $field ): array {
		return [
			'exists' => [
				'field' => $this->map_field( $field ),
			],
		];
	}

	/**
	 * Maps a field key to the Elasticsearch mapped field path.
	 *
	 * @param string $field The field key to map.
	 *
	 * @return string The mapped field reference.
	 */
	public function map_field( string $field ): string {
		return $this->field_map[ $field ] ?? $field;
	}

	/**
	 * Map a meta field. This will swap in the data type.
	 *
	 * @param  string $meta_key Meta key to map.
	 * @param  string $type Data type to map.
	 * @return string The mapped field.
	 */
	public function map_meta_field( string $meta_key, string $type = '' ): string {
		if ( ! empty( $type ) ) {
			return sprintf( $this->map_field( 'post_meta.' . $type ), $meta_key );
		} else {
			return sprintf( $this->map_field( 'post_meta' ), $meta_key );
		}
	}

	/**
	 * Map a taxonomy field. This will swap in the taxonomy name.
	 *
	 * @param  string $taxonomy Taxonomy to map.
	 * @param  string $field Field to map.
	 * @return string The mapped field.
	 */
	public function map_tax_field( string $taxonomy, string $field ): string {
		if ( 'post_tag' === $taxonomy ) {
			$field = str_replace( 'term_', 'tag_', $field );
		} elseif ( 'category' === $taxonomy ) {
			$field = str_replace( 'term_', 'category_', $field );
		}
		return sprintf( $this->map_field( $field ), $taxonomy );
	}

	/**
	 * Build a match DSL fragment.
	 *
	 * @param string $field The field key to check.
	 * @param string $value Value to match against.
	 * @param array  $args  Optional. Additional DSL arguments.
	 *
	 * @return array DSL fragment.
	 */
	public function match( string $field, string $value, array $args = [] ): array {
		$field = $this->map_field( $field );
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
	 * Build a missing DSL fragment (field must not exist).
	 *
	 * @param string $field The field to check for nonexistence.
	 * @param array  $args  Optional. Additional DSL arguments.
	 *
	 * @return array DSL fragment.
	 */
	public function missing( string $field, array $args = [] ): array {
		return [
			'bool' => [
				'must_not' => [
					'exists' => array_merge(
						[
							'field' => $this->map_field( $field ),
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
	 * @param array  $mapped_fields ES fields. Must already be mapped.
	 * @param string $query         Search phrase to query.
	 * @param array  $args          Optional. Additional DSL arguments.
	 *
	 * @return array DSL fragment.
	 */
	public function multi_match( array $mapped_fields, string $query, array $args = [] ): array {
		return [
			'multi_match' => array_merge(
				[
					'query'  => $query,
					'fields' => $mapped_fields,
				],
				$args
			),
		];
	}

	/**
	 * Build a range DSL fragment.
	 *
	 * @param string $field Field to compare against.
	 * @param array  $args  Range arguments for the field.
	 *
	 * @return array DSL fragment.
	 */
	public function range( string $field, array $args ): array {
		$field = $this->map_field( $field );
		return [
			'range' => [
				$field => $args,
			],
		];
	}

	/**
	 * Given a search term, return the query DSL for the search.
	 *
	 * @param string $s Search term.
	 *
	 * @return array DSL fragment.
	 */
	public function search_query( string $s ): array {
		/**
		 * Filter the Elasticsearch fields to search. The fields should already
		 * be mapped (use `$dsl->map_field()`, `$dsl->map_tax_field()`, or
		 * `$dsl->map_meta_field()` to map a field).
		 *
		 * @param array $fields A list of string fields to search against.
		 * @param DSL   $dsl    The DSL object, which provides map_field functionality.
		 */
		$fields = apply_filters(
			'elasticsearch_extensions_searchable_fields',
			[
				$this->map_field( 'post_title.analyzed' ) . '^3',
				$this->map_field( 'post_excerpt' ),
				$this->map_field( 'post_content.analyzed' ),
				$this->map_field( 'post_author.display_name' ),
				$this->map_meta_field( '_wp_attachment_image_alt', 'analyzed' ),
			],
			$this
		);

		return $this->multi_match(
			$fields,
			$s,
			[
				'operator' => 'and',
				'type'     => 'cross_fields',
			]
		);
	}

	/**
	 * Build a term or terms DSL fragment.
	 *
	 * @param string $field  The field to check against.
	 * @param mixed  $values Value(s) to compare.
	 * @param array  $args   Optional. Additional DSL arguments.
	 *
	 * @return array DSL fragment.
	 */
	public function terms( string $field, $values, array $args = [] ): array {
		$field = $this->map_field( $field );
		$type  = is_array( $values ) ? 'terms' : 'term';

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
