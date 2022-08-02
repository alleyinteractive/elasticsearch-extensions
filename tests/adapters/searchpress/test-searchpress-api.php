<?php
/**
 * Elasticsearch Extensions Tests: SearchPress_Adapter_TestAPI class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */


it( 'should load the SP version', function () {
	$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+/', SP_API()->version() );

	var_dump( $this );
} );

// it( 'should test the get', function () {
// 	$post_id = $this->factory->post->create( array( 'post_title' => 'lorem-ipsum', 'post_date' => '2009-07-01 00:00:00' ) );
// 	$this::index( $post_id );

// 	$response = SP_API()->get( SP_API()->get_doc_type() . '/' . $post_id );
// 	$this->assertEquals( 'GET', SP_API()->last_request['params']['method'] );
// 	$this->assertEquals( '200', wp_remote_retrieve_response_code( SP_API()->last_request['response'] ) );
// 	$this->assertEquals( $post_id, $response->_source->post_id );

// 	SP_API()->get( SP_API()->get_doc_type() . "/foo" );
// 	$this->assertEquals( 'GET', SP_API()->last_request['params']['method'] );
// 	$this->assertEquals( '404', wp_remote_retrieve_response_code( SP_API()->last_request['response'] ) );
// } );
