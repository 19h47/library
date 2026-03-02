<?php
/**
 * Query modifications: search in post meta.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Extends main query for book search (post meta).
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_WP_Query {

	/**
	 * Plugin identifier.
	 *
	 * @since  1.0.0
	 * @var    string $plugin_name
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since  1.0.0
	 * @var    string $version
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name Plugin identifier.
	 * @param string $version     Plugin version.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Join postmeta for search.
	 *
	 * @since  1.0.0
	 * @param  string   $join  JOIN clause.
	 * @param  WP_Query $query Query instance.
	 * @return string
	 */
	public function search_join( string $join, WP_Query $query ) : string {
		global $wpdb;

		if ( is_search() ) {
			$join .= ' LEFT JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
		}

		return $join;
	}


	/**
	 * Extend WHERE clause for post meta search.
	 *
	 * @since  1.0.0
	 * @param  string   $where WHERE clause.
	 * @param  WP_Query $query Query instance.
	 * @return string
	 */
	public function search_where( string $where, WP_Query $query ) : string {
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
	 * Add DISTINCT for search to avoid duplicates.
	 *
	 * @since  1.0.0
	 * @param  string   $distinct DISTINCT clause.
	 * @param  WP_Query $query    Query instance.
	 * @return string
	 */
	public function search_distinct( string $distinct, WP_Query $query ) : string {
		if ( is_search() ) {
			return 'DISTINCT';
		}

		return $distinct;
	}
}
