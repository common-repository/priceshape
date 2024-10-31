<?php

namespace Priceshape;

use Exception;
use Priceshape\Core\Priceshape_Helper;
use Throwable;

/**
 * Priceshape_Queries class
 *
 * @package Priceshape
 */
class Priceshape_Queries {
	const FOREIGN_KEY_PREFIX = 'fk_';
	const UNIQUE_INDEX_PREFIX = 'uk_';

	const UPDATING_FIELD_PRICE = 'price';
	const UPDATING_FIELD_COST_OF_GOODS = 'cost_of_goods';
	const UPDATING_FIELDS_LIST = [
		self::UPDATING_FIELD_PRICE,
		self::UPDATING_FIELD_COST_OF_GOODS,
	];

	const TABLE_LOGS = 'prs_logs';
	const TABLE_HISTORY = 'prs_history';
	const TABLE_PRODUCTS = 'prs_products';
	const TABLE_PLUGIN_INFO = 'prs_plugin_info';
	const TABLE_PRODUCT_UPDATES = 'prs_product_updates';
	const TABLE_MIGRATE = 'prs_migrate';

	const ORIGIN_PRICESHAPE = 'priceshape';
	const ORIGIN_WOOCOMMERCE = 'woocommerce';
	const QUERY_PART_TYPE_TRIGGERS = 'triggers';
	const QUERY_PART_TYPE_TABLES = 'tables';

	const ORIGINS = [
		self::ORIGIN_PRICESHAPE,
		self::ORIGIN_WOOCOMMERCE,
	];

	const DEFAULT_QUERY_PARAMS = [
		'orderby' => 'id',
		'order'   => 'asc',
		'limit'   => null,
		'offset'  => null,
		'search'  => null,
		'filter'  => null,
	];

	const QUERY_PART_TYPE_ALL = 'all';
	const QUERY_PART_TYPE_ID = 'id';
	const QUERY_PART_TYPE_COUNT = 'count';
	const QUERY_PART_TYPES = [
		self::QUERY_PART_TYPE_ALL,
		self::QUERY_PART_TYPE_ID,
		self::QUERY_PART_TYPE_COUNT,
	];

	/**
	 * Gets a specific file name
	 *
	 * @param $files_directory - the directory that contains files
	 * @param $type - type of file
	 * @param $variables  - an array of variables
	 */
	public static function priceshape_run_sql_files( $files_directory, $type, $variables ) {
		global $wpdb;

		$directory_tables = scandir( $files_directory );

		foreach ( $directory_tables as $key => $file_name ) {

			if ( self::QUERY_PART_TYPE_TABLES == $type && 'sql' == pathinfo( $file_name, PATHINFO_EXTENSION ) ) {
				$table_name = explode( '__', $file_name );
				$table_name = self::priceshape_add_table_prefix( pathinfo( ! isset( $table_name[1] ) ? $table_name[0] : $table_name[1] )['filename'] );
			} else {
				$table_name = self::priceshape_add_table_prefix( pathinfo( $file_name )['filename'] );
			}

			$trigger_name = pathinfo( $file_name )['filename'];

			if ( ( self::QUERY_PART_TYPE_TABLES == $type && $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name && false !== self::priceshape_check_migrate_table( $file_name ) )
			     || ( self::QUERY_PART_TYPE_TRIGGERS == $type && $wpdb->get_var( "SHOW TRIGGERS WHERE `Trigger` = '$trigger_name'" ) ) ) {
				continue;
			}

			self::priceshape_run_sql_file( $files_directory, $type, $variables, $file_name );
		}
	}

	/**
	 * Gets sql-query from the file
	 *
	 * @param $files_directory - the directory that contains files
	 * @param $type - type of file
	 * @param $variables  - an array of variables
	 * @param null $file_name - a file that contains an sql-query
	 */
	public static function priceshape_run_sql_file( $files_directory, $type, $variables, $file_name = null ) {
		if ( ! is_file( $files_directory . $file_name ) || 'sql' !== pathinfo( $file_name, PATHINFO_EXTENSION ) ) {
			return;
		}
		$create_query_sql = file_get_contents( $files_directory . $file_name );

		foreach ( $variables as $variable_key => $variable ) {
			$create_query_sql = str_replace( "{{" . $variable_key . "}}", $variable, $create_query_sql );
		}

		self::priceshape_execute_core_query( $create_query_sql, $type, $file_name );
	}

	/**
	 * This returns a directory path for sql files
	 *
	 * @param $directory - a specific directory
	 *
	 * @return string
	 */
	public static function priceshape_query_dir_path( $directory ) {
		return plugin_dir_path( __FILE__ ) . 'query/' . $directory;
	}

