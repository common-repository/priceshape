<?php
/**
 * @package Priceshape
 * @version 1.2.1
 *
 * Plugin Name: Priceshape
 * Plugin URI: http://wordpress.org/plugins/priceshape/
 * Text Domain: priceshape
 * Domain Path: /languages
 * Description: It is a plugin/application for Woocommerce, which can be connected to the client's webshop.
 * Version: 1.2.1
 *
 * Copyright (C) 2020 Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗██████╗ ██╗███████╗███████╗███████╗██╗  ██╗ █████╗ ███████╗███████╗
 * ██╔══██║██╔══██╗██║██╔════╝██╔════╝██╔════╝██║  ██║██╔══██╗██╔══██║██╔════╝
 * ███████║██████╔╝██║██║     █████╗  ███████╗███████║███████║███████║█████╗
 * ██╔════╝██╔══██╗██║██║     ██╔══╝  ╚════██║██╔══██║██╔══██║██╔════╝██╔══╝
 * ██║     ██║  ██║██║███████╗███████╗███████║██║  ██║██║  ██║██║     ███████╗
 * ╚═╝     ╚═╝  ╚═╝╚═╝╚══════╝╚══════╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝     ╚══════╝
 *
 */


use Priceshape\Priceshape_Plugin;

# PLUGIN PATH
if ( ! defined( 'PRICESHAPE_PLUGIN_PATH' ) ) {
	define( 'PRICESHAPE_PLUGIN_PATH', __DIR__ );
}

# PLUGIN MAIN FILE
if ( ! defined( 'PRICESHAPE_PLUGIN_INDEX_FILE' ) ) {
	define( 'PRICESHAPE_PLUGIN_INDEX_FILE', __FILE__ );
}

# PLUGIN DIR NAME
if ( ! defined( 'PRICESHAPE_PLUGIN_DIR_NAME' ) ) {
	define( 'PRICESHAPE_PLUGIN_DIR_NAME', basename( dirname( __FILE__ ) ) );
}

# PRICESHAPE_ADDR
if ( ! defined( 'PRICESHAPE_ADDR' ) ) {
	define( 'PRICESHAPE_ADDR', 'https://app.priceshape.dk' );
}

# PRICESHAPE_SUPPORT_MAIL
if ( ! defined( 'PRICESHAPE_SUPPORT_MAIL' ) ) {
	define( 'PRICESHAPE_SUPPORT_MAIL', 'priceshape@gmail.com' );
}

/**
 * Autoload classes
 *
 */
spl_autoload_register( function ( $class ) {
	$dir  = PRICESHAPE_PLUGIN_PATH . DIRECTORY_SEPARATOR;
	$file = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
	$path = $dir . $file;

	if ( is_readable( $path ) ) {
		require_once $path;
	}
} );

# Create the main Priceshape_Plugin class.
$priceshape_plugin = new Priceshape_Plugin();

add_action( 'plugins_loaded', 'priceshape_true_load_plugin_textdomain' );

function priceshape_true_load_plugin_textdomain() {
	load_plugin_textdomain( 'priceshape', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
