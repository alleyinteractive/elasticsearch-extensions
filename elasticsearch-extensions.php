<?php
/**
 * Plugin Name:  Elasticsearch Extensions
 * Plugin URI:   https://github.com/alleyinteractive/elasticsearch-extensions
 * Description:  A WordPress plugin to make integrating sites with Elasticsearch easier.
 * Author:       Alley
 * Author URI:   https://alley.co/
 * Text Domain:  elasticsearch-extensions
 * Domain Path:  /languages
 * Version:      0.1.0
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

require_once __DIR__ . '/lib/autoload.php';

// Load adapter automatically based on environment settings.
if ( defined( 'VIP_ENABLE_VIP_SEARCH' ) && VIP_ENABLE_VIP_SEARCH ) {
	require_once __DIR__ . '/adapters/class-vip-enterprise-search.php';
}

/**
 * Helper function for getting the instance of the Elasticsearch_Extensions
 * class based on the automatically loaded adapter, or null if none exists.
 *
 * @return mixed An Elasticsearch_Extensions adapter if successful, or null on failure.
 */
function elasticsearch_extensions() {
	return class_exists( 'Elasticsearch_Extensions' )
		? Elasticsearch_Extensions::instance()
		: null;
}
