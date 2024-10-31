<?php

namespace Priceshape\Core;

use Priceshape\Priceshape_Queries;
use Throwable;

/**
 * Class Priceshape_Error_Handler
 * @package Priceshape\Core
 */
class Priceshape_Error_Handler {
	/**
	 * Returns error names
	 *
	 * @param $error - the key of the error array
	 *
	 * @return string
	 */
	static public function priceshape_get_error_name( $error ) {
		$errors = [
			E_ERROR             => 'ERROR',
			E_WARNING           => 'WARNING',
			E_PARSE             => 'PARSE',
			E_NOTICE            => 'NOTICE',
			E_CORE_ERROR        => 'CORE_ERROR',
			E_CORE_WARNING      => 'CORE_WARNING',
			E_COMPILE_ERROR     => 'COMPILE_ERROR',
			E_COMPILE_WARNING   => 'COMPILE_WARNING',
			E_USER_ERROR        => 'USER_ERROR',
			E_USER_WARNING      => 'USER_WARNING',
			E_USER_NOTICE       => 'USER_NOTICE',
			E_STRICT            => 'STRICT',
			E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
			E_DEPRECATED        => 'DEPRECATED',
			E_USER_DEPRECATED   => 'USER_DEPRECATED',
		];

		if ( array_key_exists( $error, $errors ) ) {
			return $errors[ $error ] . " [$error]";
		}

		return $error;
	}

	/**
	 * Error_Handler constructor.
	 */
	public function __construct() {
		error_reporting( E_ALL );
		set_error_handler( [ $this, 'priceshape_error_handler'] );
		set_exception_handler( [ $this, 'priceshape_exception_handler'] );
		register_shutdown_function( [ $this, 'priceshape_fatal_error_handler'] );
	}

	/**
	 * Sets a user-defined error handler function
	 *
	 * @param $errno - error type
	 * @param $errstr - error message
	 * @param $file - error file
	 * @param $line - error line
	 *
	 * @return bool
	 */
	public function priceshape_error_handler( $errno, $errstr, $file, $line ) {
		$this->priceshape_write_down_error( $errno, $errstr, $file, $line );

		return false;
	}

	/**
	 * Sets a user-defined exception handler function
	 *
	 * @param Throwable $e
	 */
	public function priceshape_exception_handler( Throwable $e ) {
		$this->priceshape_write_down_error( get_class( $e ), $e->getMessage(), $e->getFile(), $e->getLine() );
	}

	/**
	 * Function for execution on shutdown
	 *
	 */
	public function priceshape_fatal_error_handler() {
		if ( $error = error_get_last() AND $error['type'] & ( E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR ) ) {
			$this->priceshape_write_down_error( $error['type'], $error['message'], $error['file'], $error['line'] );
		}
	}

	/**
	 * Saves logs to database
	 *
	 * @param $errno - error type
	 * @param $errstr - error message
	 * @param $file - error file
	 * @param $line - error line
	 */
	public function priceshape_write_down_error( $errno, $errstr, $file, $line ) {
		$message = self::priceshape_get_error_name( $errno ) . " " . $errstr . ' file: ' . $file . ' line: ' . $line;
		Priceshape_Queries::priceshape_prs_write_down_log( $message );
	}
}