	/**
	 * Returns an array of variables
	 *
	 * @param null $prepared_ids - a string to define a returning array
	 *
	 * @return array
	 */
	public static function priceshape_tables_variables( $prepared_ids = null ) {
		$post_meta_table                  = self::priceshape_add_table_prefix( "postmeta" );
		$posts_table                      = self::priceshape_add_table_prefix( "posts" );
		$wc_product_meta_lookup           = self::priceshape_add_table_prefix( "wc_product_meta_lookup" );
		$options_table                    = self::priceshape_add_table_prefix( "options" );
		$history_table                    = self::priceshape_add_table_prefix( self::TABLE_HISTORY );
		$products_table                   = self::priceshape_add_table_prefix( self::TABLE_PRODUCTS );
		$product_updates_table            = self::priceshape_add_table_prefix( self::TABLE_PRODUCT_UPDATES );
		$plugin_info_table                = self::priceshape_add_table_prefix( self::TABLE_PLUGIN_INFO );
		$table_log                        = self::priceshape_add_table_prefix( self::TABLE_LOGS );
		$history_constraint_table         = self::priceshape_add_foreign_key_prefix( self::TABLE_HISTORY );
		$product_constraint_table         = self::priceshape_add_foreign_key_prefix( self::TABLE_PRODUCTS );
		$product_updates_constraint_table = self::priceshape_add_foreign_key_prefix( self::TABLE_PRODUCT_UPDATES );
		$product_updates_unique_table     = self::priceshape_add_unique_index_prefix( [ 'priceshape_product_id', 'field' ] );
		$origins                          = Priceshape_Helper::priceshape_implodes_variables( self::ORIGINS );
		$updating_fields_list             = Priceshape_Helper::priceshape_implodes_variables( self::UPDATING_FIELDS_LIST );
		$origin_priceshape                = self::ORIGIN_PRICESHAPE;
		$origin_woocommerce               = self::ORIGIN_WOOCOMMERCE;
		$updating_field_price             = self::UPDATING_FIELD_PRICE;
		$updating_field_cost              = self::UPDATING_FIELD_COST_OF_GOODS;
		$prepared_ids                     = esc_sql( $prepared_ids );

		return
			$table_variables = [
				'POST_META_TABLE'                  => $post_meta_table,
				'POSTS_TABLE'                      => $posts_table,
				'PRODUCTS_TABLE'                   => $products_table,
				'PRODUCTS_CONSTRAINT_TABLE'        => $product_constraint_table,
				'PRODUCT_UPDATES_TABLE'            => $product_updates_table,
				'PRODUCT_UPDATES_CONSTRAINT_TABLE' => $product_updates_constraint_table,
				'PRODUCT_UPDATES_UNIQUE_TABLE'     => $product_updates_unique_table,
				'OPTIONS_TABLE'                    => $options_table,
				'HISTORY_TABLE'                    => $history_table,
				'HISTORY_CONSTRAINT_TABLE'         => $history_constraint_table,
				'PLUGIN_INFO_TABLE'                => $plugin_info_table,
				'TABLE_LOGS'                       => $table_log,
				'PREPARED_IDS'                     => $prepared_ids,
				'UPDATING_FIELD_PRICE'             => $updating_field_price,
				'UPDATING_FIELD_COST'              => $updating_field_cost,
				'WC_PRODUCT_META_LOOKUP'           => $wc_product_meta_lookup,
				'ORIGIN_PRICESHAPE'                => $origin_priceshape,
				'ORIGIN_WOOCOMMERCE'               => $origin_woocommerce,
				'ORIGINS'                          => $origins,
				'UPDATING_FIELDS_LIST'             => $updating_fields_list
			];
	}

	/**
	 * Create triggers
	 *
	 */
	public static function priceshape_create_triggers() {
		$files_directory = self::priceshape_query_dir_path( 'triggers/' );
		self::priceshape_run_sql_files( $files_directory, self::QUERY_PART_TYPE_TRIGGERS, self::priceshape_tables_variables() );
	}

	/**
	 * Executes sql
	 *
	 * @param $sql - sql-query
	 * @param $type - table type
	 * @param array $params - additional parameters
	 */
	private static function priceshape_execute_core_query( $sql, $type, $params = [] ) {
		if ( self::priceshape_run_query( $sql ) ) {
			self::priceshape_insert_migrate_table( $params );
		} else {
			Priceshape_Plugin::priceshape_send_report_to_support_mail( $type, $params );
			wp_die( 'Sorry, an error occurred while processing your request.' );
		}
	}

