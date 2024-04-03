<?php
/**
 * Elasticsearch Extensions Tests: VIP_Enterprise_Search_Adapter_UnitTestCase class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

use \ElasticPress\Elasticsearch;
use \ElasticPress\Indexables;

/**
 * VIP_Enterprise_Search_Adapter_UnitTestCase class.
 */
class VIP_Enterprise_Search_Adapter_UnitTestCase extends Adapter_UnitTestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		static::flush();

		// Generate the mapping for the post index.
		$indexable = Indexables::factory()->get( 'post' );
		$index_name = $indexable->get_index_name();
		$mapping = $indexable->generate_mapping();
		Elasticsearch::factory()->put_mapping( $index_name, $mapping );

		// Create and index posts.
		$posts = self::create_sample_content();
		self::index_content( $posts );
	}

	protected function tearDown(): void {
		// Reset to default state. Includes features and config as well.
		self::flush();
		parent::tearDown();
	}

	/**
	 * Flush the index.
	 * Deletes the index created for the tests.
	 *
	 * @see See Adapter_UnitTestCase::flush()
	 */
	protected static function flush(): void {
		$ep = new Elasticsearch();
		$ep->delete_index( Indexables::factory()->get( 'post' )->get_index_name() );
	}

	/**
	 * Force Elasticsearch to refresh its index to make content changes
	 * available to search.
	 *
	 * @see See Adapter_UnitTestCase::refresh_index()
	 */
	protected static function refresh_index(): void {
		$ep = new Elasticsearch();
		$ep->refresh_indices();
	}

	/**
	 * Index one or more posts in Elasticsearch.
	 *
	 * @see See Adapter_UnitTestCase::index()
	 * @see See Adapter_UnitTestCase::index_content()
	 *
	 * @param mixed $posts Can be a post ID, WP_Post object, or
	 *                     an array of either of the above.
	 */
	protected static function index_content( $posts ): void {
		Indexables::factory()->get( 'post' )->bulk_index( $posts );

		// This isn't optimal, but the indexing requires time to complete, without it,
		// the tests run before the index operation is complete. Time depends on number of posts being indexed.
		sleep( 2 );
	}
}
