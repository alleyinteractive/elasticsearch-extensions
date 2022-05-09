<?php
/**
 * Elasticsearch Extensions: Aggregation Bucket Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

/**
 * A class to represent a bucket in an aggregation response.
 *
 * @package Elasticsearch_Extensions
 */
class Bucket {

	/**
	 * The number of results in this bucket.
	 *
	 * @var int
	 */
	public int $count;

	/**
	 * The machine-readable key for this bucket (e.g., post type slug).
	 *
	 * @var string
	 */
	public string $key;

	/**
	 * The human-readable label for this bucket.
	 *
	 * @var string
	 */
	public string $label;

	/**
	 * Whether this bucket is selected or not.
	 *
	 * @var bool
	 */
	public bool $selected;

	/**
	 * Constructor. Sets property values.
	 *
	 * @param string $key      The machine-readable key for this bucket (e.g., post type slug).
	 * @param int    $count    Optional. The number of results in this bucket. Defaults to 0.
	 * @param string $label    Optional. The human-readable label for this bucket. Defaults to $value.
	 * @param bool   $selected Optional. Whether this bucket is selected or not. Defaults to false.
	 */
	public function __construct( string $key, int $count = 0, string $label = '', bool $selected = false ) {
		$this->key      = $key;
		$this->count    = $count;
		$this->label    = $label ?: $key;
		$this->selected = $selected;
	}
}
