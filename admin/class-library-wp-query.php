<?php
/**
 * The settings of the plugin.
 *
 * @link       https://github.com/19h47/sellsy-clients/
 * @since      0.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Class Library_WP_Query
 */
class Library_WP_Query {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}


	/**
	 * Join posts and postmeta tables
	 *
	 * @see https://developer.wordpress.org/reference/hooks/posts_join/
	 */
	function search_join( string $join, WP_Query $query ) : string {
		global $wpdb;

		if ( is_search() ) {
			$join .= ' LEFT JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
		}

		return $join;
	}


	/**
	 * Modify the search query with posts_where
	 *
	 * @see https://developer.wordpress.org/reference/hooks/posts_where/
	 */
	function search_where( string $where, WP_Query $query ) : string {
		global $wpdb;

		if ( is_search() ) {
			$where = preg_replace(
				'/\(\s*' . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
				'(' . $wpdb->posts . '.post_title LIKE $1) OR (' . $wpdb->postmeta . '.meta_value LIKE $1)',
				$where
			);
		}

		return $where;
	}


	/**
	 * Prevent duplicates
	 *
	 * @see https://developer.wordpress.org/reference/hooks/posts_distinct/
	 */
	function search_distinct( string $distinct, WP_Query $query ) : string {
		if ( is_search() ) {
			return 'DISTINCT';
		}

		return $distinct;
	}
}
