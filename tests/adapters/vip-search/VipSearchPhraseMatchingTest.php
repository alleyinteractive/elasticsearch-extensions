<?php
/**
 * Elasticsearch Extensions Tests: VIP_Enterprise_Search_Adapter_TestAPI class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */
use Elasticsearch_Extensions\Factory;

it( 'tests that phrase matching matches phrase exactly.', function () {
	// Adapter is loaded in the VIP Unit Test Case.
	// Enable phrase matching.
	elasticsearch_extensions()->load_adapter( Factory::vip_enterprise_search_adapter() );
	elasticsearch_extensions()->enable_phrase_matching();

	// Run a search for the phrase "exact match" and get the global wp_query.
	$this->get( '/?s="exact match"' );
	global $wp_query;

	// Only 1 of the 3 posts should be found with hits for the words "exact match".
	$this->assertEquals( 1, $wp_query->found_posts );
	$this->assertEquals( $wp_query->posts[0]->post_title, 'Phrase Matching Text A' );
} );

/**
 * Test that phrase matching is enabled.
 *
 * TODO Test that the behavior is also disabled.
 * @see https://github.com/alleyinteractive/elasticsearch-extensions/issues/78
 */
it( 'tests that disabling phrase matching disables the feature.', function () {
	// Adapter is loaded in the VIP Unit Test Case.
	// Enable phrase matching.
	elasticsearch_extensions()->load_adapter( Factory::vip_enterprise_search_adapter() );
	elasticsearch_extensions()->enable_phrase_matching();

	// Disable the feature.
	elasticsearch_extensions()->disable_phrase_matching();
	// Test the setter.
	$this->assertFalse( elasticsearch_extensions()->get_enable_phrase_matching() );
} );

it( 'tests that enabling phrase matching enables the feature.', function () {
	// Adapter is loaded in the VIP Unit Test Case.
	// Enable phrase matching.
	elasticsearch_extensions()->load_adapter( Factory::vip_enterprise_search_adapter() );
	elasticsearch_extensions()->enable_phrase_matching();

	$this->assertTrue( elasticsearch_extensions()->get_enable_phrase_matching() );
} );
