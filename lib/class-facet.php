<?php
/**
 * Class for Facets.
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

/**
 * Elasticsearch facet/aggregation.
 */
class Facet {
	/**
	 * The label for this facet, as provided by ES.
	 *
	 * @var string
	 */
	public string $label;

	/**
	 * This facet's buckets (results).
	 *
	 * @var array
	 */
	public array $buckets;

	/**
	 * The human-readable name for this facet section.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * The label for this facet section.
	 *
	 * @var string
	 */
	public string $title;

	/**
	 * The parsed type from the facet label.
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * The parsed subtype from the facet label, if applicable.
	 *
	 * @var string
	 */
	public string $subtype;

	/**
	 * The query var this facet.
	 *
	 * @var string
	 */
	public string $query_var;

	/**
	 * Build this facet object.
	 *
	 * @param string $label   The label as provided by ES.
	 * @param array  $buckets The buckets/results for the facet.
	 * @param string $name    Human readable name for the facet.
	 */
	public function __construct( string $label, array $buckets, $name = '' ) {
		$this->label   = $label;
		$this->buckets = $buckets;
		$this->name    = $name;
		$this->parse_type();
	}

	/**
	 * Parse the type (and subtype) for this facet.
	 */
	protected function parse_type() {
		if ( 'taxonomy_' === substr( $this->label, 0, 9 ) ) {
			$this->type    = 'taxonomy';
			$this->subtype = $this->query_var = substr( $this->label, 9 ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
		} else {
			$this->type = $this->query_var = $this->label; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
		}
	}

	/**
	 * Get the field (checkbox) name for this facet.
	 *
	 * @return string
	 */
	public function field_name(): string {
		return sprintf( 'facets[%s][]', $this->query_var );
	}

	/**
	 * Get the buckets for this facet.
	 *
	 * @return array
	 */
	public function buckets(): array {
		return $this->buckets;
	}

	/**
	 * Does this facet have any results?
	 *
	 * @return boolean
	 */
	public function has_buckets(): bool {
		return ! empty( $this->buckets );
	}

	/**
	 * Get the title for this facet section.
	 *
	 * @return string
	 */
	public function title(): string {
		if ( ! isset( $this->title ) ) {
			/**
			 * Filter the facet title.
			 *
			 * @param null|string $title   The facet title. Defaults to null.
			 * @param string      $label   Facet label.
			 * @param string      $type    Facet type.
			 * @param string      $subtype Facet Subtype.
			 */
			$this->title = apply_filters( 'elasticsearch_extensions_facet_title', '', $this->label, $this->type, $this->subtype );
			if ( null === $this->title ) {
				switch ( $this->type ) {
					case 'taxonomy':
						$taxonomy_object = get_taxonomy( $this->subtype );
						if ( ! empty( $taxonomy_object->labels->name ) ) {
							$this->title = $taxonomy_object->labels->name;
						} else {
							$this->title = $this->type;
						}
						break;

					case 'post_type':
						$this->title = __( 'Content Type', 'elasticsearch-extensions' );
						break;

					case 'post_date':
						$this->title = __( 'Date', 'elasticsearch-extensions' );
						break;

					case 'post_author':
						$this->title = __( 'Author', 'elasticsearch-extensions' );
						break;

					default:
						$this->title = $this->label;
						break;
				}
			}
		}

		return $this->title;
	}

	/**
	 * Get the label for an individual bucket.
	 *
	 * @param  array $bucket Bucket from ES.
	 * @return string
	 */
	public function get_label_for_bucket( array $bucket ): string {
		/**
		 * Filter the facet bucket label.
		 *
		 * @param string $bucket_label Bucket label. Defaults to null.
		 * @param string $label        Facet label.
		 * @param string $type         Facet type.
		 * @param string $subtype      Facet Subtype.
		 */
		$label = apply_filters( 'elasticsearch_extensions_facet_bucket_label', null, $bucket, $this->label, $this->type, $this->subtype );
		if ( null !== $label ) {
			return $label;
		}

		if ( isset( $bucket['key_as_string'] ) ) {
			return $bucket['key_as_string'];
		} else {
			switch ( $this->type ) {
				case 'taxonomy':
					$get_term_by = ( function_exists( 'wpcom_vip_get_term_by' ) ? 'wpcom_vip_get_term_by' : 'get_term_by' );
					$term        = call_user_func( $get_term_by, 'slug', $bucket['key'], $this->subtype );
					if ( ! empty( $term->name ) ) {
						return $term->name;
					}
					break;

				case 'post_type':
					$post_type_obj = get_post_type_object( $bucket['key'] );
					if ( ! empty( $post_type_obj->labels->name ) ) {
						return $post_type_obj->labels->name;
					}
					break;

				case 'post_date':
					if ( is_numeric( $bucket['key'] ) ) {
						return date( 'Y-m-d', absint( $bucket['key'] ) / 1000 ); //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					}
					break;

				case 'post_author':
					$name = get_the_author_meta( 'display_name', absint( $bucket['key'] ) );
					if ( $name ) {
						return $name;
					}
					break;
			}

			return $bucket['key'];
		}
	}

	/**
	 * Get the formatted field value.
	 *
	 * @param  mixed $value Raw value.
	 * @return mixed Formatted value.
	 */
	public function field_value( $value ) {
		if ( 'post_date' === $this->type ) {
			return absint( $value ) / 1000;
		}
		return $value;
	}

	/**
	 * Checked helper for the input checkbox. Wraps `checked()` and checks $_GET
	 * to keep the template clean.
	 *
	 * @param  mixed $value Current bucket value.
	 */
	public function checked( $value ) {
		if ( 'post_date' === $this->type ) {
			$value = absint( $value ) / 1000;
		}
		$values = ! empty( $_GET['facets'][ $this->query_var ] ) ? (array) $_GET['facets'][ $this->query_var ] : []; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		checked( in_array( $value, $values ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
	}
}
