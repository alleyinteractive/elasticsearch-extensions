<?php
/**
 * Elasticsearch Extensions Tests: Bootstrap File
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
const WP_TESTS_PHPUNIT_POLYFILLS_PATH = __DIR__ . '/../vendor/yoast/phpunit-polyfills';

define( 'ELASTICSEARCH_EXTENSIONS_TESTS_DIR', dirname( __DIR__ ) . '/tests/' );

// Load Core's test suite.
$elasticsearch_extensions_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $elasticsearch_extensions_tests_dir ) {
	$elasticsearch_extensions_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $elasticsearch_extensions_tests_dir . '/includes/functions.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

// Define bootstrap helper functions.
require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/bootstrap-functions.php';

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

	// Load plugins.
	require_once dirname( __FILE__, 3 ) . '/searchpress/searchpress.php';

	// Load this plugin.
	require_once dirname( __DIR__ ) . '/elasticsearch-extensions.php';

	// Boot up ES!
	elasticsearch_bootup();
}
tests_add_filter( 'muplugins_loaded', 'elasticsearch_extensions_manually_load_environment' );

// Disable the emoji detection script, because it throws unnecessary errors.
tests_add_filter(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	}
);

// Load adapters bootstrap files.
require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/searchpress-bootstrap.php';

// Include core's bootstrap.
require $elasticsearch_extensions_tests_dir . '/includes/bootstrap.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

echo "Loading Elasticsearch Extensions testcase...\n";
require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/class-unit-test-case.php';

echo "Loading Adapters testcases...\n";
require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/adapters/searchpress/class-searchpress-unit-test-case.php';
require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/adapters/vip-search/class-vip-enterprise-search-unit-test-case.php';
