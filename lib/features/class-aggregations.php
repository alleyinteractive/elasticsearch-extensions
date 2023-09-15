<?php
/**
 * Elasticsearch Extensions: Aggregations class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Features;

use Elasticsearch_Extensions\Aggregations\Aggregation;
use Elasticsearch_Extensions\Aggregations\CAP_Author;
use Elasticsearch_Extensions\Aggregations\Custom_Date_Range;
use Elasticsearch_Extensions\Aggregations\Post_Date;
use Elasticsearch_Extensions\Aggregations\Post_Type;
use Elasticsearch_Extensions\Aggregations\Relative_Date;
use Elasticsearch_Extensions\Aggregations\Taxonomy;
use Elasticsearch_Extensions\Interfaces\Featurable;

class Aggregations implements Featurable {
	public function is_active(): bool {
		// TODO: Implement is_active() method.
	}

	public function activate(): void {
		// TODO: Implement activate() method.
	}

	public function deactivate(): void {
		// TODO: Implement deactivate() method.
	}

	/**
	 * Stores aggregation data from the Elasticsearch response.
	 *
	 * @var Aggregation[]
	 */
	private array $aggregations = [];

	/**
	 * Adds an Aggregation to the list of active aggregations.
	 *
	 * @param Aggregation $aggregation The aggregation to add.
	 */
	private function add_aggregation( Aggregation $aggregation ): void {
		$this->aggregations[ $aggregation->get_query_var() ] = $aggregation;
	}

	/**
	 * Adds a new Co-Authors Plus author aggregation to the list of active aggregations.
	 *
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_cap_author_aggregation( array $args = [] ): void {
		$this->add_aggregation( new CAP_Author( $this->dsl, $args ) );
	}

	/**
	 * Adds a new custom date range aggregation to the list of active aggregations.
	 *
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_custom_date_range_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Custom_Date_Range( $this->dsl, $args ) );
	}

	/**
	 * Adds a new post date aggregation to the list of active aggregations.
	 *
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_post_date_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Post_Date( $this->dsl, $args ) );
	}

	/**
	 * Adds a new post type aggregation to the list of active aggregations.
	 *
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_post_type_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Post_Type( $this->dsl, $args ) );
	}

	/**
	 * Adds a new relative date aggregation to the list of active aggregations.
	 *
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_relative_date_aggregation( array $args = [] ): void {
		$this->add_aggregation( new Relative_Date( $this->dsl, $args ) );
	}

	/**
	 * Adds a new taxonomy aggregation to the list of active aggregations.
	 *
	 * @param string $taxonomy The taxonomy slug to add (e.g., category, post_tag).
	 * @param array  $args     Optional. Additional arguments to pass to the aggregation.
	 */
	public function add_taxonomy_aggregation( string $taxonomy, array $args = [] ): void {
		$this->add_aggregation( new Taxonomy( $this->dsl, wp_parse_args( $args, [ 'taxonomy' => $taxonomy ] ) ) );
	}

	/**
	 * Get an aggregation by its label.
	 *
	 * @param string $label Aggregation label.
	 *
	 * @return ?Aggregation The aggregation, if found, or null if not.
	 */
	public function get_aggregation_by_label( string $label ): ?Aggregation {
		foreach ( $this->aggregations as $aggregation ) {
			if ( $label === $aggregation->get_label() ) {
				return $aggregation;
			}
		}

		return null;
	}

	/**
	 * Get an aggregation by its query var.
	 *
	 * @param string $query_var Aggregation query var.
	 *
	 * @return ?Aggregation The aggregation, if found, or null if not.
	 */
	public function get_aggregation_by_query_var( string $query_var ): ?Aggregation {
		foreach ( $this->aggregations as $aggregation ) {
			if ( $query_var === $aggregation->get_query_var() ) {
				return $aggregation;
			}
		}

		return null;
	}

	/**
	 * Get the aggregation configuration.
	 *
	 * @return Aggregation[]
	 */
	public function get_aggregations(): array {
		return $this->aggregations;
	}

	/**
	 * Parses aggregations from an aggregations object in an Elasticsearch
	 * response into the loaded aggregations.
	 *
	 * @param array $aggregations Aggregations from the Elasticsearch response.
	 */
	protected function parse_aggregations( array $aggregations ): void {
		foreach ( $aggregations as $aggregation_key => $aggregation ) {
			if ( isset( $this->aggregations[ $aggregation_key ] ) ) {
				$this->aggregations[ $aggregation_key ]->parse_buckets( $aggregation['buckets'] ?? [] );
			}
		}
	}
}
