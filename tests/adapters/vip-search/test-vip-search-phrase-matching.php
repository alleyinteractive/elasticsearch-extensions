<?php
/**
 * Elasticsearch Extensions Tests: VIP_Enterprise_Search_Adapter_TestAPI class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */
use Elasticsearch_Extensions\Factory;

it( 'tests that phrase matching matches phrase exactly', function () {
	// Adapter is loaded in the VIP Unit Test Case.
	// Enable phrase matching.
	elasticsearch_extensions()->load_adapter( Factory::vip_enterprise_search_adapter() );
	elasticsearch_extensions()->enable_phrase_matching();

	// WP_Query args.
	$args = [
		'ep_integrate'   => true,
		'order'          => 'DESC',
		'posts_per_page' => 10,
		'post_type'      => 'post',
		's'              => '"exact match"',
	];

	// Create the WP_Query instance to emulate search.
	$search_query = new WP_Query( $args );

	// Only 1 of the 3 posts should be found with hits for the words "exact match".
	$this->assertEquals( 1, $search_query->found_posts );

	// Disable phrase matching and test again.
	elasticsearch_extensions()->disable_phrase_matching();
	$search_query = new WP_Query( $args );
	// Expecting 2 posts to be found with hits for the words "exact match".
	$this->assertEquals( 2, $search_query->found_posts );
} );
