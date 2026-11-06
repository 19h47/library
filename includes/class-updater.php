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

namespace Library;

use WP_Error;
use WP_Upgrader;

/**
 * Offers the latest GitHub release as a plugin update, its package the library.zip the release workflow attaches to it:
 * the plugin with its dependencies, which the archive of the repository has not.
 *
 * WordPress calls update_plugins_{hostname} for plugins whose Update URI header
 * points to that host, then compares the returned version with the installed one.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 *
 * @phpstan-type Release array{version:string,package:string,url:string,notes:string,date:string,readme:string}
 */
class Updater {

	/**
	 * Site transient holding the latest release.
	 *
	 * @var string
	 */
	private const string CACHE_KEY = 'library_github_release';

	/**
	 * Name of the package of a release, which its workflow builds and attaches to it.
	 *
	 * @var string
	 */
	private const string PACKAGE = 'library.zip';

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file Plugin basename, e.g. library/library.php.
	 */
	public function __construct( private readonly string $plugin_file ) {}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'update_plugins_github.com', array( $this, 'check_update' ), 10, 3 );
		add_filter( 'plugins_api', array( $this, 'get_plugin_information' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'rename_source' ), 10, 4 );
	}

	/**
	 * Return the latest release as update data.
	 *
	 * @param array<string,mixed>|false $update      Update data, false when no other filter answered.
	 * @param array<string,string>      $plugin_data Plugin headers.
	 * @param string                    $plugin_file Plugin basename.
	 * @return array<string,mixed>|false
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
			'slug'    => $this->get_slug(),
			'version' => $release['version'],
			'url'     => $release['url'],
			'package' => $release['package'],
			'tested'  => $this->get_tested_up_to( $release['readme'] ),
			'icons'   => array( 'svg' => $this->get_asset_url( 'icon.svg' ) ),
		);
	}

	/**
	 * Fill the "View details" modal with the readme of the release, as on WordPress.org, or else its notes.
	 *
	 * @param false|object|array<string,mixed> $result Plugin information, false by default.
	 * @param string                           $action API action.
	 * @param object                           $args   Request arguments.
	 * @return false|object|array<string,mixed>
	 */
	public function get_plugin_information( $result, string $action, object $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || $this->get_slug() !== $args->slug ) {
			return $result;
		}

		$release = $this->get_release();

		if ( null === $release ) {
			return $result;
		}

		$plugin_data = get_plugin_data( LIBRARY_FILE, false, false );
		$readme      = $release['readme'];
		$sections    = Readme::get_sections( $readme );

		return (object) array(
			'name'          => $plugin_data['Name'],
			'slug'          => $this->get_slug(),
			'version'       => $release['version'],
			'author'        => $plugin_data['Author'],
			'homepage'      => $plugin_data['PluginURI'],
			'requires'      => $plugin_data['RequiresWP'],
			'tested'        => $this->get_tested_up_to( $readme ),
			'requires_php'  => $plugin_data['RequiresPHP'],
			'last_updated'  => $release['date'],
			'download_link' => $release['package'],
			'banners'       => array( 'high' => $this->get_asset_url( 'banner.svg' ) ),
			'sections'      => $sections ? $sections : array( 'changelog' => wpautop( esc_html( $release['notes'] ) ) ),
		);
	}

	/**
	 * WordPress version the release is tested with.
	 *
	 * As on WordPress.org, "Tested up to: 7.1" covers every 7.1.x release: WordPress compares
	 * the full versions, so a 7.1.3 site would otherwise warn that the plugin is not tested with it.
	 *
	 * @param string $readme Readme of the release.
	 * @return string
	 */
	private function get_tested_up_to( string $readme ): string {
		$tested     = Readme::get_header( $readme, 'Tested up to' );
		$wp_version = get_bloginfo( 'version' );

		return '' !== $tested && str_starts_with( $wp_version . '.', $tested . '.' ) ? $wp_version : $tested;
	}

	/**
	 * Rename the extracted folder of the package to the folder of the plugin, whatever it is named on the site.
	 *
	 * @param string|WP_Error     $source        Path of the extracted folder.
	 * @param string              $remote_source Parent folder of the extraction.
	 * @param WP_Upgrader         $upgrader      Upgrader instance.
	 * @param array<string,mixed> $hook_extra    Extra arguments, with the plugin being updated.
	 * @return string|WP_Error
	 */
	public function rename_source( $source, string $remote_source, $upgrader, array $hook_extra ) {
		unset( $upgrader );

		if ( is_wp_error( $source ) || ! isset( $hook_extra['plugin'] ) || $this->plugin_file !== $hook_extra['plugin'] ) {
			return $source;
		}

		global $wp_filesystem;

		$destination = trailingslashit( $remote_source ) . $this->get_slug() . '/';

		if ( trailingslashit( $source ) === $destination ) {
			return $source;
		}

		if ( ! $wp_filesystem->move( $source, $destination ) ) {
			return new WP_Error( 'library_rename_failed', __( 'Could not rename the update folder.', 'library' ) );
		}

		return $destination;
	}

	/**
	 * Latest GitHub release with its package and its readme, cached to stay under the API rate limit; none until its
	 * package is attached, a minute or so after it is published.
	 *
	 * @return Release|null
	 */
	private function get_release(): ?array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- "Check again" button on the updates screen.
		$force_check = isset( $_GET['force-check'] );
		/**
		 * The release cached, an empty string for a failed request, false for none.
		 *
		 * @var Release|''|false $cached
		 */
		$cached = $force_check ? false : get_site_transient( self::CACHE_KEY );

		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : null;
		}

		$url      = 'https://api.github.com/repos/' . $this->get_repository() . '/releases/latest';
		$response = wp_remote_get( $url, array( 'headers' => array( 'Accept' => 'application/vnd.github+json' ) ) );
		$body     = json_decode( wp_remote_retrieve_body( $response ), true );
		$package  = is_array( $body ) ? array_find( $body['assets'] ?? array(), fn( array $asset ) => self::PACKAGE === $asset['name'] ) : null;
		$release  = null;

		if ( 200 === wp_remote_retrieve_response_code( $response ) && isset( $body['tag_name'], $package['browser_download_url'] ) ) {
			$release = array(
				'version' => ltrim( $body['tag_name'], 'v' ),
				'package' => $package['browser_download_url'],
				'url'     => $body['html_url'],
				'notes'   => (string) $body['body'],
				'date'    => $body['published_at'],
				'readme'  => $this->get_readme( $body['tag_name'] ),
			);
		}

		// A failed request is cached too, for a shorter time, as an empty string.
		$cached     = $release ?? '';
		$expiration = $release ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS;

		set_site_transient( self::CACHE_KEY, $cached, $expiration );

		return $release;
	}

	/**
	 * Readme of a release, empty when it cannot be read.
	 *
	 * @param string $tag Release tag, e.g. "v2.3.2".
	 * @return string
	 */
	private function get_readme( string $tag ): string {
		$response = wp_remote_get( 'https://raw.githubusercontent.com/' . $this->get_repository() . '/' . rawurlencode( $tag ) . '/README.txt' );

		return 200 === wp_remote_retrieve_response_code( $response ) ? wp_remote_retrieve_body( $response ) : '';
	}

	/**
	 * URL of an image of the assets folder, as shown by the updates screen and the plugin details.
	 *
	 * @param string $file File name, e.g. "icon.svg".
	 * @return string
	 */
	private function get_asset_url( string $file ): string {
		return Plugin::get_asset( "assets/$file" )[0];
	}

	/**
	 * GitHub repository (owner/name), read from the Update URI header.
	 *
	 * @return string
	 */
	private function get_repository(): string {
		$update_uri = get_file_data( LIBRARY_FILE, array( 'update_uri' => 'Update URI' ) )['update_uri'];

		return trim( (string) wp_parse_url( $update_uri, PHP_URL_PATH ), '/' );
	}

	/**
	 * Plugin slug, i.e. its folder name.
	 *
	 * @return string
	 */
	private function get_slug(): string {
		return dirname( $this->plugin_file );
	}
}
