<?php

namespace Priceshape\DataTables;

use Priceshape\Core\Priceshape_Helper;
use Priceshape\Priceshape_Queries;

class Products_Data_Tables extends Priceshape_Data_Tables {
	protected $type = 'all';
	protected $filter_list = [
		self::FILTER_BY_STOCK_STATUS,
		self::FILTER_BY_PRICESHAPE_OPTION,
	];

	/**
	 * Returns columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'price'         => 'Price',
			'cost_of_goods' => 'Cost of Goods',
			'prs'           => 'PriceShape',
		];

		return self::get_custom_columns( $columns );
	}

	/**
	 * Returns bulk actions
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return [
			'prs-on'      => 'Add to PriceShape',
			'prs-off'     => 'Remove from PriceShape',
			'all-prs-on'  => 'Add all to PriceShape',
			'all-prs-off' => 'Remove all from PriceShape',
		];
	}

	/**
	 * Bulk action handler
	 *
	 */
	protected function bulk_action_handler() {
		if ( empty( $_POST['_wpnonce'] ) ) {
			return;
		}

		if ( ! $this->current_action() ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
			wp_die( 'nonce error' );
		}

		if ( empty( $_POST['products_ids'] ) && ( $this->current_action() != "all-prs-on" && $this->current_action() != "all-prs-off" ) ) {
			return;
		}

		if ( ! empty( $_POST['products_ids'] ) ) {
			$products_ids = Priceshape_Helper::priceshape_sanitize( $_POST['products_ids'] );
		}

		switch ( $this->current_action() ) {
			case "prs-on":
				Priceshape_Queries::priceshape_add_to_prs( Priceshape_Helper::priceshape_sanitize( $products_ids ) );
			break;
			case "all-prs-on":
				Priceshape_Queries::priceshape_add_all_to_prs();
			break;
			case "prs-off":
				Priceshape_Queries::priceshape_remove_from_prs( Priceshape_Helper::priceshape_sanitize( $products_ids ) );
			break;
			case "all-prs-off":
				Priceshape_Queries::priceshape_remove_all_from_prs();
			break;
			default:
				Priceshape_Queries::priceshape_prs_write_down_log( 'Unknown action = ' . $this->current_action() . ',  in' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
		}
	}

	/**
	 * Returns additional query params
	 *
	 * @return array
	 */
	protected function get_additional_query_params() {
		$search = $this->_pagination_args['s'];

		return [
			's'            => $search,
			'filter_stock' => ( isset( $_REQUEST['filter_stock'] ) ? sanitize_text_field( $_REQUEST['filter_stock'] ) : '' ),
			'filter_prs'   => ( isset( $_REQUEST['filter_prs'] ) ? sanitize_text_field( $_REQUEST['filter_prs'] ) : '' ),
		];
	}
}
