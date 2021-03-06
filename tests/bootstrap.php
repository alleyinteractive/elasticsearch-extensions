<?php
/**
 * Elasticsearch Extensions Tests: Bootstrap File
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
const WP_TESTS_PHPUNIT_POLYFILLS_PATH = __DIR__ . '/../vendor/yoast/phpunit-polyfills';

// Load Core's test suite.
$elasticsearch_extensions_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $elasticsearch_extensions_tests_dir ) {
	$elasticsearch_extensions_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $elasticsearch_extensions_tests_dir . '/includes/functions.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

/**
 * Setup our environment.
 */
function elasticsearch_extensions_manually_load_environment() {
	/*
	 * Tests won't start until the uploads directory is scanned, so use the
	 * lightweight directory from the test install.
	 *
	 * @see https://core.trac.wordpress.org/changeset/29120.
	 */
	add_filter(
		'pre_option_upload_path',
		function () {
			return ABSPATH . 'wp-content/uploads';
		}
	);

	// Load this plugin.
	require_once dirname( __DIR__ ) . '/elasticsearch-extensions.php';
}
tests_add_filter( 'muplugins_loaded', 'elasticsearch_extensions_manually_load_environment' );

// Disable the emoji detection script, because it throws unnecessary errors.
tests_add_filter(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	}
);

// Include core's bootstrap.
require $elasticsearch_extensions_tests_dir . '/includes/bootstrap.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
