<?php
/**
 * Elasticsearch Extensions Tests: Date range DSL keyword tests.
 *
 * Covers the deprecated-keyword fix in `DSL::build_range()` and
 * `Post_Date::get_date_range()` (via `Post_Date::filter()`), which switched
 * date-range fragments from `from`/`to` to `gte`/`lte`.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

use Elasticsearch_Extensions\Aggregations\Post_Date;
use Elasticsearch_Extensions\DSL;

uses( \Mantle\Testkit\Test_Case::class );

it( 'DSL::build_range emits gte and lte for both dates', function () {
	$dsl   = new DSL( [ 'post_date' => 'post_date' ] );
	$range = $dsl->build_range(
		new DateTime( '2024-01-01 00:00:00' ),
		new DateTime( '2024-01-31 23:59:59' )
	);

	expect( $range )->toBe( [
		'gte' => '2024-01-01 00:00:00',
		'lte' => '2024-01-31 23:59:59',
	] );
} );

it( 'DSL::build_range emits only gte when no upper bound is given', function () {
	$dsl = new DSL( [ 'post_date' => 'post_date' ] );

	expect( $dsl->build_range( new DateTime( '2024-01-01 00:00:00' ), null ) )
		->toBe( [ 'gte' => '2024-01-01 00:00:00' ] );
} );

it( 'DSL::build_range emits only lte when no lower bound is given', function () {
	$dsl = new DSL( [ 'post_date' => 'post_date' ] );

	expect( $dsl->build_range( null, new DateTime( '2024-01-31 23:59:59' ) ) )
		->toBe( [ 'lte' => '2024-01-31 23:59:59' ] );
} );

it( 'Post_Date::filter wraps the date range in a range query using gte/lte', function () {
	$_GET['fs']['post_date'] = [ '2024' ];

	$dsl        = new DSL( [ 'post_date' => 'post_date' ] );
	$aggregation = new Post_Date( $dsl, [] );

	$filters = $aggregation->filter();

	expect( $filters )->toHaveCount( 1 )
		->and( $filters[0] )->toHaveKey( 'range.post_date.gte' )
		->and( $filters[0] )->toHaveKey( 'range.post_date.lte' )
		->and( $filters[0] )->not->toHaveKey( 'range.post_date.from' )
		->and( $filters[0] )->not->toHaveKey( 'range.post_date.to' );

	unset( $_GET['fs'] );
} );
