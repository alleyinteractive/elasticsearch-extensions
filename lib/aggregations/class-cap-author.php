<?php
/**
 * Elasticsearch Extensions: Co-Authors Plus Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use Elasticsearch_Extensions\DSL;

/**
 * Co-Authors Plus authors aggregation class. Responsible for building the DSL
 * and requests for aggregations as well as holding the result of the
 * aggregation after a response was received.
 */
class CAP_Author extends Taxonomy {

	/**
	 * Configure the Co-Authors Plus Author aggregation.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args ) {
		$args['label']    = __( 'Author', 'elasticsearch-extensions' );
		$args['taxonomy'] = 'author';

		parent::__construct( $dsl, $args );
	}

	/**
	 * Given a Co-Authors Plus author slug (e.g., cap-alley) returns the
	 * requested field, or the best guess for the value of the requested field.
	 *
	 * @param string $cap_slug The CAP slug for the user (e.g., cap-alley).
	 * @param string $field    The field to request.
	 *
	 * @return string The best guess for the value of the requested field.
	 */
	private function get_author_field( string $cap_slug, string $field ): string {
		global $coauthors_plus;

		// Try to get the author using CAP.
		$author = $coauthors_plus->get_coauthor_by( 'user_nicename', preg_replace( '#^cap-#', '', $cap_slug ) );

		// Splitting the display name in a naïve way so we can use it as a fallback.
		$parts = explode( ' ', $author->display_name ?? '' );

		// Fork for field.
		switch ( $field ) {
			case 'first_name':
				return $author->first_name ?: array_shift( $parts ) ?: '';
			case 'last_name':
				return $author->last_name ?: array_pop( $parts ) ?: '';
			default:
				return $author->display_name;
		}
	}

	/**
	 * Given a raw array of Elasticsearch aggregation buckets, parses it into
	 * Bucket objects and saves them in this object.
	 *
	 * @param array $buckets The raw aggregation buckets from Elasticsearch.
	 */
	public function parse_buckets( array $buckets ): void {

		// Check for the existence of the global coauthors_plus object.
		global $coauthors_plus;
		if ( empty( $coauthors_plus ) ) {
			return;
		}

		// Loop over each term and map it to the CAP display name.
		$bucket_objects = [];
		foreach ( $buckets as $bucket ) {
			$coauthor_slug = preg_replace( '#^cap-#', '', $bucket['key'] );
			$coauthor      = $coauthors_plus->get_coauthor_by( 'user_nicename', $coauthor_slug );
			if ( ! empty( $coauthor ) ) {
				$bucket_objects[] = new Bucket(
					$bucket['key'],
					$bucket['doc_count'],
					$coauthor->display_name ?: $coauthor->user_login,
					$this->is_selected( $bucket['key'] ),
				);
			}
		}
		$this->set_buckets( $bucket_objects );
	}

	/**
	 * Apply special sorting rules for author fields.
	 *
	 * @param Bucket[] $buckets Buckets to be sorted.
	 *
	 * @return Bucket[] The sorted bucket array.
	 */
	protected function sort_buckets( array $buckets ): array {

		// If one of the special sort rules is in play, apply it.
		if ( in_array( $this->orderby, [ 'display_name', 'first_name', 'last_name' ], true ) ) {
			usort(
				$buckets,
				/**
				 * Compares two buckets to determine which should come first.
				 *
				 * @param Bucket $a The first bucket to compare.
				 * @param Bucket $b The second bucket to compare.
				 *
				 * @return int Less than one if a is before b, more than one if b is before a, or zero if they are equal.
				 */
				function ( Bucket $a, Bucket $b ): int {
					switch ( $this->orderby ) {
						case 'first_name':
						case 'last_name':
							$secondary_key = 'first_name' === $this->orderby ? 'last_name' : 'first_name';
							$a_first       = $this->get_author_field( $a->key, $this->orderby );
							$b_first       = $this->get_author_field( $b->key, $this->orderby );
							$c_first       = strcasecmp( $a_first, $b_first );

							// If the requested field isn't the same, we're done here.
							if ( 0 !== $c_first ) {
								return $c_first;
							}

							// Fall back to the secondary field if the first is the same (e.g., same last names).
							return strcasecmp(
								$this->get_author_field( $a->key, $secondary_key ),
								$this->get_author_field( $b->key, $secondary_key )
							);
						default:
							return strcasecmp(
								$this->get_author_field( $a->key, 'display_name' ),
								$this->get_author_field( $b->key, 'display_name' )
							);
					}
				}
			);
		}

		return parent::sort_buckets( $buckets );
	}
}
