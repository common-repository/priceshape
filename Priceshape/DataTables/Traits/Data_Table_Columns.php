<?php

namespace Priceshape\DataTables\Traits;

/**
 * Trait Data_Table_Columns
 * @package Priceshape\DataTables\Traits
 */
trait Data_Table_Columns {
	use Data_Table_Column_Mutators;

	protected static $COLUMNS = [
		'cb'         => '<input type="checkbox" />',
		'id'         => 'ID',
		'name'       => 'Name',
		'attributes' => 'Attributes',
		'sku'        => 'Sku',
		'stock'      => 'Stock',
	];

	protected static $SORTABLE_COLUMNS = [
		'id'            => [ 'id', true ],
		'name'          => [ 'name', false ],
		'sku'           => [ 'sku', false ],
		'price'         => [ 'price', false ],
		'stock'         => [ 'stock', false ],
		'cost_of_goods' => [ 'cost_of_goods', false ],
	];

	/**
	 * Returns columns
	 *
	 * @return array
	 */
	function get_columns() {
		return self::get_custom_columns();
	}

	/**
	 * Returns sortable columns
	 *
	 * @return array
	 */
	function get_sortable_columns() {
		return self::get_columns_sortable();
	}

	/**
	 * Returns custom columns
	 *
	 * @param array $custom_columns - an array of columns
	 *
	 * @return array
	 */
	protected static function get_custom_columns( $custom_columns = [] ) {
		$columns = array_merge( static::$COLUMNS, $custom_columns );
		if ( ! class_exists( 'WC_COG_Loader' ) && isset( $columns['cost_of_goods'] ) ) {
			unset( $columns['cost_of_goods'] );
		}

		return $columns;
	}

	/**
	 * Returns sortable columns
	 *
	 * @param array $custom_columns
	 *
	 * @return array - an array of sortable columns
	 */
	protected static function get_columns_sortable( $custom_columns = [] ) {
		$columns = array_merge( static::$SORTABLE_COLUMNS, $custom_columns );
		if ( ! class_exists( 'WC_COG_Loader' ) && isset( $columns['cost_of_goods'] ) ) {
			unset( $columns['cost_of_goods'] );
		}

		return $columns;
	}
}
