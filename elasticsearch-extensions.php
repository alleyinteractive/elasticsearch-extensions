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

use Elasticsearch_Extensions\Controller;
use Elasticsearch_Extensions\Registry;

// Load Composer dependencies.
require_once __DIR__ . '/vendor/wordpress-autoload.php';

require_once __DIR__ . '/lib/autoload.php';

/**
 * A helper function for getting the instance of the controller class.
 *
 * @return Controller
 */
function elasticsearch_extensions(): Controller {
	return Registry::controller();
}

// Bootstrap the plugin.
elasticsearch_extensions()->load_adapter();
