<?php
/**
 *
 * ███████╗██████╗ ██╗███████╗███████╗███████╗██╗  ██╗ █████╗ ███████╗███████╗
 * ██╔══██║██╔══██╗██║██╔════╝██╔════╝██╔════╝██║  ██║██╔══██╗██╔══██║██╔════╝
 * ███████║██████╔╝██║██║     █████╗  ███████╗███████║███████║███████║█████╗
 * ██╔════╝██╔══██╗██║██║     ██╔══╝  ╚════██║██╔══██║██╔══██║██╔════╝██╔══╝
 * ██║     ██║  ██║██║███████╗███████╗███████║██║  ██║██║  ██║██║     ███████╗
 * ╚═╝     ╚═╝  ╚═╝╚═╝╚══════╝╚══════╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝     ╚══════╝
 *
 */

namespace Priceshape;

use Exception;
use Priceshape\Core\Priceshape_Error_Handler;
use Priceshape\Core\Priceshape_Helper;
use Throwable;

/**
 * Class Priceshape_Plugin
 * @package Priceshape
 */
class Priceshape_Plugin {
	const NAME = 'name';
	const TEL = 'tel';
	const EMAIL = 'email';
	const COUNTRY = 'country';
	const AGREE = 'agree';
	const PLUGIN_TOKEN = 'plugin_token';
	const PRODUCTS_LIMIT = 'products_limit';
	const WOO_INSTALLED = 'woo_installed';
	const PLUGIN_STATUS = 'plugin_status';
	const SHOW_OLD_VALUE = 'show_old_value';
	const AUTO_UPDATE_PRICE = 'auto_update_price';
	const UPDATE_ONLY_SALE_PRICE = 'update_only_sale_price';
	const SUPPORT_MAIL = 'support_mail';
	const PRS_ROUTE = 'prs_route';
	const PLUGIN_STATUS_UNSUCCESSFUL = 'Unsuccessful';
	const YES = 'YES';
	const NO = 'NO';

	const DEFAULT_PLUGIN_PARAMS = [
		self::NAME                   => null,
		self::TEL                    => null,
		self::EMAIL                  => null,
		self::COUNTRY                => null,
		self::AGREE                  => null,
		self::PLUGIN_TOKEN           => null,
		self::PRODUCTS_LIMIT         => 0,
		self::WOO_INSTALLED          => null,
		self::PLUGIN_STATUS          => null,
		self::SHOW_OLD_VALUE         => self::YES,
		self::AUTO_UPDATE_PRICE      => self::NO,
		self::UPDATE_ONLY_SALE_PRICE => self::NO,
		self::SUPPORT_MAIL           => PRICESHAPE_SUPPORT_MAIL,
		self::PRS_ROUTE              => PRICESHAPE_ADDR . '/api/connections/remote/store',
	];

	private $plugin_params;
	private $user_agree;
	private $plugin_has_token;
	private $woo_installed;

	/**
	 * Priceshape_Plugin constructor.
	 */
	function __construct() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( Priceshape_Queries::priceshape_table_exist( Priceshape_Queries::TABLE_PLUGIN_INFO ) ) {
			$this->user_agree       = boolval( Priceshape_Queries::priceshape_get_plugin_param( self::AGREE ) );
			$this->plugin_has_token = boolval( Priceshape_Queries::priceshape_get_plugin_param( self::PLUGIN_TOKEN ) );
			$this->woo_installed    = boolval( Priceshape_Queries::priceshape_get_plugin_param( self::WOO_INSTALLED ) );
			if ( ! $this->woo_installed ) {
				add_action( 'init', [ $this, 'priceshape_check_some_other_plugin' ] );
			}

			$this->priceshape_set_default_plugin_params();
		}
		if ( Priceshape_Queries::priceshape_table_exist( Priceshape_Queries::TABLE_LOGS ) ) {
			new Priceshape_Error_Handler();
		}
		register_activation_hook( PRICESHAPE_PLUGIN_INDEX_FILE, [ $this, 'priceshape_prs_plugin_activate' ] );
		register_deactivation_hook( PRICESHAPE_PLUGIN_INDEX_FILE, [ $this, 'priceshape_prs_plugin_deactivate' ] );

