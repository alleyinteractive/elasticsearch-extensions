<?php
/**
 * Elasticsearch Extensions Tests: Bootstrap File
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

// Load Composer's autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

Mantle\Testing\manager()
	->before( function() {
		// Define bootstrap helper functions.
		require_once __DIR__ . '/includes/bootstrap-functions.php';

		// Load adapters bootstrap files.
		require_once __DIR__ . '/includes/searchpress-bootstrap.php';

		// Loading Elasticsearch Extensions testcases.
		require_once __DIR__ . '/includes/class-adapter-unit-test-case.php';
		require_once __DIR__ . '/includes/class-searchpress-unit-test-case.php';
		require_once __DIR__ . '/includes/class-vip-enterprise-search-unit-test-case.php';

		uses( \SearchPress_Adapter_UnitTestCase::class)->in('adapters/searchpress' );
		uses( \VIP_Enterprise_Search_Adapter_UnitTestCase::class)->in('adapters/vip-search' );
	})
	->after(
		function() {
			// Load plugins.
			require_once dirname( __FILE__, 4 ) . '/mu-plugins/search/search.php';
			require_once dirname( __FILE__, 3 ) . '/searchpress/searchpress.php';
			require_once dirname( __DIR__ ) . '/elasticsearch-extensions.php';

			elasticsearch_bootup(); // Boot up ES.
		}
	)
	->install();
