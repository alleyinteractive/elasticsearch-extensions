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