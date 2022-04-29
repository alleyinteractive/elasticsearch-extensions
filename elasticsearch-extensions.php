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

require_once __DIR__ . '/lib/autoload.php';

// Dynamically load the adapter based on environment settings.
Controller::instance()->load_adapter();

/**
 * A helper function for getting the instance of the controller class.
 *
 * @return Controller
 */
function elasticsearch_extensions(): Controller {
	return Controller::instance();
}
