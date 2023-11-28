<?php
/**
 * Library
 *
 * @link              https://www.github.com/19h47/library
 * @since             1.0.1
 * @package           Library
 *
 * @wordpress-plugin
 * Plugin Name: Library
 * Plugin URI: https://www.github.com/19h47/library
 * Description: Enables a Book post type, taxonomy and metaboxes.
 * Version: 0.0.0
 * Author: Jérémy Levron
 * Author URI: https://www.19h47.fr
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: library
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

define( 'LIBRARY_DIR_PATH', plugin_dir_path( __FILE__ ) );


/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-library.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_library() {
	$plugin = new Library(
		get_plugin_data( LIBRARY_DIR_PATH . 'library.php' )['TextDomain'],
		get_plugin_data( LIBRARY_DIR_PATH . 'library.php' )['Version']
	);
	$plugin->run();
}

run_library(); // Run, Forrest, run!
