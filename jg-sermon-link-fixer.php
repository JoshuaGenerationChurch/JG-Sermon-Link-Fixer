<?php
/**
 * Plugin Name:       JG Sermon Link Fixer
 * Plugin URI:        https://yourwebsite.com/plugins/jg-sermon-link-fixer
 * Description:       Custom plugin to update audio file links from JetFormBuilder submissions, remove 'Protected:' prefix from titles, and enable shortcode rendering in JetFormBuilder forms.
 * Version:           1.0.0
 * Author:            Ruan Pienaar
 * Author URI:        https://joshgen.org
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       jg-sermon-link-fixer
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants.
 */
define( 'JG_SERMON_LINK_FIXER_VERSION', '1.0.1' );
define( 'JG_SERMON_LINK_FIXER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JG_SERMON_LINK_FIXER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin textdomain for translations.
 */
function jg_sermon_link_fixer_load_textdomain() {
	load_plugin_textdomain( 'jg-sermon-link-fixer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'jg_sermon_link_fixer_load_textdomain' );

/**
 * Include required files.
 */
require_once JG_SERMON_LINK_FIXER_PLUGIN_DIR . 'includes/class-jg-sermon-link-fixer.php';

/**
 * Initialize the plugin.
 */
function jg_sermon_link_fixer_init() {
	if ( class_exists( 'JG_Sermon_Link_Fixer' ) ) {
		new JG_Sermon_Link_Fixer();
	}
}
add_action( 'plugins_loaded', 'jg_sermon_link_fixer_init' );