	/**
	 * Creates tables
	 *
	 */
	public static function priceshape_create_tables() {
		$files_directory = self::priceshape_query_dir_path( 'tables/create/' );
		self::priceshape_run_sql_files( $files_directory, self::QUERY_PART_TYPE_TABLES, self::priceshape_tables_variables() );
	}

	/**
	 * Creates migrate table
	 *
	 */
	public static function priceshape_create_table_migrate() {
		$table_migrate = self::priceshape_add_table_prefix( self::TABLE_MIGRATE );

		$migrate_variables = [
			'MIGRATE_TABLE' => $table_migrate,
		];

		$files_directory = self::priceshape_query_dir_path( 'tables/' );
		self::priceshape_run_sql_files( $files_directory, self::QUERY_PART_TYPE_TABLES, $migrate_variables );
	}

	/**
	 * Inserts name of the table
	 *
	 * @param $file_name - name of the table with sql extension
	 */
	public static function priceshape_insert_migrate_table( $file_name ) {
		global $wpdb;

		$sql = "INSERT INTO " . self::priceshape_add_table_prefix( 'prs_migrate' ) . " ( `file_name` )
				VALUE ( '" . $file_name . "' )";
		$wpdb->query( $sql );
	}

	/**
	 * Checks if exists a migrate table
	 *
	 * @param $file_name - table name with sql extension
	 *
	 * @return mixed
	 */
	public static function priceshape_check_migrate_table( $file_name ) {
		global $wpdb;

		$sql = "SELECT `file_name`
				FROM " . self::priceshape_add_table_prefix( 'prs_migrate' ) . "
				WHERE `file_name` =  '" . $file_name . "' ";

		return $wpdb->query( $sql );
	}

	/**
	 * Returns plugin parameters
	 *
	 * @param $option_name - searching option
	 *
	 * @return mixed
	 */
	public static function priceshape_get_plugin_param( $option_name ) {
		global $wpdb;

		$sql = "
            SELECT option_value
            FROM " . self::priceshape_add_table_prefix( self::TABLE_PLUGIN_INFO ) . "
            WHERE  option_name = '" . esc_sql( $option_name ) . "'";

		return $wpdb->get_var( $sql );
	}

	/**
	 * Inserts plugin parameters
	 *
	 * @param $params - parameters we insert into plugin info table
	 */
	public static function priceshape_insert_plugin_params( $params ) {
		$prepared_params = self::priceshape_prepare_params_to_insert( $params );
		$sql             = "
            INSERT INTO " . self::priceshape_add_table_prefix( self::TABLE_PLUGIN_INFO ) . " (option_name, option_value) 
            VALUES " . $prepared_params . " 
            ON DUPLICATE KEY UPDATE option_value = VALUES (option_value);";

		self::priceshape_run_query( $sql );
		Priceshape_Plugin::priceshape_send_report_to_support_mail( 'plugParams', $params );
	}

	/**
	 * Prepares parameters for inserting
	 *
	 * @param array $params - parameters we prepare
	 *
	 * @return string
	 */
	public static function priceshape_prepare_params_to_insert( array $params ) {
		$prepared_params = [];

		foreach ( $params as $key => $value ) {
			$prepared_params[] = "('" . esc_sql( $key ) . "', '" . esc_sql( $value ) . "')";
		}

		return implode( ", ", $prepared_params );
	}

	/**
	 * Removes plugin parameters
	 *
	 * @param $option_name - parameter we want to remove
	 */
	public static function priceshape_remove_plugin_param( $option_name ) {
		$sql = "
            DELETE FROM " . self::priceshape_add_table_prefix( self::TABLE_PLUGIN_INFO ) . " 
            WHERE option_name = '" . esc_sql( $option_name ) . "';";

		self::priceshape_run_query( $sql );
	}