		$this->priceshape_activate_actions();
	}

	/**
	 * Activation actions
	 *
	 */
	private function priceshape_activate_actions() {
		add_action( 'admin_menu', [ $this, 'priceshape_plugin_setup_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'priceshape_plugin_script_enqueue' ] );
		add_action( 'wp_ajax_prsLoginFormAjax', [ $this, 'priceshape_login_form_ajax_handler' ] );
		add_action( 'wp_ajax_prsItemTurnAjax', [ $this, 'priceshape_prs_item_turn_on_off_handler' ] );
		add_action( 'wp_ajax_prsItemApproveAjax', [ $this, 'priceshape_prs_item_approve_handler' ] );
		add_action( 'wp_ajax_prsTryAgainAjax', [ $this, 'priceshape_prs_try_again_ajax' ] );
		add_action( 'wp_ajax_prsHideOldPrice', [ $this, 'priceshape_prs_hide_old_price_ajax' ] );
		add_action( 'wp_ajax_prsAutoUpdatePrice', [ $this, 'priceshape_prs_auto_update_price_ajax' ] );
		add_action( 'wp_ajax_prsUpdateOnlySalePrice', [ $this, 'priceshape_prs_update_only_sale_price_ajax' ] );
		add_action( 'wp_ajax_prsSupportBtn', [ $this, 'priceshape_prs_error_report_ajax' ] );
		add_action( 'rest_api_init', [ $this, 'priceshape_rest_api_init' ] );
	}

	/**
	 * Checks if woocommerce is active and save this information to the database
	 *
	 */
	public function priceshape_check_some_other_plugin() {
		$plugin_name = 'woocommerce/woocommerce.php';
		$is_active   = is_plugin_active( $plugin_name );
		Priceshape_Queries::priceshape_insert_plugin_params( [ self::WOO_INSTALLED => $is_active ] );
		$this->woo_installed = boolval( $is_active );
	}

	/**
	 * Plugin activation function
	 *
	 */
	public function priceshape_prs_plugin_activate() {
		Priceshape_Queries::priceshape_create_table_migrate();
		Priceshape_Queries::priceshape_create_tables();
		Priceshape_Queries::priceshape_create_triggers();

		if ( ! Priceshape_Queries::priceshape_get_plugin_param( Priceshape_Plugin::PLUGIN_STATUS ) ) {
			Priceshape_Queries::priceshape_change_plugins_status_to( 'Not Authorized' );
		}
	}

	/**
	 * Plugin deactivation function
	 *
	 */
	public function priceshape_prs_plugin_deactivate() {
		if ( ! $this->user_agree ) {
			return;
		}
		$this->priceshape_send_plugin_info_to_prs( [ 'deactivated' => site_url() ] );
	}

	/**
	 * Sets plugin parameters
	 *
	 * @param array $params - an array of parameters
	 */
	private function priceshape_set_plugin_params( array $params ) {
		$this->plugin_params = $params;
	}

	/**
	 * Sets default plugin parameters
	 *
	 * @param array $params - an array of parameters
	 */
	private function priceshape_set_default_plugin_params() {
		$this->priceshape_set_plugin_params( self::DEFAULT_PLUGIN_PARAMS );
	}

	/**
	 * Returns plugin parameters
	 *
	 */
	private function priceshape_get_plugin_params() {
		return $this->plugin_params;
	}

	/**
	 * Returns plugin parameter
	 *
	 * @param $param_name - name of the parameter
	 *
	 * @return mixed
	 */
	public static function priceshape_get_param( $param_name ) {
		return Priceshape_Queries::priceshape_get_plugin_param( $param_name ) ? Priceshape_Queries::priceshape_get_plugin_param( $param_name ) : self::DEFAULT_PLUGIN_PARAMS[ $param_name ];
	}

	/**
	 * Setup menu during activation
	 *
	 */
	public function priceshape_plugin_setup_menu() {
		add_menu_page( 'Priceshape Main Page', 'Priceshape', 'manage_options', 'priceshape-plugin', [
			$this,
			'priceshape_main_page'
		] );
		if ( $this->plugin_has_token && $this->user_agree && $this->woo_installed ) {
			add_submenu_page( 'priceshape-plugin', 'All products', 'All products', 'manage_options', 'all-product-page', [
				$this,
				'priceshape_all_products_page'
			] );
		}
	}

	/**
	 * The function to be called to output the products for this page
	 *
	 */
	public function priceshape_all_products_page() {
		$this->priceshape_get_widgets();
		$this->priceshape_get_all_products_page();
	}

	/**
	 * The function to be called to output the content for this page
	 *
	 */
	public function priceshape_main_page() {
		if ( ! $this->woo_installed ) {
			require_once PRICESHAPE_PLUGIN_PATH . "/views/not-found.php";

			return;
		}
		$this->priceshape_get_widgets();
		if ( $this->plugin_has_token && $this->user_agree ) {
			$this->priceshape_get_approving_price_page();
		} else {
			$this->priceshape_get_authorization_page();
		}
	}

	/**
	 * Require all-products-page.php view
	 *
	 */
	function priceshape_get_all_products_page() {
		require_once PRICESHAPE_PLUGIN_PATH . "/views/all-products-page.php";
	}

	/**
	 * Require approving-price-page.php view
	 *
	 */
	function priceshape_get_approving_price_page() {
		require_once PRICESHAPE_PLUGIN_PATH . "/views/approving-price-page.php";
	}

	/**
	 * Require login-form.php view
	 *
	 */
	function priceshape_get_authorization_page() {
		require_once PRICESHAPE_PLUGIN_PATH . "/views/login-form.php";
	}

	/**
	 * Require views
	 *
	 */
	function priceshape_get_widgets() {
		$this->priceshape_get_support_button();
		$this->priceshape_get_plugin_status_row();
		$this->priceshape_get_plugin_limit_row();
	}

	/**
	 * Require plugin-status.php view
	 *
	 */
	function priceshape_get_plugin_status_row() {
		require_once PRICESHAPE_PLUGIN_PATH . "/views/plugin-status.php";
	}

	/**
	 * Require plugin-limit.php view
	 *
	 */
	function priceshape_get_plugin_limit_row() {
		require_once PRICESHAPE_PLUGIN_PATH . "/views/plugin-limit.php";
	}

	/**
	 * Require support-btn.php view
	 *
	 */
	function priceshape_get_support_button() {
		require_once PRICESHAPE_PLUGIN_PATH . "/views/support-btn.php";
	}

	/**
	 * Registers scripts and styles
	 *
	 */
	public function priceshape_plugin_script_enqueue() {
		wp_enqueue_script( 'ajax-script', plugins_url( PRICESHAPE_PLUGIN_DIR_NAME . '/js/script.js' ), [ 'jquery' ] );
		wp_localize_script( 'ajax-script', 'ajaxObject', [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ] );
		wp_enqueue_style( 'main-style', plugins_url( PRICESHAPE_PLUGIN_DIR_NAME . '/css/style.css' ) );
	}

	/**
	 * Removes plugin parameters during activation
	 *
	 */
	public function priceshape_prs_try_again_ajax() {
		Priceshape_Queries::priceshape_remove_plugin_param( self::PLUGIN_TOKEN );
		wp_die();
	}

	/**
	 * Sets SHOW_OLD_VALUE parameter during activation
	 *
	 */
	public function priceshape_prs_hide_old_price_ajax() {
		$hide = ( self::NO == $_POST['hide'] ) ? self::NO : self::YES;
		Priceshape_Queries::priceshape_insert_plugin_params( [ self::SHOW_OLD_VALUE => $hide ] );
		wp_die();
	}

	/**
	 * Sets AUTO_UPDATE_PRICE parameter during activation
	 *
	 */
	public function priceshape_prs_auto_update_price_ajax() {
		$auto_update = ( self::NO == $_POST['prsAutoUpdate'] ) ? self::NO : self::YES;
		Priceshape_Queries::priceshape_insert_plugin_params( [ self::AUTO_UPDATE_PRICE => $auto_update ] );
		wp_die();
	}

	/**
	 * Sets UPDATE_ONLY_SALE_PRICE parameter during activation
	 *
	 */
	public function priceshape_prs_update_only_sale_price_ajax() {
		$update_only_sale_price = ( self::NO == $_POST['prsUpdateOnlySalePrice'] ) ? self::NO : self::YES;
		Priceshape_Queries::priceshape_insert_plugin_params( [ self::UPDATE_ONLY_SALE_PRICE => $update_only_sale_price ] );
		wp_die();
	}

	/**
	 * Login form ajax handler
	 *
	 */
	public function priceshape_login_form_ajax_handler() {
		if ( ! empty( Priceshape_Queries::priceshape_get_plugin_param( self::PLUGIN_TOKEN ) ) ) {
			wp_die();
		}
		$this->priceshape_register_plugin();
		$this->priceshape_activate_plugin();
		wp_die();
	}

	/**
	 * Adding to / removing from priceshape product handler
	 *
	 */
	public function priceshape_prs_item_turn_on_off_handler() {
		$action     = ( ! empty( $_POST['prs-turn'] ) ) ? sanitize_text_field( $_POST['prs-turn'] ) : null;
		$product_id = ( ! empty( $_POST['prod'] ) ) ? sanitize_text_field( $_POST['prod'] ) : null;
		if ( ! is_null( $action ) ) {
			switch ( $action ) {
				case "prs-on":
					Priceshape_Queries::priceshape_add_to_prs( $product_id );
				break;
				case "prs-off":
					Priceshape_Queries::priceshape_remove_from_prs( $product_id );
				break;
			}
			echo $action;
		}
		wp_die();
	}

	/**
	 * Approves / declines prices for products handler
	 *
	 */
	public function priceshape_prs_item_approve_handler() {
		$data = array_merge( [
			'priceHandler' => null,
			'field_id'     => null,
		], Priceshape_Helper::priceshape_sanitize( $_POST ) );

		switch ( $data['priceHandler'] ) {
			case "approve":
				Priceshape_Queries::priceshape_approve_prices( $data['field_id'] );
			break;
			case "decline":
				Priceshape_Queries::priceshape_decline( $data['field_id'] );
			break;
		}
		echo $data['priceHandler'];
		wp_die();
	}

	/**
	 * Registers plugin
	 *
	 */
	private function priceshape_register_plugin() {
		$this->priceshape_filter_request_params();
		$this->priceshape_set_plugin_token();
		Priceshape_Queries::priceshape_insert_plugin_params( array_merge( self::DEFAULT_PLUGIN_PARAMS, $this->priceshape_get_plugin_params() ) );
	}

	/**
	 * Sets plugin token
	 *
	 */
	private function priceshape_set_plugin_token() {
		$plugin_params                       = $this->priceshape_get_plugin_params();
		$plugin_params[ self::PLUGIN_TOKEN ] = $this->priceshape_create_token( $plugin_params[ self::NAME ] . time() );
		$this->priceshape_set_plugin_params( $plugin_params );
	}

	/**
	 * Creates plugin token
	 *
	 * @param $key_phrase - a string to calculate the md5 hash of
	 *
	 * @return string
	 */
	private function priceshape_create_token( $key_phrase ) {
		return md5( $key_phrase );
	}

	/**
	 * Activates the plugin
	 *
	 */
	private function priceshape_activate_plugin() {
		if ( ! $this->user_agree ) {
			return;
		}
		$plugin_info  = $this->priceshape_get_plugin_info_for_sending();
		$prs_response = $this->priceshape_send_plugin_info_to_prs( $plugin_info );
		$this->priceshape_handle_activation_response( $prs_response );
	}

	/**
	 * Returns plugin information for subsequent activation
	 *
	 * @return array
	 */
	private function priceshape_get_plugin_info_for_sending() {
		$plugin_params = $this->priceshape_get_plugin_params();
		$actual_link   = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]";

		return [
			'link' => $actual_link,
			'user' => Priceshape_Helper::priceshape_sanitize( $_POST['user'] ),
			'hash' => $plugin_params[ self::PLUGIN_TOKEN ],
		];
	}

	/**
	 * Handle activation response
	 *
	 * @param $prs_response - priceshape response
	 */
	private function priceshape_handle_activation_response( $prs_response ) {
		Priceshape_Queries::priceshape_prs_write_down_log( " PriceShape response => " . json_encode( $prs_response ) );
		if ( isset( $prs_response->state ) ) {
			$plugin_status = $prs_response->message;
		} else {
			$plugin_status = self::PLUGIN_STATUS_UNSUCCESSFUL;
			$warnings      = $prs_response->message;
			echo json_encode( $warnings );
		}

		Priceshape_Queries::priceshape_change_plugins_status_to( $plugin_status );
	}

	/**
	 * Returns api response
	 *
	 * @param $params - plugin information
	 *
	 * @return mixed
	 */
	private function priceshape_send_plugin_info_to_prs( $params ) {

		$route = self::priceshape_get_param( self::PRS_ROUTE );

		$api_response = wp_remote_post( $route, [
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => json_encode( $params ),
		] );

		return isset( $api_response['body'] ) ? json_decode( $api_response['body'] ) : [];
	}

	/**
	 * Filters and sets request parameters
	 *
	 */
	private function priceshape_filter_request_params() {
		$plugin_params = [];
		if ( ! isset( $_POST['user'] ) || ! is_array( $_POST['user'] ) ) {
			throw new Exception( 'An error in $_POST[user] must be array, ' . json_encode( Priceshape_Helper::priceshape_sanitize( $_POST['user'] ) ) . ' given, in ' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
		}
		foreach ( Priceshape_Helper::priceshape_sanitize( $_POST['user'] ) as $key => $value ) {
			if ( ! array_key_exists( $key, self::DEFAULT_PLUGIN_PARAMS ) ) {
				continue;
			}
			if ( self::AGREE == $key ) {
				$this->user_agree = true;
			}
			$plugin_params[ $key ] = $value;
		}
		$this->priceshape_set_plugin_params( $plugin_params );
	}

	/**
	 * Register routes
	 *
	 */
	public function priceshape_rest_api_init() {
		$API = new Priceshape_Api();
		register_rest_route( 'priceshape/v1', '/create', [
			'methods'  => 'POST',
			'callback' => [ $API, 'priceshape_create' ],
		] );

		register_rest_route( 'priceshape/v1', '/read', [
			'methods'  => 'GET',
			'callback' => [ $API, 'priceshape_read' ],
		] );

		register_rest_route( 'priceshape/v1', '/update', [
			'methods'  => 'POST',
			'callback' => [ $API, 'priceshape_update' ],
		] );

		register_rest_route( 'priceshape/v1', '/logs', [
			'methods'  => 'GET',
			'callback' => [ $API, 'priceshape_logs' ],
		] );
	}

	/**
	 * Error report
	 *
	 */
	public function priceshape_prs_error_report_ajax() {
		$site_name = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]";
		$string    = $site_name . ' sent its Log. Please see it here ' . $site_name . "/wp-json/priceshape/v1/logs?" . self::PLUGIN_TOKEN . "=" . Priceshape_Queries::priceshape_get_plugin_param( self::PLUGIN_TOKEN );
		$status    = self::priceshape_send_mail_to_support( $string );
		echo $status;
		wp_die();
	}

	/**
	 * A request to support
	 *
	 * @param null $params - plugin information
	 */
	public static function priceshape_duplicate_request_to_support_mail( $params = null ) {
		$params = is_array( $params ) ? json_encode( $params ) : (string) $params;
		$string = 'Send a connect query to PriceShape with params => ' . $params;
		self::priceshape_send_mail_to_support( $string );
	}

	/**
	 * Sending an email with plugin status to support
	 *
	 * @param $status - plugin status
	 */
	public static function priceshape_send_plugin_status_to_support_mail( $status ) {
		$string = 'Plugin Status has been changed to => ' . $status;
		self::priceshape_send_mail_to_support( $string );
	}

	/**
	 * Sending an email with products ids and action we apply to them to support
	 *
	 * @param $ids - ids of products we want to add or remove
	 * @param string $action - add or remove action
	 */
	public static function priceshape_send_products_ids_to_support_mail( $ids, $action = 'add' ) {
		$string = "Product ids have been {$action}ed => " . json_encode( $ids );
		self::priceshape_send_mail_to_support( $string );
	}

	/**
	 * Constructing a message for sending to support
	 *
	 * @param string $type - table type
	 * @param array $params - additional parameters
	 */
	public static function priceshape_send_report_to_support_mail( $type = '', $params = [] ) {
		$string = '';
		switch ( $type ) {
			case "newValues" :
				$string .= "Products have got new price from PRS => " . json_encode( $params );
			break;
			case "emptyValues" :
				$string .= "Products list seems to be empty";
			break;
			case "plugParams" :
				$string .= "Plugin params were added => " . json_encode( $params );
			break;
			case "triggers" :
				$string .= "Plugin has not been installed. Create Trigger issue => " . json_encode( $params );
			break;
			case "tables" :
				$string .= "Plugin has not been installed. Prepare Tables issue => " . json_encode( $params );
			break;
			default:
				$string .= "Plugin has unknown issue $type => " . json_encode( $params );
			break;
		}

		self::priceshape_send_mail_to_support( $string );
	}

	/**
	 * Sending an email to support
	 *
	 * @param $message - a string message
	 *
	 * @return bool
	 */
	public static function priceshape_send_mail_to_support( $message ) {
		$site_name = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]";
		$to        = self::priceshape_get_param( self::SUPPORT_MAIL );
		$message   = wordwrap( $message, 70 );
		$subject   = $site_name . " - Plugin Notification";
		$headers   = 'X-Mailer: PHP/' . phpversion();
		$status    = mail( $to, $subject, $message, $headers );
		try {
			if ( false === $status ) {
				throw new Exception( $message . '. Email was not sent. ' . __FILE__ . '#' . __LINE__ . ' ' . __METHOD__ );
			}
		} catch ( Throwable $e ) {
			Priceshape_Queries::priceshape_prs_write_down_log( $e->getMessage() );
		}

		return $status;
	}
}
