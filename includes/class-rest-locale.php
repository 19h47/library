<?php
/**
 * Language of the REST API: the one the client asks for, in its Accept-Language header, among those of the plugin.
 *
 * @link       https://github.com/19h47/library
 * @since      9.37.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * Answers a REST request in the language its Accept-Language header prefers, among English and the languages the plugin
 * is translated into, as HTTP negotiates a language; core answers in the language of the site, unless a logged-in user
 * asks for theirs with `_locale=user`, as the reading app and the admin do, which keeps precedence. A request without the
 * header, or with none of these languages, keeps the language of the site, as does a request that writes: what it saves,
 * such as the name of a cover, “t. 25” in French, is in the language of the site, whoever sends it.
 *
 * @since      9.37.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Rest_Locale {

	/**
	 * Locale of the strings in the code, the one a client gets without translation.
	 *
	 * @var string
	 */
	private const string SOURCE_LOCALE = 'en_US';

	/**
	 * Locales the plugin answers in, once read from its translations.
	 *
	 * @var list<string>|null
	 */
	private static ?array $locales = null;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'determine_locale', array( $this, 'negotiate' ) );
		add_filter( 'rest_pre_serve_request', array( $this, 'send_language_headers' ) );
	}

	/**
	 * The locale of a REST request that reads, as its Accept-Language header prefers it, else the one WordPress determined.
	 *
	 * @param string $locale Locale WordPress determined.
	 * @return string
	 */
	public function negotiate( string $locale ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reads which language to answer in, as core does.
		$asked_locale = sanitize_key( wp_unslash( $_GET['_locale'] ?? '' ) );

		if ( 'user' === $asked_locale || ! self::is_rest_request() || ! self::is_read_request() ) {
			return $locale;
		}

		$accept_language = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '' ) );

		return self::get_preferred_locale( $accept_language, self::get_locales() ) ?? $locale;
	}

	/**
	 * Say the language of the response, and that it depends on the Accept-Language header, so that a cache keeps one
	 * response per language. Sent as core sends `Vary: Origin`, beside it: a header of the response would replace it.
	 *
	 * @param bool $served Whether the request has already been served.
	 * @return bool
	 */
	public function send_language_headers( bool $served ): bool {
		$language = str_replace( '_', '-', determine_locale() );

		header( 'Content-Language: ' . $language );
		header( 'Vary: Accept-Language', false );

		return $served;
	}

	/**
	 * The locale an Accept-Language header prefers among `$locales`: its languages from the highest quality, each matched
	 * by its locale, fr-CA by fr_CA, else by its language alone, fr-CA or fr by fr_FR. Null when none matches.
	 *
	 * @param string   $accept_language Accept-Language header, such as `fr-CA, fr;q=0.9, en;q=0.8`.
	 * @param string[] $locales         Locales to choose from, such as fr_FR.
	 * @return string|null
	 */
	public static function get_preferred_locale( string $accept_language, array $locales ): ?string {
		$ranges = array();

		foreach ( explode( ',', $accept_language ) as $range ) {
			$parameters = explode( ';', $range );
			$tag        = strtolower( str_replace( '-', '_', trim( array_shift( $parameters ) ) ) );
			$quality    = 1.0;

			foreach ( $parameters as $parameter ) {
				if ( preg_match( '/^\s*q\s*=\s*([01](?:\.\d{0,3})?)\s*$/i', $parameter, $matches ) ) {
					$quality = (float) $matches[1];
				}
			}

			if ( '' !== $tag && '*' !== $tag && $quality > 0 ) {
				$ranges[] = array(
					'tag'     => $tag,
					'quality' => $quality,
				);
			}
		}

		// Stable: the ranges of the same quality keep the order of the header.
		usort( $ranges, fn( array $a, array $b ) => $b['quality'] <=> $a['quality'] );

		foreach ( $ranges as $range ) {
			$language = strtok( $range['tag'], '_' );

			foreach ( $locales as $locale ) {
				if ( strtolower( $locale ) === $range['tag'] ) {
					return $locale;
				}
			}

			foreach ( $locales as $locale ) {
				if ( strtolower( (string) strtok( $locale, '_' ) ) === $language ) {
					return $locale;
				}
			}
		}

		return null;
	}

	/**
	 * Locales the plugin answers in: English, the language of its strings, and those of its translations.
	 *
	 * @return list<string>
	 */
	public static function get_locales(): array {
		if ( null === self::$locales ) {
			$files = glob( LIBRARY_DIR_PATH . 'languages/library-*.mo' );

			self::$locales = array( self::SOURCE_LOCALE );

			foreach ( false !== $files ? $files : array() as $file ) {
				self::$locales[] = substr( basename( $file, '.mo' ), strlen( 'library-' ) );
			}
		}

		return self::$locales;
	}

	/**
	 * Whether the request reads, by the method it is dispatched with: the one the `_method` parameter or the
	 * X-HTTP-Method-Override header gives, as core takes them, else its own.
	 *
	 * @return bool
	 */
	private static function is_read_request(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reads the method asked, as core does.
		$method = sanitize_key( wp_unslash( $_GET['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET' ) );
		$method = strtoupper( $method );

		return in_array( $method, array( 'GET', 'HEAD', 'OPTIONS' ), true );
	}

	/**
	 * Whether the request is one of the REST API, from its URL: WordPress tells it once it has parsed the request, after
	 * the translations load. A pretty URL, under the prefix of the REST API, or with the `rest_route` parameter.
	 *
	 * @return bool
	 */
	private static function is_rest_request(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reads the route asked, as core does.
		if ( isset( $_GET['rest_route'] ) ) {
			return true;
		}

		$request_uri = sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
		$home_path   = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );
		$rest_path   = preg_quote( $home_path, '#' ) . '(?:/index\.php)?/' . preg_quote( rest_get_url_prefix(), '#' );

		return 1 === preg_match( '#^' . $rest_path . '(?:/|$)#', $path );
	}
}
