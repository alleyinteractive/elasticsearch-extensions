<?php
/**
 * Elasticsearch Extensions Tests: SearchPress bootstrap functions.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

function sp_remove_index() {

	// Bail early.
	if ( false === class_exists( 'SP_Config' ) ) {
		return;
	}

	SP_Config()->flush();
}
tests_add_filter( 'shutdown', 'sp_remove_index' );

function sp_index_flush_data() {

	// Bail early.
	if ( false === class_exists( 'SP_Config' ) ) {
		return;
	}

	SP_Config()->flush();

	// Attempt to create the mapping.
	$response = SP_Config()->create_mapping();

	if ( ! empty( $response->error ) ) {
		echo "Could not create the mapping!\n";

		// Output error data.
		if ( ! empty( $response->error->code ) ) {
			printf( "Error code `%d`\n", $response->error->code );
		} elseif ( ! empty( $response->status ) ) {
			printf( "Error code `%d`\n", $response->status );
		}
		if ( ! empty( $response->error->message ) ) {
			printf( "Error message `%s`\n", $response->error->message );
		} elseif ( ! empty( $response->error->reason ) && ! empty( $response->error->type ) ) {
			printf( "Error: %s\n%s\n", $response->error->type, $response->error->reason );
		}
		exit( 1 );
	}

	SP_API()->post( '_refresh' );
}

function searchpress_setup() {
	sp_index_flush_data();

	$i = 0;
	while ( ! ( $beat = SP_Heartbeat()->check_beat( true ) ) && $i++ < 5 ) {
		echo "\nHeartbeat failed, sleeping 2 seconds and trying again...\n";
		sleep( 2 );
	}
	if ( ! $beat && ! SP_Heartbeat()->check_beat( true ) ) {
		echo "\nCould not find a heartbeat!";
		exit( 1 );
	}
}
