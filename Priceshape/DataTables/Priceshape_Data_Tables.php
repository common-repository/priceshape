<?php
/**
 * Base class for displaying a list of items in an ajaxified HTML table.
 *
 */

namespace Priceshape\DataTables;

use Priceshape\Core\Priceshape_Helper;
use Priceshape\Core\Priceshape_Request;
use Priceshape\DataTables\Traits\Data_Table_Columns;
use Priceshape\DataTables\Traits\Data_Table_Filters;
use Priceshape\DataTables\Traits\Data_Table_Pagination;
use Priceshape\Priceshape_Queries;
use WP_List_Table;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class Priceshape_Data_Tables
 * @package Priceshape\DataTables
 */
abstract class Priceshape_Data_Tables extends WP_List_Table {
	use Data_Table_Columns, Data_Table_Pagination, Data_Table_Filters;

	protected $type;

	const FILTER_BY_STOCK_STATUS = 'Stock';
	const FILTER_BY_PRICESHAPE_OPTION = 'PRS';

	function __construct() {
		parent::__construct( [
			'singular' => 'product',
			'plural'   => 'products',
			'ajax'     => false,
		] );

		$this->bulk_action_handler();
	}

	/**
	 * Returns products info by query parameters
	 *
	 * @param array $query_params - query parameters
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	protected function get_items( $query_params = [] ) {
		return Priceshape_Queries::priceshape_get_all_products_info( $query_params, $this->type );
	}

	/**
	 * Prepares items for a request
	 *
	 * @param null $search - searching text
	 */
	function prepare_items( $search = null ) {
		$hidden                = [];
		$columns               = $this->get_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$per_page     = 50;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$query_params = [
			'orderby' => Priceshape_Request::priceshape_get_by_key( 'orderby', 'id' ),
			'order'   => Priceshape_Request::priceshape_get_by_key( 'order', 'asc' ),
			'limit'   => $per_page,
			'offset'  => $offset,
			'search'  => $search,
			'filter'  => self::get_filter_params(),
		];

		$data        = $this->get_items( $query_params );
		$total_items = $data['total_count'];
		unset( $data['total_count'] );

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			's'           => $search,
		] );

		$this->items = $data;
	}

	/**
	 * Gets features on page
	 *
	 */
	protected function get_other_features() {

	}

	/**
     * Table nav features
     *
	 * @param $which - position of features
	 */
	function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		} ?>
        <div class="alignleft actions">
			<?php
			$this->get_filters();
			$this->get_other_features();
			?>
        </div>
		<?php
	}

	/**
	 * Returns additional query parameters
	 *
	 * @return array
	 */
	protected function get_additional_query_params() {
		$search = $this->_pagination_args['s'];

		return [
			's'            => $search,
			'filter_stock' => ( isset( $_REQUEST['filter_stock'] ) ? sanitize_text_field( $_REQUEST['filter_stock'] ) : '' ),
		];
	}
}
