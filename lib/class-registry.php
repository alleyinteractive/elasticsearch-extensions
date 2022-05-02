<?php
/**
 * Elasticsearch Extensions: Registry
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

/**
 * Registry class. Used to create and manage single instances of classes.
 *
 * @package Elasticsearch_Extensions
 */
class Registry {

	/**
	 * Keeps a list of active instances of classes, keyed by identifier.
	 *
	 * @var array
	 */
	private static array $registry = [];

	/**
	 * Gets the active instance of the controller.
	 *
	 * @return Controller The requested class instance.
	 */
	public static function controller(): Controller {
		if ( ! isset( self::$registry['controller'] ) ) {
			self::$registry['controller'] = Factory::controller();
		}

		return self::$registry['controller'];
	}
}
