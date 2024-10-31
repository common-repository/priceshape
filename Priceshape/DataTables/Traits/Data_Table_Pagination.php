<?php

namespace Priceshape\DataTables\Traits;

/**
 * Trait Data_Table_Pagination
 * @package Priceshape\DataTables\Traits
 */
trait  Data_Table_Pagination {
	private $_pagination;

	/**
	 * Pagination function
	 *
	 * @param $which - position of pagination
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}
		$output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$page_links = [];
		$this->set_pagination_page_links( $page_links, $total_pages, $which );
		$pagination_links_class = $this->get_pagination_links_class();
		$output                 .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";
		echo $this->_pagination;
	}

	/**
	 * Returns a current pagination url
	 *
	 * @return mixed
	 */
	private function get_current_pagination_url() {
		$removable_query_args = wp_removable_query_args();
		$current_url          = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url          = remove_query_arg( $removable_query_args, $current_url );

		return add_query_arg( $this->get_additional_query_params(), $current_url );
	}

	/**
	 * Sets pagination page links
	 *
	 * @param $page_links - page links
	 * @param $total_pages - number of pages
	 * @param $which - position of pagination
	 */
	private function set_pagination_page_links( &$page_links, $total_pages, $which ) {
		$current       = $this->get_pagenum();
		$current_url   = $this->get_current_pagination_url();
		$disable_first = $disable_last = $disable_prev = $disable_next = false;

		if ( 1 == $current ) {
			$disable_first = true;
			$disable_prev  = true;
		}

		if ( 2 == $current ) {
			$disable_first = true;
		}

		if ( $current == $total_pages ) {
			$disable_last = true;
			$disable_next = true;
		}

		if ( $total_pages - 1 == $current ) {
			$disable_last = true;
		}

		$this->pagination_disable_first( $page_links, $disable_first, $current_url );
		$this->pagination_disable_prev( $page_links, $disable_prev, $current, $current_url );
		$this->pagination_set_side( $page_links, $current, $total_pages, $which );
		$this->pagination_disable_next( $page_links, $disable_next, $current, $current_url, $total_pages );
		$this->pagination_disable_last( $page_links, $disable_last, $current_url, $total_pages );
	}

	/**
	 * Disabled first pagination link
	 *
	 * @param $page_links - page links
	 * @param $disable_first - a flag
	 * @param $current_url - a current url
	 */
	private function pagination_disable_first( &$page_links, $disable_first, $current_url ) {
		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';

			return;
		}

		$page_links[] = sprintf( "<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			__( 'First page' ),
			'&laquo;'
		);
	}

	/**
	 * Disabled previous pagination link
	 *
	 * @param $page_links - page links
	 * @param $disable_prev - a flag
	 * @param $current - a current position
	 * @param $current_url - a current url
	 */
	private function pagination_disable_prev( &$page_links, $disable_prev, $current, $current_url ) {
		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';

			return;
		}

		$page_links[] = sprintf( "<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
			esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
			__( 'Previous page' ),
			'&lsaquo;'
		);
	}

	/**
	 * Disabled next pagination link
	 *
	 * @param $page_links - page links
	 * @param $disable_next - a flag
	 * @param $current - a current position
	 * @param $current_url - a current url
	 * @param $total_pages - number of pages
	 */
	private function pagination_disable_next( &$page_links, $disable_next, $current, $current_url, $total_pages ) {
		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';

			return;
		}

		$page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
			esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
			__( 'Next page' ),
			'&rsaquo;'
		);
	}

	/**
	 * Disabled last pagination link
	 *
	 * @param $page_links - page links
	 * @param $disable_last - a flag
	 * @param $current_url - a current url
	 * @param $total_pages - number of pages
	 */
	private function pagination_disable_last( &$page_links, $disable_last, $current_url, $total_pages ) {
		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';

			return;
		}

		$page_links[] = sprintf( "<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
			esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
			__( 'Last page' ),
			'&raquo;'
		);
	}

	/**
	 * Set side of pagination
	 *
	 * @param $page_links - page links
	 * @param $current - a current position
	 * @param $total_pages - number of pages
	 * @param $which - location of pagination
	 */
	private function pagination_set_side( &$page_links, $current, $total_pages, $which ) {
		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;
	}

	/**
	 * Returns a pagination links class
	 *
	 * @param string $class - a class
	 *
	 * @return string
	 */
	private function get_pagination_links_class( $class = 'pagination-links' ) {
		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		if ( ! empty( $infinite_scroll ) ) {
			$class .= ' hide-if-js';
		}

		return $class;
	}
}
