<?php
/**
 * Elasticsearch Extensions Tests: Bootstrap functions.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

/**
 * Boot up adapters and wake Elasticsearch up.
 *
 * @param string $adapter Adapter.
 */
function elasticsearch_bootup(): void {

	// ES server.
	$host = 'http://localhost:9200';

	echo "----\n";

	sp_setup( $host );
	vip_setup( $host );

	echo "----\n";
}

/**
 * Ping the Elasticsearch instance.
 *
 * @param string $host Host URL.
 */
function ping_es( $host ): void {
	static $output = null;
	static $tries  = 5;
	static $sleep  = 3;

	if ( ! empty( $output ) ) {
		return;
	}

	do {
		$response = wp_remote_get( $host ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $body['version']['number'] ) ) {
				printf( "Elasticsearch is up and running, using version: %s.\n", $body['version']['number'] );
			}
			break;
		} else {
			printf(
				"\nInvalid response from ES (%s), sleeping %d seconds and trying again...\n",
				wp_remote_retrieve_response_code( $response ),
				$sleep
			);
			sleep( $sleep );
		}
	} while ( --$tries );

	// If we didn't end with a 200 status code, exit.
	if ( '200' != wp_remote_retrieve_response_code( $response ) ) {
		printf( "Could not index posts!\nResponse code %s\n", wp_remote_retrieve_response_code( $response ) );
		if ( is_wp_error( $response ) ) {
			printf( "Message: %s\n", $response->get_error_message() );
		}
		exit( 1 );
	}

	// Save to the static variable.
	$output = $response;
}

/**
 * SearchPress setup.
 *
 * @param string $host Host url.
 */
function sp_setup( $host ): void {

	// Bail early.
	if ( false === class_exists( 'SP_Config' ) ) {
		echo "-- The SearchPress plugin IS NOT available! =(\n";
		return;
	}

	// Use $_ENV['SEARCHPRESS_HOST'], if set.
	$sp_host = getenv( 'SEARCHPRESS_HOST' );
	if ( ! empty( $sp_host ) ) {
		$host = $sp_host;
	}

	// Ping ES.
	ping_es( $host );

	// Set up SearchPress.
	searchpress_setup();

	echo "-- The SearchPress plugin is available! \o/\n";
}

/**
 * VIP Search setup.
 *
 * @param string $host Host url.
 */
function vip_setup( $host ): void {

	// Bail early.
	if ( false === class_exists( '\Automattic\VIP\Search\Search' ) ) {
		echo "-- The VIP Enterprise Search plugin IS NOT available! =(\n";
		return;
	}

	define( 'Automattic\WP\Cron_Control\JOB_CONCURRENCY_LIMIT', 10 );
	define( 'VIP_ELASTICSEARCH_ENDPOINTS', [ $host ] );
	define( 'VIP_ELASTICSEARCH_USERNAME', 'vip-search' );
	define( 'VIP_ELASTICSEARCH_PASSWORD', 'password' );
	define( 'FILES_CLIENT_SITE_ID', 'test-project' );

	// Ping ES.
	ping_es( $host );

	echo "-- The VIP Enterprise Search plugin is available! \o/\n";
}