	/**
	 * Updates product values
	 *
	 * @param $products - products for update
	 */
	public static function priceshape_update_product_values( $products ) {
		Priceshape_Plugin::priceshape_send_report_to_support_mail( 'newValues', $products );
		$post_meta_table       = self::priceshape_add_table_prefix( "postmeta" );
		$table_product_updates = self::priceshape_add_table_prefix( self::TABLE_PRODUCT_UPDATES );
		$updates      = [];
		$current_time = current_time( 'mysql' );

		foreach ( esc_sql( $products ) as $product ) {
			$priceshape_product_id = self::priceshape_get_prs_product_id_query( $product['id'] );

			foreach ( self::UPDATING_FIELDS_LIST as $field ) {
				if ( isset( $product[ $field ]['new'] ) ) {
					$meta_field = $field == self::UPDATING_FIELD_PRICE ? '_price' : '_wc_cog_cost';
					$sql        = "
                        SELECT pm.meta_value
                            FROM {$post_meta_table} as pm
                        INNER JOIN wp_prs_products as pru 
                            ON pru.product_id = pm.post_id
                        WHERE 
                            pm.meta_key = '{$meta_field}' AND 
                            pru.product_id IN ({$product['id']});
                    ";

					$next    = false;
					$results = self::priceshape_get_sql_results( $sql );
					foreach ( $results as $result ) {
						if ( $result["meta_value"] == $product[ $field ]['new'] ) {
							$next = true;
						}
					}

					if ( $next ) {
						continue;
					}
					$updates[] = "(({$priceshape_product_id}), '{$field}', {$product[$field]['new']}, '$current_time')";
				}
			}
		}

		if ( empty( $updates ) ) {
			Priceshape_Plugin::priceshape_send_report_to_support_mail( 'emptyValues' );
			return;
		}

		$updates = implode( ',', $updates );
		$query   = "
            INSERT IGNORE INTO {$table_product_updates} (`priceshape_product_id`, `field`, `value`, `created_at`) 
            VALUES {$updates}
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `created_at` = VALUES(`created_at`);
        ";
		self::priceshape_run_query( $query );
	}

	/**
	 * Returns product id from products table
	 *
	 * @param $product_id - id of the product
	 *
	 * @return string
	 */
	private static function priceshape_get_prs_product_id_query( $product_id ) {
		$table_products = self::priceshape_add_table_prefix( self::TABLE_PRODUCTS );
		return "SELECT id FROM {$table_products} WHERE product_id = $product_id";
	}

	/**
	 * Returns all products info
	 *
	 * @param array $args - ids of products
	 * @param string $type - type of products
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function priceshape_get_all_products_info( $args = [], $type = 'prs' ) {
		$sql_count             = self::priceshape_get_query_for_all_products_info( $args, 'count', $type );
		$sql_select            = self::priceshape_get_query_for_all_products_info( $args, 'all', $type );
		$total_count           = self::priceshape_get_sql_results( $sql_count );
		$result                = self::priceshape_get_sql_results( $sql_select );
		$result['total_count'] = current( $total_count )['count'];

		return $result;
	}

	/**
	 * Returns all products ids
	 *
	 * @param string $type - type of products
	 *
	 * @return array|mixed
	 * @throws Exception
	 */
	public static function priceshape_get_all_products_ids( $type = 'prs' ) {
		$sql              = self::priceshape_get_query_for_all_products_info( self::priceshape_get_query_params(), 'id', $type );
		$all_products_ids = self::priceshape_get_sql_results( $sql );
		if ( empty( $all_products_ids ) ) {
			return [];
		}

		return $all_products_ids;
	}

