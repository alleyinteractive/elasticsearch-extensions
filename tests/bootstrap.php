<?php
/**
 * Elasticsearch Extensions Tests: Bootstrap File
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

use Mantle\Support\Str;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\data_get;

// Define VIP Search constants early.
defined( 'VIP_ENABLE_VIP_SEARCH' ) || define( 'VIP_ENABLE_VIP_SEARCH', true );
defined( 'VIP_ENABLE_VIP_SEARCH_QUERY_INTEGRATION' ) || define( 'VIP_ENABLE_VIP_SEARCH_QUERY_INTEGRATION', true );
defined( 'FILES_CLIENT_SITE_ID' ) || define( 'FILES_CLIENT_SITE_ID', 'test-project' );
defined( 'VIP_ELASTICSEARCH_ENDPOINTS' ) || define( 'VIP_ELASTICSEARCH_ENDPOINTS', [ 'http://localhost:9200' ] );
defined( 'VIP_ELASTICSEARCH_PASSWORD' ) || define( 'VIP_ELASTICSEARCH_PASSWORD', 'password' );
defined( 'VIP_ELASTICSEARCH_USERNAME' ) || define( 'VIP_ELASTICSEARCH_USERNAME', 'vip-search' );
defined( 'Automattic\WP\Cron_Control\JOB_CONCURRENCY_LIMIT' ) || define( 'Automattic\WP\Cron_Control\JOB_CONCURRENCY_LIMIT', 10 );

if ( ! file_exists( __DIR__ . '/../composer.lock' ) ) {
	echo 'Please run "composer install" from the elasticsearch-extensions directory before running tests.' . PHP_EOL;
	exit( 1 );
}

$manager = Mantle\Testing\manager()
	->maybe_rsync_plugin()
	->with_vip_mu_plugins();

// Add plugins that are installed via Composer to rsync environments.
collect( json_decode( file_get_contents( __DIR__ . '/../composer.lock' ), true )['packages-dev'] ?? [] )
	->where( 'type', 'wordpress-plugin' )
	->where( 'name', '!=', 'automattic/vip-go-mu-plugins-built' )
	->each( function ( array $item ) use ( $manager ) {
		$dist_url = data_get( $item, 'dist.url' );
		$name     = Str::after( $item['name'], '/' );

		if ( $dist_url ) {
			return $manager->install_plugin( $name, $dist_url );
		}

		$source_url = data_get( $item, 'source.url' );

		if ( $source_url && str_ends_with( $source_url, '.git' ) ) {
			return $manager->install_plugin(
				$name,
				str_replace( '.git', '/archive/refs/heads/' . $item['source']['reference'] . '.zip', $source_url ),
			);
		}
	} );

$manager
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
