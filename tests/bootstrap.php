<?php
/**
 * Elasticsearch Extensions Tests: Bootstrap File
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

// Load Composer's autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

defined( 'VIP_ENABLE_VIP_SEARCH' ) || define( 'VIP_ENABLE_VIP_SEARCH', true );
defined( 'VIP_ENABLE_VIP_SEARCH_QUERY_INTEGRATION' ) || define( 'VIP_ENABLE_VIP_SEARCH_QUERY_INTEGRATION', true );
defined( 'FILES_CLIENT_SITE_ID' ) || define( 'FILES_CLIENT_SITE_ID', 'test-project' );
defined( 'VIP_ELASTICSEARCH_ENDPOINTS' ) || define( 'VIP_ELASTICSEARCH_ENDPOINTS', [ 'http://localhost:9200' ] );
defined( 'VIP_ELASTICSEARCH_PASSWORD' ) || define( 'VIP_ELASTICSEARCH_PASSWORD', 'password' );
defined( 'VIP_ELASTICSEARCH_USERNAME' ) || define( 'VIP_ELASTICSEARCH_USERNAME', 'vip-search' );
defined( 'Automattic\WP\Cron_Control\JOB_CONCURRENCY_LIMIT' ) || define( 'Automattic\WP\Cron_Control\JOB_CONCURRENCY_LIMIT', 10 );

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
			// Create Table needed by EP.
			\Automattic\VIP\Search\Search::instance()->queue->schema->prepare_table();

			// Load plugins.
			require_once dirname( __FILE__, 3 ) . '/searchpress/searchpress.php';
			require_once dirname( __DIR__ ) . '/elasticsearch-extensions.php';

			elasticsearch_bootup(); // Boot up ES.
		}
	)
	->install();
