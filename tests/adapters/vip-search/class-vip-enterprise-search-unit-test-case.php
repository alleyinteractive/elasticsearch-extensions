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

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		static::flush();
	}

	public static function tearDownAfterClass(): void {
		static::flush();
		parent::tearDownAfterClass();
	}

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Flush the index.
	 *
	 * @todo implement.
	 *
	 * @see See Adapter_UnitTestCase::flush()
	 */
	protected static function flush(): void {
	}

	/**
	 * Force Elasticsearch to refresh its index to make content changes
	 * available to search.
	 *
	 * @todo implement
	 *
	 * @see See Adapter_UnitTestCase::refresh_index()
	 */
	protected static function refresh_index(): void {
	}

	/**
	 * Index one or more posts in Elasticsearch.
	 *
	 * @todo implement
	 *
	 * @see See Adapter_UnitTestCase::index()
	 * @see See Adapter_UnitTestCase::index_content()
	 *
	 * @param mixed $posts Can be a post ID, WP_Post object, SP_Post object, or
	 *                     an array of any of the above.
	 */
	protected static function index_content( $posts ): void {
	}
}
