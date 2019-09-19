<?php
/**
 * Plugin Name:     Lanco Tiendas
 * Plugin URI:      https://www.lancostore.com/
 * Description:     Connects new orders with the correct store based on location
 * Author:          Cristian Araya
 * Author URI:      http://teahdigital.com
 * Text Domain:     lanco-store
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Lanco_Tiendas
 */
require_once __DIR__ . '/inc/Tiendas.php';

define('LANCORE_PATH', __DIR__);
define('LANCORE_VERSION', '0.1.0');

function ltiendas_get_plugin_object() {
	return Lanco_Tiendas::get_instance();
}

add_action( 'plugins_loaded', [ ltiendas_get_plugin_object(), 'hooks' ] );
