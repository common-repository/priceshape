<?php

namespace Priceshape\DataTables\Traits;

use Priceshape\Core\Priceshape_Helper;
use Priceshape\Core\Priceshape_Request;

/**
 * Trait Data_Table_Filters
 * @package Priceshape\DataTables\Traits
 */
trait  Data_Table_Filters {
	protected $filter_list = [];

	protected static $FILTER_PARAMS = [
		'filter_stock',
		'filter_prs',
	];

	protected static $FILTER_STOCK_OPTIONS = [
		'instock'     => 'In Stock',
		'outofstock'  => 'Out of stock',
		'onbackorder' => 'On backorder',
	];

	/**
     * Returns filter parameters
     *
	 * @return array
	 */
	protected static function get_filter_params() {
		$filter_params = [];
		foreach ( static::$FILTER_PARAMS as $key ) {
			$filter_params[ $key ] = Priceshape_Request::priceshape_get_by_key( $key );
		}

		return $filter_params;
	}

	/**
	 * Calls filter for method
     *
	 */
	private function get_filters() {
		$filters       = 0;
		$filter_params = self::get_filter_params();

		foreach ( $this->filter_list as $filter_item ) {
			$method = "get_filter{$filter_item}";
			if ( method_exists( $this, $method ) ) {
				$this->{$method}( $filter_params );
				$filters ++;
			}
		}

		if ( $filters ) {
			submit_button( __( 'Filter' ), '', 'filter_action', false, [ 'id' => 'post-query-submit' ] );
		}
	}

	/**
     * A filter by stock status
	 *
	 * @param array $filter_params - an array of filter parameters
	 */
	protected function get_filterStock( $filter_params = [] ) {
		$has_filter_stock = isset( $filter_params['filter_stock'] ); ?>
        <select name="filter_stock" id="filter-stock">
            <option value="" <?php echo $has_filter_stock ? '' : 'selected' ?>>Filter by stock status</option>
			<?php foreach ( static::$FILTER_STOCK_OPTIONS as $key => $value ) : ?>
                <option value="<?php echo $key; ?>" <?php echo( ( $has_filter_stock && $filter_params['filter_stock'] == $key ) ? 'selected' : '' ); ?> >
					<?php echo $value; ?>
                </option>
			<?php endforeach; ?>
        </select>
		<?php
	}

	/**
	 * A filter by PRS status
	 *
	 * @param array $filter_params - an array of filter parameters
	 */
	protected function get_filterPRS( $filter_params = [] ) {
		$has_filter_prs     = isset( $filter_params['filter_prs'] );
		$filter_prs_options = [
			'prs-on'  => 'PRS ON',
			'prs-off' => 'PRS OFF',
		];
		?>
        <select name="filter_prs" id="filter-rps">
            <option value <?php $has_filter_prs ? '' : 'selected' ?> >Filter by PRS Status</option>
			<?php
			foreach ( $filter_prs_options as $key => $value ) {
				?>
                <option value="<?php echo $key; ?>" <?php echo( ( $has_filter_prs && $filter_params['filter_prs'] == $key ) ? 'selected' : '' ); ?> ><?php echo $value; ?></option>
				<?php
			}
			?>
        </select>
		<?php
	}
}
