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
 * Version:           8.0.0
 * Requires at least: 7.1
 * Requires PHP:      8.2
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

define( 'LIBRARY_FILE', __FILE__ );
define( 'LIBRARY_DIR_PATH', plugin_dir_path( __FILE__ ) );

add_action(
	'init',
	fn() => load_plugin_textdomain( 'library', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ),
	0
);

require LIBRARY_DIR_PATH . 'includes/class-library.php';

add_action( 'plugins_loaded', array( new Library(), 'run' ) );
