<?php
/**
 * Plugin updates from GitHub releases.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Offers the latest GitHub release as a plugin update.
 *
 * WordPress calls update_plugins_{hostname} for plugins whose Update URI header
 * points to that host, then compares the returned version with the installed one.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Updater {

	/**
	 * Site transient holding the latest release.
	 *
	 * @var string
	 */
	private const CACHE_KEY = 'library_github_release';

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file Plugin basename, e.g. library/library.php.
	 */
	public function __construct( private readonly string $plugin_file ) {}

	/**
	 * Return the latest release as update data.
	 *
	 * @param array|false $update      Update data, false when no other filter answered.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin basename.
	 * @return array|false
	 */
	public function check_update( $update, array $plugin_data, string $plugin_file ) {
		unset( $plugin_data );

		if ( $this->plugin_file !== $plugin_file ) {
			return $update;
		}

		// Updating replaces the plugin folder: never offer it over a Git working copy.
		if ( is_dir( LIBRARY_DIR_PATH . '.git' ) ) {
			return $update;
		}

		$release = $this->get_release();

		if ( null === $release ) {
			return $update;
		}

		return array(
			'slug'    => $this->slug(),
			'version' => $release['version'],
			'url'     => $release['url'],
			'package' => $release['package'],
		);
	}

	/**
	 * Fill the "View details" modal with the release notes.
	 *
	 * @param false|object|array $result Plugin information, false by default.
	 * @param string             $action API action.
	 * @param object             $args   Request arguments.
	 * @return false|object|array
	 */
	public function plugin_information( $result, string $action, object $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || $this->slug() !== $args->slug ) {
			return $result;
		}

		$release = $this->get_release();

		if ( null === $release ) {
			return $result;
		}

		$plugin_data = get_plugin_data( LIBRARY_DIR_PATH . 'library.php', false, false );

		return (object) array(
			'name'          => $plugin_data['Name'],
			'slug'          => $this->slug(),
			'version'       => $release['version'],
			'author'        => $plugin_data['Author'],
			'homepage'      => $plugin_data['PluginURI'],
			'requires'      => $plugin_data['RequiresWP'],
			'requires_php'  => $plugin_data['RequiresPHP'],
			'last_updated'  => $release['date'],
			'download_link' => $release['package'],
			'sections'      => array(
				'changelog' => wpautop( esc_html( $release['notes'] ) ),
			),
		);
	}

	/**
	 * Rename the extracted archive folder (owner-repo-sha) to the plugin slug.
	 *
	 * @param string|WP_Error $source        Path of the extracted folder.
	 * @param string          $remote_source Parent folder of the extraction.
	 * @param WP_Upgrader     $upgrader      Upgrader instance.
	 * @param array           $hook_extra    Extra arguments, with the plugin being updated.
	 * @return string|WP_Error
	 */
	public function rename_source( $source, string $remote_source, $upgrader, array $hook_extra ) {
		unset( $upgrader );

		if ( is_wp_error( $source ) || ! isset( $hook_extra['plugin'] ) || $this->plugin_file !== $hook_extra['plugin'] ) {
			return $source;
		}

		global $wp_filesystem;

		$destination = trailingslashit( $remote_source ) . $this->slug() . '/';

		if ( trailingslashit( $source ) === $destination ) {
			return $source;
		}

		if ( ! $wp_filesystem->move( $source, $destination ) ) {
			return new WP_Error( 'library_rename_failed', __( 'Could not rename the update folder.', 'library' ) );
		}

		return $destination;
	}

	/**
	 * Latest GitHub release, cached to stay under the API rate limit.
	 *
	 * @return array{version:string,package:string,url:string,notes:string,date:string}|null
	 */
	private function get_release(): ?array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- "Check again" button on the updates screen.
		$force_check = isset( $_GET['force-check'] );
		$cached      = $force_check ? false : get_site_transient( self::CACHE_KEY );

		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : null;
		}

		$url      = 'https://api.github.com/repos/' . $this->repository() . '/releases/latest';
		$response = wp_remote_get( $url, array( 'headers' => array( 'Accept' => 'application/vnd.github+json' ) ) );
		$body     = json_decode( wp_remote_retrieve_body( $response ), true );
		$release  = null;

		if ( 200 === wp_remote_retrieve_response_code( $response ) && isset( $body['tag_name'], $body['zipball_url'] ) ) {
			$release = array(
				'version' => ltrim( $body['tag_name'], 'v' ),
				'package' => $body['zipball_url'],
				'url'     => $body['html_url'],
				'notes'   => (string) $body['body'],
				'date'    => $body['published_at'],
			);
		}

		// A failed request is cached too, for a shorter time, as an empty string.
		$cached     = $release ?? '';
		$expiration = $release ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS;

		set_site_transient( self::CACHE_KEY, $cached, $expiration );

		return $release;
	}

	/**
	 * GitHub repository (owner/name), read from the Update URI header.
	 *
	 * @return string
	 */
	private function repository(): string {
		$plugin_data = get_plugin_data( LIBRARY_DIR_PATH . 'library.php', false, false );

		return trim( (string) wp_parse_url( $plugin_data['UpdateURI'], PHP_URL_PATH ), '/' );
	}

	/**
	 * Plugin slug, i.e. its folder name.
	 *
	 * @return string
	 */
	private function slug(): string {
		return dirname( $this->plugin_file );
	}
}
