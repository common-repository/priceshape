<?php

namespace Priceshape\DataTables;

use Priceshape\Priceshape_Plugin;
use Priceshape\Priceshape_Queries;

/**
 * Class Products_Approve_Data_Tables
 * @package Priceshape\DataTables
 */
class Products_Approve_Data_Tables extends Priceshape_Data_Tables {
	protected $type = 'prs';
	protected $filter_list = [
		self::FILTER_BY_STOCK_STATUS
	];

	/**
	 * Returns columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'date'      => 'Updated at',
			'new_value' => 'New Value',
			'field'     => 'Updated Field',
		];

		if ( Priceshape_Plugin::priceshape_get_param( Priceshape_Plugin::SHOW_OLD_VALUE ) == Priceshape_Plugin::YES ) {
			$columns['old_value'] = 'Old value';
		}

		$columns = array_reverse( $columns );

		return self::get_custom_columns( $columns );
	}

	/**
	 * Returns sortable columns
	 *
	 * @return array
	 */
	function get_sortable_columns() {
		$sortable_columns = [
			'new_value' => [ 'new_value', false ],
			'old_value' => [ 'old_value', false ],
			'date'      => [ 'date', false ],
		];

		return self::get_columns_sortable( $sortable_columns );
	}

	/**
	 * Returns checkbox
	 *
	 * @param $item - an array
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="field_ids[]" value="%s" />', $item['field_id'] );
	}

	/**
	 * Returns bulk actions
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return [
			'approve'     => 'Approve',
			'decline'     => 'Decline',
			'approve-all' => 'Approve All',
			'decline-all' => 'Decline All',
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

		$field_ids = isset( $_POST['field_ids'] ) ? Priceshape_Helper::priceshape_sanitize( $_POST['field_ids'] ) : [];
		if ( empty( $field_ids ) && ( "approve-all" != $this->current_action() && "decline-all" != $this->current_action() ) ) {
			return;
		}

		switch ( $this->current_action() ) {
			case "approve":
				Priceshape_Queries::priceshape_approve_prices( $field_ids );
			break;
			case "decline":
				Priceshape_Queries::priceshape_decline( $field_ids );
			break;
			case "approve-all":
				Priceshape_Queries::priceshape_approve_all_prices();
			break;
			case "decline-all":
				Priceshape_Queries::priceshape_decline_all();
			break;
			default:
				Priceshape_Queries::priceshape_prs_write_down_log( 'Unknown action = ' . $this->current_action() . ',  in' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
			break;
		}
	}

	/**
	 * Gets features
	 *
	 */
	protected function get_other_features() {
		$this->get_btn_hide_old_price();
		$this->get_btn_auto_update_price();
		$this->get_btn_set_only_sale_price();
	}

	/**
	 * 'Show old price' checkbox
	 *
	 */
	protected function get_btn_hide_old_price() {
		$hide_old_price   = Priceshape_Plugin::priceshape_get_param( Priceshape_Plugin::SHOW_OLD_VALUE );
		$text_hide_button = ' Show Old Price';
		$hide             = ( $hide_old_price == Priceshape_Plugin::NO ) ? Priceshape_Plugin::YES : Priceshape_Plugin::NO;
		$checked          = ( $hide_old_price == Priceshape_Plugin::YES ) ? 'checked' : '';
		?>
        <div class="btn-box">
            <span id="hide-old-price" data-hide="<?php echo $hide; ?>"><?php echo $text_hide_button; ?>
                <label class="switch">
                    <input type="checkbox" <?php echo $checked; ?>>
                    <span class="slider round"></span>
                </label>
            </span>
        </div>
		<?php
	}

	/**
	 * 'Auto Update Price' checkbox
	 *
	 */
	protected function get_btn_auto_update_price() {
		$auto_update_price = Priceshape_Plugin::priceshape_get_param( Priceshape_Plugin::AUTO_UPDATE_PRICE );
		$text_hide_button  = 'Auto Update Price';
		$auto_update       = ( $auto_update_price == Priceshape_Plugin::NO ) ? Priceshape_Plugin::YES : Priceshape_Plugin::NO;
		$checked           = ( $auto_update_price == Priceshape_Plugin::NO ) ? '' : 'checked';
		?>
        <div class="btn-box">
            <span id="auto-update-price"
                  data-auto-update="<?php echo $auto_update; ?>"><?php echo $text_hide_button; ?>
                <label class="switch">
                    <input type="checkbox" <?php echo $checked; ?>>
                    <span class="slider round"></span>
                </label>
            </span>
        </div>
		<?php
	}

	/**
	 * 'Update Only Sale Price' checkbox
	 *
	 */
	protected function get_btn_set_only_sale_price() {
		$update_only_sale_price = Priceshape_Plugin::priceshape_get_param( Priceshape_Plugin::UPDATE_ONLY_SALE_PRICE );
		$text_hide_button       = 'Update Only Sale Price';
		$update_only            = ( $update_only_sale_price == Priceshape_Plugin::NO ) ? Priceshape_Plugin::YES : Priceshape_Plugin::NO;
		$checked                = ( $update_only_sale_price == Priceshape_Plugin::NO ) ? '' : 'checked';
		?>
        <div class="btn-box">
            <span id="update-only-sale-price"
                  data-update-only-sale-price="<?php echo $update_only; ?>"><?php echo $text_hide_button; ?>
                <label class="switch">
                    <input type="checkbox" <?php echo $checked; ?>>
                    <span class="slider round"></span>
                </label>
            </span>
        </div>
		<?php
	}
}
