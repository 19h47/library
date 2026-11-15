<?php
/**
 * What deleting the plugin leaves out: its caches, never the books.
 *
 * @link       https://github.com/19h47/library
 * @since      9.47.0
 *
 * @package    Library
 */

// If this file is not called by WordPress deleting the plugin, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// The caches of the books, of the BnF and of the latest release: see Cache, BnF and Updater.
delete_option( 'library_cache_generation' );
delete_site_transient( 'library_github_release' );

// The names of the transients of the cache hold a hash, which nothing keeps: they are found by their prefix.
$library_transients = $wpdb->prepare(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
	$wpdb->esc_like( '_transient_library_' ) . '%',
	$wpdb->esc_like( '_transient_timeout_library_' ) . '%'
);

$wpdb->query( $library_transients ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Prepared above, once, on uninstall.
