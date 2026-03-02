<?php
/**
 * Plugin bootstrap and text domain loading.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 *
 * @wordpress-plugin
 * Plugin Name:       Library
 * Plugin URI:        https://www.github.com/19h47/library
 * Description:       Enables a Book post type, taxonomy and metaboxes.
 * Version:           0.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Jérémy Levron
 * Author URI:        https://www.19h47.fr
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/19h47/library
 * Text Domain:       library
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

define( 'LIBRARY_DIR_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Load plugin text domain for translations.
 * Uses absolute path so translations load even when plugin path is non-standard.
 * Must run at init or later (WordPress 6.7+).
 */
add_action(
	'init',
	function () {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$mofile = LIBRARY_DIR_PATH . 'languages/library-' . $locale . '.mo';
		if ( is_readable( $mofile ) ) {
			load_textdomain( 'library', $mofile, $locale );
			return;
		}
		load_plugin_textdomain(
			'library',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	},
	0
);

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-library.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_library(): void {
	$plugin_data = get_plugin_data( LIBRARY_DIR_PATH . 'library.php', false, false );
	$plugin      = new Library(
		$plugin_data['TextDomain'],
		$plugin_data['Version']
	);
	$plugin->run();
}

add_action( 'plugins_loaded', 'run_library' );
