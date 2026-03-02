<?php
/**
 * Plugin settings registration.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Registers plugin options (e.g. reading percentage).
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Settings {

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
	 * Register settings with WordPress.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'options',
			'reading_percentage',
			array(
				'type'         => 'string',
				'show_in_rest' => true,
			)
		);
	}
}