	/**
	 * Gets ids of products we want to update
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function priceshape_get_all_product_update_ids() {
		return array_map( function ( $product ) {
			return $product['field_id'];
		}, self::priceshape_get_all_products_ids( 'prs' ) );
	}

	/**
	 * Returns an sql request we use to get info about products
	 *
	 * @param array $args - product info array
	 * @param string $query_part_type - a query part type for all products
	 * @param string $type - type of products
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function priceshape_get_query_for_all_products_info( $args = [], $query_part_type = self::QUERY_PART_TYPE_ALL, $type = 'prs' ) {
		if ( ! in_array( $query_part_type, self::QUERY_PART_TYPES ) ) {
			throw new Exception( 'Unknown value of  $whichPart = ' . $query_part_type . ',  in' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
		}

		$posts_table           = self::priceshape_add_table_prefix( "posts" );
		$post_meta_table       = self::priceshape_add_table_prefix( "postmeta" );
		$products_table        = self::priceshape_add_table_prefix( self::TABLE_PRODUCTS );
		$product_updates_table = self::priceshape_add_table_prefix( self::TABLE_PRODUCT_UPDATES );

		$params = array_merge( self::DEFAULT_QUERY_PARAMS, $args );
		$params = array_map( function ( $option ) {
			return esc_sql( $option );
		}, $params );

		$query_select_parts = [ 'image', 'sku', 'stock', 'price', 'cost_of_goods', 'attributes' ];
		$columns            = [
			'posts.id',
			'posts.guid AS url',
			'posts.post_type AS type',
			'posts.post_title AS title',
			'posts.post_name AS name',
		];

		$from    = "{$posts_table} as posts";
		$filters = [ '1 = 1' ];
		$where   = [
			"post_type = 'product_variation' OR post_type = 'product'",
			"posts.id NOT IN (SELECT post_parent FROM {$posts_table} AS pp WHERE pp.post_parent = posts.id AND post_type = 'product_variation')",
			"posts.id NOT IN (SELECT post_id FROM {$post_meta_table} AS pm WHERE pm.post_id = posts.id AND meta_key = '_children')",
		];

		foreach ( $query_select_parts as $query_select_part ) {
			$columns[] = self::priceshape_get_sql_query_select_part( $query_select_part );
		}

		if ( isset( $params['filter']['filter_stock'] ) && $params['filter']['filter_stock'] ) {
			$filters[] = "stock = '{$params['filter']['filter_stock']}'";
		}

		if ( isset( $params['filter']['filter_prs'] ) ) {
			if ( 'prs-on' == $params['filter']['filter_prs'] ) {
				$filters[] = "prs IS NOT NULL";
			} else if ( 'prs-off' == $params['filter']['filter_prs'] ) {
				$filters[] = "prs IS NULL";
			}
		}

		if ( $params['search'] ) {
			$filters[] = "(id LIKE '%{$params['search']}%' OR name LIKE '%{$params['search']}%' OR sku LIKE '%{$params['search']}%')";
		}

		if ( 'all' == $type ) {
			$columns[] = self::priceshape_get_sql_query_select_part( 'prsOnOff', 'prs' );
		}

		if ( 'all-prs-on' == $type ) {
			$where[] = "posts.id NOT IN (SELECT product_id FROM {$products_table})";
		}

		if ( 'all-prs-off' == $type ) {
			$where[] = "posts.id IN (SELECT product_id FROM {$products_table})";
		}

		if ( 'prs' === $type ) {
			$columns = array_merge( $columns, [
				"pru.id AS field_id",
				"pru.value AS new_value",
				"pru.field AS field",
				"pru.created_at AS date",
				"pmt.meta_value as old_value"
			] );

			$joins = implode( ' ', [
				"INNER JOIN {$posts_table} as posts ON posts.ID = pr.product_id",
				"INNER JOIN {$product_updates_table} as pru ON pr.id = pru.priceshape_product_id",
				"INNER JOIN {$post_meta_table} as pmt ON pmt.post_id = pr.product_id"
			] );

			$where = "pmt.meta_key = '_price' AND pmt.meta_key IS NOT NULL";
			$from = "$products_table as pr $joins";
		}
		$filters = implode( ' AND ', $filters );

		if ( 'prs' !== $type ) {
			$where   = implode( ' AND ', $where );
		}

		$columns = implode( ',', $columns );
		$order_by = "ORDER BY {$params['orderby']} {$params['order']}";
		$limit    = isset( $params['limit'], $params['offset'] ) ? "LIMIT {$params['limit']} OFFSET {$params['offset']}" : "";
		$select = $query_part_type === self::QUERY_PART_TYPE_COUNT ? "COUNT(id) AS count" : '*';
		$query  = "SELECT $select FROM (SELECT $columns FROM $from WHERE $where) as p WHERE $filters";
		if ( $query_part_type === self::QUERY_PART_TYPE_ALL ) {
			$query = "$query $order_by $limit";
		}

		return $query;
	}

	/**
	 * Adds new product to priceshape
	 *
	 * @param $new_products_ids - ids of products we want to add
	 */
	public static function priceshape_add_to_prs( $new_products_ids ) {
		if ( empty( $new_products_ids ) ) {
			return;
		}

		Priceshape_Plugin::priceshape_send_products_ids_to_support_mail( $new_products_ids );
		$ids_for_adding    = self::priceshape_prepare_products_ids( $new_products_ids );
		$prepared_products = implode( '),(', $ids_for_adding );

		$sql = "
            INSERT INTO " . self::priceshape_add_table_prefix( self::TABLE_PRODUCTS ) . " (product_id) 
            VALUES (" . esc_sql( $prepared_products ) . ") 
            ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);";

