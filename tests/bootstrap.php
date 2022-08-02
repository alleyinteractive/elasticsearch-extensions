<?php
/**
 * Elasticsearch Extensions Tests: Bootstrap File
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

define( 'ELASTICSEARCH_EXTENSIONS_TESTS_DIR', dirname( __DIR__ ) . '/tests/' );

Mantle\Testing\manager()
	->before( function() {
		// Define bootstrap helper functions.
		require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/bootstrap-functions.php';

		// Load adapters bootstrap files.
		require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/searchpress-bootstrap.php';

		// Loading Elasticsearch Extensions testcases.
		require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/class-adapter-unit-test-case.php';
		require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/class-searchpress-unit-test-case.php';
		require_once ELASTICSEARCH_EXTENSIONS_TESTS_DIR . '/includes/class-vip-enterprise-search-unit-test-case.php';
	})
	->after(
		function() {
			// Load plugins.
			require_once dirname( __FILE__, 3 ) . '/searchpress/searchpress.php';
			require_once dirname( __DIR__ ) . '/elasticsearch-extensions.php';

			elasticsearch_bootup(); // Boot up ES.
		}
	)
	->install();
