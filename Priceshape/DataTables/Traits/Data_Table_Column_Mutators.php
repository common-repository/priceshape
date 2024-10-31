<?php

namespace Priceshape\DataTables\Traits;

/**
 * Trait Data_Table_Column_Mutators
 * @package Priceshape\DataTables\Traits
 */
trait  Data_Table_Column_Mutators {
	/**
	 * Returns a column name of the item
	 *
	 * @param $item - an array
	 * @param $column_name - column name
	 *
	 * @return mixed|string|true
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'sku':
			case 'price':
			case 'stock':
			case 'date':
				return $item[ $column_name ];
			default:
				return print_r( $item[ $column_name ], true );
		}
	}

	/**
	 * Returns a checkbox
	 *
	 * @param $item - an array with information about column
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="products_ids[]" value="%s" />', $item['id'] );
	}

	/**
	 * Returns a string of the column name
	 *
	 * @param $item - an array with information about column
	 *
	 * @return string
	 */
	function column_name( $item ) {
		return sprintf( '<strong><a href="%1$s">%2$s</a></strong>', $item['url'], $item['name'] );
	}

	/**
	 * Returns a string of the new column value
	 *
	 * @param $item - an array with information about column
	 *
	 * @return string
	 */
	function column_new_value( $item ) {
		$approve_html = '<a class="price-operation" href="#" data-price-handler="%s" data-prod="%s" data-field_id="%s">Approve</a>';
		$decline_html = '<a class="price-operation" href="#" data-price-handler="%s" data-prod="%s" data-field_id="%s">Decline</a>';

		$actions = [
			'price-approve' => sprintf( $approve_html, 'approve', $item['id'], $item['field_id'] ),
			'price-decline' => sprintf( $decline_html, 'decline', $item['id'], $item['field_id'] ),
		];

		return sprintf( '<strong>%1$s</strong>%2$s', $item['new_value'], $this->row_actions( $actions ) );
	}

	/**
	 * Returns a string of the old column value
	 *
	 * @param $item - an array with information about column
	 *
	 * @return string
	 */
	function column_old_value( $item ) {
		$old_value = isset( $item[ $item['field'] ] ) ? $item[ $item['field'] ] : 'unknown';

		return sprintf( '<span>%1$s</span>', $old_value );
	}

	/**
	 * Returns a string of the cost of goods value
	 *
	 * @param $item - an array with information about cost of goods
	 *
	 * @return string
	 */
	function column_cost_of_goods( $item ) {
		return sprintf( '<span>%1$s</span>', $item['cost_of_goods'] );
	}

	/**
	 * Returns a column attributes
	 *
	 * @param $item - an array with an attributes key
	 *
	 * @return string
	 */
	function column_attributes( $item ) {
		$attrs = json_decode( $item['attributes'], true );
		if ( empty( $attrs ) ) {
			return '<span>No variations</span>';
		}

		$html = '';
		foreach ( $attrs as $key => $attr ) {
			$html .= "<div>$key: <strong>$attr</strong></div>";
		}

		return $html;
	}

	/**
	 * Returns a string for switching priceshape status
	 *
	 * @param $item - an array with column status
	 *
	 * @return string
	 */
	function column_prs( $item ) {
		$text = is_null( $item['prs'] ) ? 'OFF' : 'ON';
		if ( is_null( $item['prs'] ) ) {
			$actions = [
				'prs-on' => sprintf( '<a class="priceshape-turn" href="#" data-turn="%s" data-prod="%s">Add to PriceShape</a>', 'prs-on', $item['id'] ),
			];
		} else {
			$actions = [
				'prs-off' => sprintf( '<a class="priceshape-turn" href="#" data-turn="%s" data-prod="%s">Remove from PriceShape</a>', 'prs-off', $item['id'] ),
			];
		}

		return sprintf( '<strong>%1$s</strong>%2$s', $text, $this->row_actions( $actions ) );
	}
}
