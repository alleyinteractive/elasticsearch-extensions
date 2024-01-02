<?php
/**
 * Elasticsearch Extensions Tests: VIP_Enterprise_Search_Adapter_TestAPI class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */
use Elasticsearch_Extensions\Factory;

it( 'tests that phrase matching matches phrase exactly', function () {
	// Posts for Phrase matching tests.
	// TODO Move all post creation to bootstrap?
	$posts = [];
	$posts[] = self::factory()->post->create( [ 'post_title' => 'Phrase Matching Text A', 'post_content' => 'This text should contain an exact match.', 'post_date' => '2010-10-01 00:00:00' ] );
	$posts[] = self::factory()->post->create( [ 'post_title' => 'Phrase Matching Text B', 'post_content' => 'This exact text should not contain a match.', 'post_date' => '2010-10-01 00:00:00' ] );
	$posts[] = self::factory()->post->create( [ 'post_title' => 'Phrase Matching Text C', 'post_content' => 'This text should contain yet another exact match.', 'post_date' => '2010-10-01 00:00:00' ] );
	self::index_content( $posts );

	// Adapter is loaded in the VIP Unit Test Case.
	// Enable phrase matching.
	elasticsearch_extensions()->load_adapter( Factory::vip_enterprise_search_adapter() );
	elasticsearch_extensions()->enable_phrase_matching();

	// TODO DRY these args up for the two assertions below.
	// Set up the arguments for WP_Query
	$args = [
		'order'          => 'DESC,',
		'posts_per_page' => 10,
		'post_type'      => 'post',
		's'              => '"exact match"',
	];

	// Create the WP_Query instance.
	$search_query = new WP_Query( $args );

	// 2 of the 3 posts should be found with exact matches.
	$this->assertEquals( 16, $search_query->found_posts );

	// Ensure that EP ran.
	$ep_ran = false;
	add_action( 'ep_valid_response', function () use ( &$ep_ran ) {
		$ep_ran = true;
	} );
	$this->assertEquals( false, $ep_ran );

	$args = [
		'ep_integrate'   => true,
		'order'          => 'DESC,',
		'posts_per_page' => 10,
		'post_type'      => 'post',
		's'              => 'exact match',
	];

	// Create the WP_Query instance
	// TODO Step through to determine where these posts are coming from. This seems to be pulling from cache.
	$search_query = new WP_Query( $args );

	// 3 of the 3 posts should be found with hits for the words "exact" and "match".
	$this->assertEquals( 3, $search_query->found_posts );
} );
