<?php

namespace Priceshape\Core;

/**
 * Class Priceshape_Request
 * @package Priceshape\Core
 */
class Priceshape_Request {
	/**
	 * Returns if the key exists by method
	 *
	 * @param $key - a checking key
	 * @param null $default_value - default value
	 *
	 * @return mixed|null
	 */
	public static function priceshape_get_by_key( $key, $default_value = null ) {
		$request = self::priceshape_get_real_method( true );

		return Priceshape_Helper::priceshape_key_exists( $request, $key, $default_value );
	}

	/**
	 * Returns a request method
	 *
	 * @param bool $is_request - pointer on method
	 *
	 * @return mixed|string|null
	 */
	private static function priceshape_get_real_method( $is_request = false ) {
		$method = Priceshape_Helper::priceshape_key_exists( $_SERVER, 'REQUEST_METHOD', 'GET' );
		$method = strtoupper( $method );

		if ( ! $is_request ) {
			return $method;
		}

		switch ( $method ) {
			case 'GET':
				return Priceshape_Helper::priceshape_sanitize( $_GET );
			case 'POST':
				return Priceshape_Helper::priceshape_sanitize( $_POST );
			default:
				return Priceshape_Helper::priceshape_sanitize( $_REQUEST );
		}
	}
}
