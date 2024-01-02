<?php
/**
 * Elasticsearch Extensions Tests: VIP_Enterprise_Search_Adapter_TestAPI class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

it( 'tests that phrase matching is disabled by default', function () {
	$this->assertEquals( false, true );
} );

it( 'tests that phrase matching is enabled via function', function () {
	add_action(
		'elasticsearch_extensions_config',
		function( $es_config ) {
			$es_config->enable_phrase_matching();
		}
	);

	$this->assertEquals( false, true );
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

	$this->assertEquals( false, true );
} );
