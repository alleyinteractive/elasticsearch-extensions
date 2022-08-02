<?php
/**
 * Elasticsearch Extensions Tests: SearchPress_Adapter_UnitTestCase class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

/**
 * SearchPress_Adapter_UnitTestCase class.
 */
class SearchPress_Adapter_UnitTestCase extends Adapter_UnitTestCase {

	/**
	 * SearchPress Settings.
	 *
	 * @var array
	 */
	protected static $sp_settings;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		static::flush();
		SP_Cron()->setup();
		wp_clear_scheduled_hook( 'sp_heartbeat' );

		// Don't auto-sync posts to ES.
		sp_remove_sync_hooks();
	}

	public static function tearDownAfterClass(): void {
		SP_Sync_Meta()->reset( 'save' );
		SP_Sync_Manager()->published_posts = false;
		static::flush();

		SP_Heartbeat()->record_pulse();
		wp_clear_scheduled_hook( 'sp_reindex' );
		wp_clear_scheduled_hook( 'sp_heartbeat' );

		parent::tearDownAfterClass();
	}

	public function setUp(): void {
		parent::setUp();
		self::$sp_settings = SP_Config()->get_settings();
	}

	public function tearDown(): void {
		$this->reset_post_types();
		$this->reset_taxonomies();
		$this->reset_post_statuses();
		SP_Config()->update_settings( self::$sp_settings );
		SP_Config()->post_types = null;
		SP_Config()->post_statuses = null;
		sp_searchable_post_types( true );
		sp_searchable_post_statuses( true );

		parent::tearDown();
	}

	/**
	 * Flush the index.
	 *
	 * @see See Adapter_UnitTestCase::flush()
	 */
	protected static function flush(): void {
		sp_index_flush_data();
	}

	/**
	 * Force Elasticsearch to refresh its index to make content changes
	 * available to search.
	 *
	 * @see See Adapter_UnitTestCase::refresh_index()
	 */
	protected static function refresh_index(): void {
		SP_API()->post( '_refresh' );
	}

	/**
	 * Index one or more posts in Elasticsearch.
	 *
	 * @see See Adapter_UnitTestCase::index()
	 * @see See Adapter_UnitTestCase::index_content()
	 *
	 * @param mixed $posts Can be a post ID, WP_Post object, SP_Post object, or
	 *                     an array of any of the above.
	 */
	protected static function index_content( $posts ): void {
		SP_API()->index_posts( $posts );
	}
}
