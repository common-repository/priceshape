<?php

namespace Priceshape\Core;

/**
 * Class Priceshape_Helper
 * @package Priceshape\Core
 */
class Priceshape_Helper {
	/**
	 * A debugging function
	 */
	public static function priceshape_dd() {
		echo '<pre>';
		array_map( function ( $x ) {
			var_dump( $x );
		}, func_get_args() );
		die;
	}

	/**
	 * Returns a parameter $value if it exists
	 *
	 * @param $value - parameter we want to check
	 * @param null $default_value - default value of the parameter
	 *
	 * @return null
	 */
	public static function priceshape_exists( $value, $default_value = null ) {
		return isset( $value ) && ! empty( $value ) ? $value : $default_value;
	}

	/**
	 * Checks if exists a key into array and returns it
	 *
	 * @param $array - an array we want to check
	 * @param $key - a searching key
	 * @param null $default_value - default value
	 *
	 * @return mixed|null
	 */
	public static function priceshape_key_exists( $array, $key, $default_value = null ) {
		return isset( $array[ $key ] ) && ! empty( $array[ $key ] ) ? $array[ $key ] : $default_value;
	}

	/**
	 * Concatenates array elements into a string
	 *
	 * @param $pieces - an array
	 *
	 * @return string
	 */
	public static function priceshape_implodes_variables( $pieces ) {
		return '"' . implode( '","', $pieces ) . '"';
	}

	public static function priceshape_sanitize( $array ) {
		foreach( $array as $key => $value ) {
			$sanitized_array[$key] = sanitize_text_field( $value );
		}

		return $sanitized_array;
	}
}
