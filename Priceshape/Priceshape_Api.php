<?php

namespace Priceshape;

use Exception;
use SimpleXMLElement;

/**
 * Class Priceshape_Api
 * @package Priceshape
 */
class Priceshape_Api {
	private $is_plugin_token_correct;

	const XML_TAGS_NAMES = [
		'stock_status'          => 'availability',
		'variation_description' => 'description',
		'wc_cog_cost'           => 'cost_price',
	];

	const PLUGIN_PARAMS_NAMES = [
		'state'                => Priceshape_Plugin::PLUGIN_STATUS,
		'products_restriction' => Priceshape_Plugin::PRODUCTS_LIMIT,
	];

	/**
	 * Checks if plugin token correct
	 *
	 * @param $params - an array of parameters
	 */
	private function priceshape_set_tokens_correction( $params ) {
		if ( isset( $params[ Priceshape_Plugin::PLUGIN_TOKEN ] ) ) {
			$this->is_plugin_token_correct = $params[ Priceshape_Plugin::PLUGIN_TOKEN ] === Priceshape_Queries::priceshape_get_plugin_param( Priceshape_Plugin::PLUGIN_TOKEN );
		}

		if ( isset( $_GET[ Priceshape_Plugin::PLUGIN_TOKEN ] ) ) {
			$this->is_plugin_token_correct = sanitize_text_field( $_GET[ Priceshape_Plugin::PLUGIN_TOKEN ] ) === Priceshape_Queries::priceshape_get_plugin_param( Priceshape_Plugin::PLUGIN_TOKEN );
		}
	}

