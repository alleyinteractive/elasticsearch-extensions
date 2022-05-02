<?php
/**
 * Elasticsearch Extensions: Factory
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions;

use Elasticsearch_Extensions\Adapters\Generic;
use Elasticsearch_Extensions\Adapters\VIP_Enterprise_Search;

/**
 * Factory class. Used to create new instances of classes.
 *
 * @package Elasticsearch_Extensions
 */
class Factory {

	/**
	 * Gets an instance of the controller class, hooked.
	 *
	 * @return Controller An instance of the controller class.
	 */
	public static function controller(): Controller {
		return self::initialize( new Controller() );
	}

	/**
	 * Deinitializes the given object by calling the unhook method.
	 *
	 * @param mixed $object The object to deinitialize.
	 *
	 * @return mixed The deinitialized object.
	 */
	public static function deinitialize( $object ) {
		if ( method_exists( $object, 'unhook' ) ) {
			$object->unhook();
		}

		return $object;
	}

	/**
	 * Returns an initialized generic adapter.
	 *
	 * @return Generic The initialized adapter.
	 */
	public static function generic_adapter(): Generic {
		return self::initialize( new Generic() );
	}

	/**
	 * Initializes the given object by calling the hook method.
	 *
	 * @param mixed $object The object to initialize.
	 *
	 * @return mixed The initialized object.
	 */
	public static function initialize( $object ) {
		if ( method_exists( $object, 'hook' ) ) {
			$object->hook();
		}

		return $object;
	}

	/**
	 * Returns an initialized VIP Enterprise Search adapter.
	 *
	 * @return Generic The initialized adapter.
	 */
	public static function vip_enterprise_search_adapter(): VIP_Enterprise_Search {
		return self::initialize( new VIP_Enterprise_Search() );
	}
}
