<?php
/**
 * Elasticsearch Extensions Tests: VIP_Enterprise_Search_Adapter_UnitTestCase class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

/**
 * VIP_Enterprise_Search_Adapter_UnitTestCase class.
 */
class VIP_Enterprise_Search_Adapter_UnitTestCase extends Adapter_UnitTestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::flush();
	}

	public static function tear_down_after_class() {
		static::flush();
		parent::tear_down_after_class();
	}

	/**
	 * Flush the index.
	 *
	 * TODO implement.
	 *
	 * @see See Adapter_UnitTestCase::flush()
	 */
	protected static function flush(): void {
	}

	/**
	 * Force Elasticsearch to refresh its index to make content changes
	 * available to search.
	 *
	 * TODO implement
	 *
	 * @see See Adapter_UnitTestCase::refresh_index()
	 */
	protected static function refresh_index(): void {
	}

	/**
	 * Index one or more posts in Elasticsearch.
	 *
	 * TODO implement
	 *
	 * @see See Adapter_UnitTestCase::index()
	 * @see See Adapter_UnitTestCase::index_content()
	 *
	 * @param mixed $posts Can be a post ID, WP_Post object, or
	 *                     an array of any of the above.
	 */
	protected static function index_content( $posts ): void {
	}
}
