<?php
/**
 * Plugin Name:       Pillow Mockup Generator
 * Plugin URI:        https://example.com/pillow-mockup-generator
 * Description:        Mixtiles-style widget that turns a customer photo into a custom-shaped pillow mockup using OpenRouter, captures leads, and saves the original / mockup / print-ready cut-out images.
 * Version:           2.3.4
 * Author:            Aviad
 * License:           GPL-2.0-or-later
 * Text Domain:       pillow-mockup-generator
 * Domain Path:       /languages
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

define( 'PMG_VERSION', '2.3.4' );
define( 'PMG_PLUGIN_FILE', __FILE__ );
define( 'PMG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PMG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PMG_OPTION_KEY', 'pmg_settings' );
define( 'PMG_REST_NAMESPACE', 'pmg/v1' );

require_once PMG_PLUGIN_DIR . 'includes/class-pmg-settings.php';
require_once PMG_PLUGIN_DIR . 'includes/class-pmg-activator.php';
require_once PMG_PLUGIN_DIR . 'includes/class-pmg-storage.php';
require_once PMG_PLUGIN_DIR . 'includes/class-pmg-leads.php';
require_once PMG_PLUGIN_DIR . 'includes/class-pmg-openrouter.php';
require_once PMG_PLUGIN_DIR . 'includes/class-pmg-generator.php';
require_once PMG_PLUGIN_DIR . 'includes/class-pmg-emailer.php';
require_once PMG_PLUGIN_DIR . 'includes/class-pmg-rest.php';
require_once PMG_PLUGIN_DIR . 'includes/class-pmg-shortcode.php';
require_once PMG_PLUGIN_DIR . 'includes/class-pmg-admin.php';
require_once PMG_PLUGIN_DIR . 'includes/class-pmg-plugin.php';

register_activation_hook( __FILE__, array( 'PMG_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PMG_Activator', 'deactivate' ) );

/**
 * Boot the plugin once all plugins are loaded.
 *
 * @return void
 */
function pmg_boot() {
	PMG_Plugin::instance();
}
add_action( 'plugins_loaded', 'pmg_boot' );
