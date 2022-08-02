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

// Define bootstrap helper functions.
require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/bootstrap-functions.php';

// Load adapters bootstrap files.
require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/searchpress-bootstrap.php';

// echo "Loading Elasticsearch Extensions testcase...\n";
require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/class-adapter-unit-test-case.php';

// echo "Loading Adapters testcases...\n";
require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/adapters/searchpress/class-searchpress-unit-test-case.php';
require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/adapters/vip-search/class-vip-enterprise-search-unit-test-case.php';

// uses(\Pest\PestPluginWordPress\FrameworkTestCase::class)->in(__DIR__);

uses(\Pest\PestPluginWordPress\FrameworkTestCase::class)->in('adapters/searchpress');
uses(\VIP_Enterprise_Search_Adapter_UnitTestCase::class)->in('adapters/vip-search');

\Mantle\Testing\manager()
	// Load the main file of the plugin.
	->loaded(
		function() {
			// Load this plugin.
			// require_once dirname( __DIR__ ) . '/elasticsearch-extensions.php';
			require_once __DIR__ . '/../../searchpress/searchpress.php';
			require_once __DIR__ . '/../elasticsearch-extensions.php';
		}
	)
	->install();

// Boot up ES!
elasticsearch_bootup();