	/**
	 * Creates a connection
	 *
	 * @param $request - a request parameter
	 */
	public function priceshape_create( $request ) {
		$params = json_decode( $request->get_body(), true );
		if ( empty( $params ) || ! is_array( $params ) ) {
			try {
				throw new Exception( 'request params is empty ,  in' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
			} catch ( Exception $e ) {
				Priceshape_Queries::priceshape_prs_write_down_log( $e->getMessage() );
				header( "HTTP/1.1 400" );
				die( "Bad Request" );
			}
		}

		$this->priceshape_set_tokens_correction( $params );
		$this->priceshape_get_operation_access();

		$new_plugin_params = [];
		foreach ( $params as $key => $value ) {
			if ( array_key_exists( $key, self::PLUGIN_PARAMS_NAMES ) ) {
				$replace_key = self::PLUGIN_PARAMS_NAMES[ $key ];
				if ( $replace_key == Priceshape_Plugin::PLUGIN_STATUS ) {
					Priceshape_Queries::priceshape_change_plugins_status_to( $params[ $key ] );
					continue;
				}
				$new_plugin_params[ $replace_key ] = $params[ $key ];
			} else {
				$new_plugin_params[ $key ] = $params[ $key ];
			}
		}
		if ( ! empty( $new_plugin_params ) ) {
			Priceshape_Queries::priceshape_insert_plugin_params( $new_plugin_params );
		}
		header( 'HTTP/1.1 200 OK' );
		die( '200 OK' );
	}

	/**
	 * @param $request - a request parameter
	 */
	public function priceshape_read( $request ) {
		$params = json_decode( $request->get_body(), true );
		$this->priceshape_set_tokens_correction( $params );
		$this->priceshape_get_operation_access();
		$all_products_info = Priceshape_Queries::priceshape_get_all_product_for_xml();

		try {
			header( 'Content-type: text/xml' );
		} catch ( Exception $e ) {
			Priceshape_Queries::priceshape_prs_write_down_log( $e->getMessage() );
			header( "HTTP/1.1 500" );
			die();
		}
		$xml = new SimpleXMLElement( '<xml/>' );
		foreach ( $all_products_info as $product_info ) {
			$link  = $product_info['url'] . '?id=' . $product_info['id'];
			$track = $xml->addChild( 'item' );
			$track->addChild( 'link', htmlspecialchars( $link ) );
			$track->addChild( 'id', htmlspecialchars( $product_info['id'] ) );
			$track->addChild( 'title', htmlspecialchars( $product_info['title'] ) );
			$track->addChild( 'name', htmlspecialchars( $product_info['name'] ) );
			$track->addChild( 'image_link', htmlspecialchars( $product_info['image'] ) );
			$data = json_decode( $product_info['data'], true );
			if ( is_array( $data ) ) {
				foreach ( $data as $key => $value ) {
					if ( empty( $value ) ) {
						continue;
					}
					if ( array_key_exists( $key, self::XML_TAGS_NAMES ) ) {
						$key = self::XML_TAGS_NAMES[ $key ];
					}
					$track->addChild( $key, htmlspecialchars( $value ) );
				}
			}
		}
		print( $xml->asXML() );
		die();
	}

	/**
	 * @param $request - a request parameter
	 */
	public function priceshape_update( $request ) {
		$params = json_decode( $request->get_body(), true );
		$this->priceshape_set_tokens_correction( $params );
		$this->priceshape_get_operation_access();
		if ( empty( $params ) || ! isset( $params['products'] ) ) {
			try {
				throw new Exception( 'Variable products for update do not exist,  in' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
			} catch ( Exception $e ) {
				Priceshape_Queries::priceshape_prs_write_down_log( $e->getMessage() );
				header( "HTTP/1.1 400" );
				die( 'Bad request1' );
			}
		}

		$products = $params['products'];
		if ( ! is_array( $products ) ) {
			try {
				throw new Exception( 'Variable $products must be array ' . gettype( $products ) . 'given,  in' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
			} catch ( Exception $e ) {
				Priceshape_Queries::priceshape_prs_write_down_log( $e->getMessage() );
				header( "HTTP/1.1 400" );
				die( 'Bad request2' );
			}
		}

		Priceshape_Queries::priceshape_update_product_values( $products );
		$auto_update_price = Priceshape_Plugin::priceshape_get_param( Priceshape_Plugin::AUTO_UPDATE_PRICE );
		if ( Priceshape_Plugin::YES == $auto_update_price ) {
			Priceshape_Queries::priceshape_approve_all_prices();
		}
		header( 'HTTP/1.1 200 OK' );
		die( '200 OK' );
	}

	/**
	 * Writes down logs
	 *
	 * @param $request - a request parameter
	 */
	public function priceshape_logs( $request ) {
		$params = json_decode( $request->get_body(), true );
		$this->priceshape_set_tokens_correction( $params );
		$this->priceshape_get_operation_access();
		if ( isset( $params['action'] ) && 'drop' == $params['action'] ) {
			Priceshape_Queries::priceshape_drop_logs();
			header( 'HTTP/1.1 200 OK' );
			die( '200 OK' );
		}
		$all_logs = Priceshape_Queries::priceshape_get_all_logs();
		try {
			header( 'Content-type: text/xml' );
		} catch ( Exception $e ) {
			Priceshape_Queries::priceshape_prs_write_down_log( $e->getMessage() );
			header( "HTTP/1.1 500" );
			die();
		}
		$xml = new SimpleXMLElement( '<xml/>' );
		foreach ( $all_logs as $log ) {
			$track = $xml->addChild( 'item' );
			$track->addChild( 'id', htmlspecialchars( $log['id'] ) );
			$track->addChild( 'message', htmlspecialchars( $log['message'] ) );
			$track->addChild( 'created_at', htmlspecialchars( $log['created_at'] ) );
		}
		print( $xml->asXML() );
		die();
	}

	/**
	 * Checks if plugin token is correct
	 *
	 */
	private function priceshape_get_operation_access() {
		if ( ! $this->is_plugin_token_correct ) {
			try {
				throw new Exception( 'incorrect plugin token,  in' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
			} catch ( Exception $e ) {
				Priceshape_Queries::priceshape_prs_write_down_log( $e->getMessage() );
				header( "HTTP/1.1 403" );
				die( "Access denied" );
			}
		}
	}
}
