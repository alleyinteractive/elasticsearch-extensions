<?php
/**
 * Elasticsearch Extensions Tests: SearchPress_Adapter_TestAPI class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

/**
 * SearchPress_Adapter_TestAPI class.
 *
 * @group searchpress
 */
class SearchPress_Adapter_TestAPI extends SearchPress_Adapter_UnitTestCase {

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	protected static $post_id;

	public function test_version() {
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+/', SP_API()->version() );
	}

	public function test_api_get() {
		self::$post_id = static::factory()->post->create( array( 'post_title' => 'lorem-ipsum', 'post_date' => '2009-07-01 00:00:00' ) );
		self::index( self::$post_id );

		$response = SP_API()->get( SP_API()->get_doc_type() . '/' . self::$post_id );
		$this->assertEquals( 'GET', SP_API()->last_request['params']['method'] );
		$this->assertEquals( '200', wp_remote_retrieve_response_code( SP_API()->last_request['response'] ) );
		$this->assertEquals( self::$post_id, $response->_source->post_id );

		SP_API()->get( SP_API()->get_doc_type() . "/foo" );
		$this->assertEquals( 'GET', SP_API()->last_request['params']['method'] );
		$this->assertEquals( '404', wp_remote_retrieve_response_code( SP_API()->last_request['response'] ) );
	}
}
