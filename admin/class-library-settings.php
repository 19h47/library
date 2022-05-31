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
 * Class Library_Settings
 */
class Library_Settings {

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
	 * Register settings.
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