		self::priceshape_run_query( $sql );
	}

	/**
	 * Remove product from products table
	 *
	 * @param $products_ids (string|array)
	 */
	public static function priceshape_remove_from_prs( $products_ids ) {
		if ( empty( $products_ids ) ) {
			return;
		}
		Priceshape_Plugin::priceshape_send_products_ids_to_support_mail( $products_ids, 'remov' );
		$ids_for_removing  = self::priceshape_prepare_products_ids( $products_ids );
		$prepared_products = implode( ',', $ids_for_removing );
		$sql               = "
            DELETE FROM " . self::priceshape_add_table_prefix( self::TABLE_PRODUCTS ) . " 
            WHERE product_id IN (" . esc_sql( $prepared_products ) . ");";

		self::priceshape_run_query( $sql );
	}

	/**
	 * Insert All Products ID to PRS Products Table
	 *
	 */
	public static function priceshape_add_all_to_prs() {
		$all_products_ids_string = self::priceshape_get_all_products_ids( 'all-prs-on' );
		self::priceshape_add_to_prs( $all_products_ids_string );
	}

	/**
	 * Removes all products from product table
	 *
	 */
	public static function priceshape_remove_all_from_prs() {
		$sql = "DELETE FROM " . self::priceshape_add_table_prefix( self::TABLE_PRODUCTS ) . " WHERE 1;";
		self::priceshape_run_query( $sql );
	}

	/**
	 * Returns products info for xml
	 *
	 * @return mixed
	 */
	public static function priceshape_get_all_product_for_xml() {
		$posts_table    = self::priceshape_add_table_prefix( "posts" );
		$products_table = self::priceshape_add_table_prefix( self::TABLE_PRODUCTS );
		$image          = self::priceshape_get_sql_query_select_part( 'image' );
		$products_data  = self::priceshape_get_sql_query_select_part( 'productsData', 'data' );

		$sql = "
            SELECT 
                posts.id,
                posts.post_title AS title,
                posts.post_name AS name,
                posts.guid AS url,
                {$image},
                {$products_data} 
            FROM {$posts_table} as posts
            INNER JOIN {$products_table} as pr
                ON posts.ID = pr.product_id
            ORDER BY posts.id ";

		$limit = Priceshape_Plugin::priceshape_get_param( Priceshape_Plugin::PRODUCTS_LIMIT );

		if ( 0 != $limit ) {
			$sql .= " LIMIT $limit";
		}

		return self::priceshape_get_sql_results( $sql );
	}

	/**
	 * Updates post meta table during approving prices
	 *
	 * @param $prepared_ids - ids of products
	 */
	private static function priceshape_default_update_query( $prepared_ids ) {
		$files_directory = self::priceshape_query_dir_path( 'tables/update/' );
		$file_name       = 'default-update-query.sql';
		self::priceshape_run_sql_file( $files_directory, self::QUERY_PART_TYPE_TABLES, self::priceshape_tables_variables( $prepared_ids ), $file_name );
	}

	/**
	 * Inserts data into post meta table for only sales price products
	 *
	 * @param $prepared_ids - ids of products
	 */
	private static function priceshape_only_sale_price_insert( $prepared_ids ) {
		$files_directory = self::priceshape_query_dir_path( 'tables/insert/' );
		$file_name       = 'only-sale-price-insert.sql';
		self::priceshape_run_sql_file( $files_directory, self::QUERY_PART_TYPE_TABLES, self::priceshape_tables_variables( $prepared_ids ), $file_name );
	}

	/**
	 * Updates post meta table data for only sales price products
	 *
	 * @param $prepared_ids - ids of products
	 */
	private static function priceshape_only_sale_price_update( $prepared_ids ) {
		$files_directory = self::priceshape_query_dir_path( 'tables/update/' );
		$file_name       = 'only-sale-price-update.sql';
		self::priceshape_run_sql_file( $files_directory, self::QUERY_PART_TYPE_TABLES, self::priceshape_tables_variables( $prepared_ids ), $file_name );
	}

	/**
	 * Approves prices for products
	 *
	 * @param $field_ids - ids of products
	 */
	public static function priceshape_approve_prices( $field_ids ) {
		if ( empty( $field_ids ) ) {
			return;
		}

		if ( is_array( $field_ids ) ) {
			$field_ids = implode( ',', $field_ids );
		}

		$only_sale_price = Priceshape_Plugin::priceshape_get_param( Priceshape_Plugin::UPDATE_ONLY_SALE_PRICE );
		$queries         = $only_sale_price == Priceshape_Plugin::YES ? [
			self::priceshape_only_sale_price_insert( $field_ids ),
			'SET @is_prs = 1',
			self::priceshape_only_sale_price_update( $field_ids ),
			'SET @is_prs = 0',
		] : [
			'SET @is_prs = 1',
			self::priceshape_default_update_query( $field_ids ),
			'SET @is_prs = 0',
		];

		self::priceshape_run_transaction( $queries );
		self::priceshape_decline( $field_ids );
	}

	/**
	 * Deletes products from product updates table
	 *
	 * @param $field_ids - ids of products
	 */
	public static function priceshape_decline( $field_ids ) {
		if ( empty( $field_ids ) ) {
			return;
		}

		if ( is_array( $field_ids ) ) {
			$field_ids = implode( ',', $field_ids );
		}

		$prepared_ids          = esc_sql( $field_ids );
		$product_updates_table = self::priceshape_add_table_prefix( self::TABLE_PRODUCT_UPDATES );

		$sql = " DELETE FROM {$product_updates_table} WHERE id IN ({$prepared_ids});";
		self::priceshape_run_query( $sql );
	}

	/**
	 * Approves all products prices
	 *
	 */
	public static function priceshape_approve_all_prices() {
		$field_ids = self::priceshape_get_all_product_update_ids();
		self::priceshape_approve_prices( $field_ids );
	}

	/**
	 * Declines all product prices
	 *
	 */
	public static function priceshape_decline_all() {
		$field_ids = self::priceshape_get_all_product_update_ids();
		self::priceshape_decline( $field_ids );
	}

	/**
	 * Prepares products ids for adding or removing
	 *
	 * @param $unprepared_ids - product ids
	 *
	 * @return array
	 */
	private static function priceshape_prepare_products_ids( $unprepared_ids ) {
		if ( ! is_array( $unprepared_ids ) ) {
			return [ $unprepared_ids ];
		}
		if ( ! is_array( current( $unprepared_ids ) ) ) {
			return $unprepared_ids;
		}
		$extracted_ids = [];
		foreach ( $unprepared_ids as $array ) {
			$extracted_ids[] = $array['id'];
		}

		return $extracted_ids;
	}

	/**
	 * Checks if table exists
	 *
	 * @param $table_name - name of the table
	 *
	 * @return bool
	 */
	public static function priceshape_table_exist( $table_name ) {
		global $wpdb;

		$result = $wpdb->get_var( "SHOW TABLES LIKE '" . self::priceshape_add_table_prefix( $table_name ) . "'" );

		return boolval( $result );
	}

	/**
	 * Adds prefix to the table
	 *
	 * @param $table_name - name of the table
	 *
	 * @return mixed
	 */
	private static function priceshape_add_table_prefix( $table_name ) {
		global $wpdb;

		return esc_sql( $wpdb->prefix . $table_name );
	}

	/**
	 * Adds foreign key prefix to the table
	 *
	 * @param $table_name - name of the table
	 *
	 * @return mixed
	 */
	private static function priceshape_add_foreign_key_prefix( $table_name ) {
		return esc_sql( self::FOREIGN_KEY_PREFIX . $table_name );
	}

	/**
	 * Adds unique index prefix to the table
	 *
	 * @param $columns - column names
	 *
	 * @return mixed
	 */
	private static function priceshape_add_unique_index_prefix( $columns ) {
		if ( is_array( $columns ) ) {
			$columns = implode( '_', $columns );
		}

		return esc_sql( self::UNIQUE_INDEX_PREFIX . $columns );
	}

	/**
	 * Returns query params
	 *
	 * @return array
	 */
	public static function priceshape_get_query_params() {
		return [
			'search' => ( ( ! empty( $_REQUEST['s'] ) ) ? sanitize_text_field( $_REQUEST['s'] ) : null ),
			'filter' => [
				'filter_prs'   => ( ( ! empty( $_REQUEST['filter_prs'] ) ) ? sanitize_text_field( $_REQUEST['filter_prs'] ) : null ),
				'filter_stock' => ( ( ! empty( $_REQUEST['filter_stock'] ) ) ? sanitize_text_field( $_REQUEST['filter_stock'] ) : null ),
			],
		];
	}

	/**
	 * Changes plugin status
	 *
	 * @param $status - status of plugin we want to apply
	 */
	public static function priceshape_change_plugins_status_to( $status ) {
		Priceshape_Plugin::priceshape_send_plugin_status_to_support_mail( $status );
		self::priceshape_insert_plugin_params( [ Priceshape_Plugin::PLUGIN_STATUS => $status ] );
	}

	/**
	 * Runs the query
	 *
	 * @param $sql - sql-request we want to run
	 *
	 * @return bool
	 */
	private static function priceshape_run_query( $sql ) {
		global $wpdb;

		try {
			$processing_status = $wpdb->query( $sql );
			if ( false === $processing_status ) {
				throw new Exception( 'An error during a query ' . $sql . ', in ' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
			}
		} catch ( Throwable $e ) {
			self::priceshape_prs_write_down_log( $e->getMessage() );

			return false;
		}

		return true;
	}

	/**
	 * Runs transactions query
	 *
	 * @param $queries - sql-request we want to run
	 */
	private static function priceshape_run_transaction( $queries ) {
		global $wpdb;

		try {
			$wpdb->query( 'START TRANSACTION' );
			foreach ( $queries as $sql ) {
				$processing_status = $wpdb->query( $sql );
				if ( false === $processing_status ) {
					throw new Exception( 'An error during a query ' . $sql . ', in ' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
				}
			}
			$wpdb->query( 'COMMIT' );
		} catch ( Throwable $e ) {
			$wpdb->query( 'ROLLBACK' );
			self::priceshape_prs_write_down_log( $e->getMessage() );
		}
	}

	/**
	 * Returns result of sql-request
	 *
	 * @param $sql - sql-request
	 * @param $output - data output type
	 *
	 * @return mixed
	 */
	private static function priceshape_get_sql_results( $sql, $output = ARRAY_A ) {
		global $wpdb;

		$results = $wpdb->get_results( $sql, $output );
		try {
			if ( is_null( $results ) ) {
				throw new Exception( 'Query is an empty or output_type is not correct. ' . $sql . ', in ' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
			}
		} catch ( Throwable $e ) {
			self::priceshape_prs_write_down_log( $e->getMessage() );
		}

		return $results;
	}

	/**
	 * Saves logs to database
	 *
	 * @param $message - log message
	 */
	public static function priceshape_prs_write_down_log( $message ) {
		global $wpdb;

		$actualLink = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$message    = $actualLink . ' => ' . $message;

		$sql = "INSERT INTO " . self::priceshape_add_table_prefix( self::TABLE_LOGS ) . " (message, created_at) VALUES('" . esc_sql( $message ) . "', CURRENT_TIMESTAMP);";
		$wpdb->query( $sql );
	}

	/**
	 * Returns all logs from database
	 *
	 * @return mixed
	 */
	public static function priceshape_get_all_logs() {
		$sql = "SELECT * FROM " . self::priceshape_add_table_prefix( self::TABLE_LOGS );

		return self::priceshape_get_sql_results( $sql );
	}

	/**
	 * Deletes all logs from database
	 *
	 */
	public static function priceshape_drop_logs() {
		$sql = "DELETE FROM " . self::priceshape_add_table_prefix( self::TABLE_LOGS );
		self::priceshape_run_query( $sql );
	}

	/**
	 * Returns a specific part of sql request
	 *
	 * @param null $part - name of the part
	 * @param null $alias - added alias name for sql part
	 *
	 * @return mixed|string
	 */
	private static function priceshape_get_sql_query_select_part( $part = null, $alias = null ) {
		if ( empty( $part ) ) {
			return "";
		}

		if ( empty( $alias ) ) {
			$alias = $part;
		}

		$posts_table     = self::priceshape_add_table_prefix( "posts" );
		$post_meta_table = self::priceshape_add_table_prefix( "postmeta" );
		$products_table  = self::priceshape_add_table_prefix( self::TABLE_PRODUCTS );

		$attributes_concat      = "CONCAT('{', GROUP_CONCAT( CONCAT( '\"', TRIM(LEADING 'attribute_pa_' FROM meta_key), '\":\"', meta_value, '\"')),'}')";
		$product_data_concat    = "CONCAT('{',GROUP_CONCAT( CONCAT( '\"', TRIM(LEADING '_' FROM meta_key),  '\":\"', meta_value, '\"') ),'}')";
		$product_data_meta_keys = "'" . implode( "','", [
				'_price',
				'_regular_price',
				'_sale_price',
				'_sku',
				'_variation_description',
				'_stock_status',
				'_wc_cog_cost',
			] ) . "'";

		$sql_query_parts = [
			"image"         => "(SELECT guid FROM $posts_table AS img WHERE img.post_parent = posts.id AND img.post_mime_type LIKE '%image/%' LIMIT 1)",
			"sku"           => "(SELECT meta_value FROM $post_meta_table WHERE meta_key = '_sku' AND posts.id = post_id LIMIT 1)",
			"price"         => "(SELECT meta_value FROM $post_meta_table WHERE meta_key = '_price' AND posts.id = post_id LIMIT 1) +0",
			"stock"         => "(SELECT meta_value FROM $post_meta_table WHERE meta_key = '_stock_status' AND posts.id = post_id LIMIT 1)",
			"cost_of_goods" => "(SELECT meta_value FROM $post_meta_table WHERE meta_key = '_wc_cog_cost' AND posts.id = post_id LIMIT 1)",
			"attributes"    => "(SELECT $attributes_concat FROM $post_meta_table WHERE meta_key LIKE \"%attribute_pa_%\" AND posts.id = post_id)",
			"newPrice"      => "(SELECT new_price FROM $products_table WHERE product_id = posts.id)",
			"changedOn"     => "(SELECT changed_on FROM $products_table WHERE product_id = posts.id)",
			"prsOnOff"      => "(SELECT product_id FROM $products_table WHERE product_id = posts.id)",
			"productsData"  => "(SELECT $product_data_concat FROM $post_meta_table WHERE meta_key IN ($product_data_meta_keys) AND post_id = posts.id)",
		];

		$result = $sql_query_parts[ $part ];

		return $alias ? "$result AS $alias" : $result;
	}
}
