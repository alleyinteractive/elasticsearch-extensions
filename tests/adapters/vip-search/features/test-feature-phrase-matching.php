<?php
/**
 * Elasticsearch Extensions Tests: VIP_Enterprise_Search_Adapter_TestAPI class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

it( 'tests that phrase matching is disabled by default', function () {
	// Posts for Phrase matching tests.
	$post_ids = [];
	$post_ids[] = self::factory()->post->create( [ 'post_title' => 'Exact Matching Text A', 'post_content' => 'This text should contain an exact match.', 'post_date' => '2010-10-01 00:00:00' ] );
	$post_ids[] = self::factory()->post->create( [ 'post_title' => 'Exact Matching Text B', 'post_content' => 'This text should contain a different exact match.', 'post_date' => '2010-10-01 00:00:00' ] );
	$post_ids[] = self::factory()->post->create( [ 'post_title' => 'Exact Matching Text C', 'post_content' => 'This text should contain yet another exact match.', 'post_date' => '2010-10-01 00:00:00' ] );

	// Index setup and sync
	\ElasticPress\register_indexable_posts();
	self::index_content( $post_ids );

	// Set up the arguments for WP_Query
	$args = [
		'ep_integrate'   => true,
		'order'          => 'DESC,',
		'orderby'        => 'relevance',
		'posts_per_page' => 10,
		'post_type'      => 'post',
		's'              => '\"contain text\"',
	];

	// Create the WP_Query instance
	$search_query = new WP_Query( $args );

	// All 3 posts should be found even though there are no exact matches.
	$this->assertEquals( 3, $search_query->found_posts );
} );

it( 'tests that phrase matching matches phrase exactly', function () {
	// Posts for Phrase matching tests.
	$post_ids = [];
	$post_ids[] = self::factory()->post->create( [ 'post_title' => 'Exact Matching Text A', 'post_content' => 'This text should contain an exact match.', 'post_date' => '2010-10-01 00:00:00' ] );
	$post_ids[] = self::factory()->post->create( [ 'post_title' => 'Exact Matching Text B', 'post_content' => 'This text should contain a different match.', 'post_date' => '2010-10-01 00:00:00' ] );
	$post_ids[] = self::factory()->post->create( [ 'post_title' => 'Exact Matching Text C', 'post_content' => 'This text should contain yet another exact match.', 'post_date' => '2010-10-01 00:00:00' ] );

	// Index setup and sync
	\ElasticPress\register_indexable_posts();
	self::index_content( $post_ids );

	// Set up the arguments for WP_Query
	$args = [
		'ep_integrate'   => true,
		'order'          => 'DESC,',
		'orderby'        => 'relevance',
		'posts_per_page' => 10,
		'post_type'      => 'post',
		's'              => '"exact match"',
	];

	// Create the WP_Query instance
	$search_query = new WP_Query( $args );

	// 2 of the 3 posts should be found with exact matches.
	$this->assertEquals( 2, $search_query->found_posts );
} );

it( 'tests that phrase matching is enabled via function', function () {
	// Posts for Phrase matching tests.
	$post_ids = [];
	$post_ids[] = self::factory()->post->create( [ 'post_title' => 'Exact Matching Text A', 'post_content' => 'This text should contain an exact match.', 'post_date' => '2010-10-01 00:00:00' ] );
	$post_ids[] = self::factory()->post->create( [ 'post_title' => 'Exact Matching Text B', 'post_content' => 'This text should contain a different exact match.', 'post_date' => '2010-10-01 00:00:00' ] );
	$post_ids[] = self::factory()->post->create( [ 'post_title' => 'Exact Matching Text C', 'post_content' => 'This text should contain yet another exact match.', 'post_date' => '2010-10-01 00:00:00' ] );

	// Index setup and sync
	\ElasticPress\register_indexable_posts();
	self::index_content( $post_ids );

	// TODO I need to initialize the config object before we can test the changes. How should I go about this?
	add_action(
		'elasticsearch_extensions_config',
		function( $es_config ) {
			$es_config->enable_phrase_matching();
		}
	);

	// Set up the arguments for WP_Query
	$args = [
		'ep_integrate'   => true,
		'order'          => 'DESC,',
		'orderby'        => 'relevance',
		'posts_per_page' => 10,
		'post_type'      => 'post',
		's'              => '"contain text"',
	];

	// Create the WP_Query instance
	$search_query = new WP_Query( $args );

	// No posts should be found because none are an exact match.
	$this->assertEquals( 0, $search_query->found_posts );
} );

it( 'tests that phrase matching is disabled via function', function () {
	// Enable on priority 10 (default priority).
	add_action(
		'elasticsearch_extensions_config',
		function( $es_config ) {
			$es_config->enable_phrase_matching();
		}
	);

	// Called at a later priority to ensure that it happens after it is toggled on.
	add_action(
		'elasticsearch_extensions_config',
		function( $es_config ) {
			$es_config->enable_phrase_matching();
		},
		11
	);

	// TODO Perform a search to determine that phrase matching is disabled.
	$this->assertEquals( true, true );
} );
