<?php
/**
 * Reading app: the library on a phone, as an installable web app.
 *
 * @link       https://github.com/19h47/library
 * @since      4.4.0
 *
 * @package    Library
 * @subpackage Library/app
 */

/**
 * Adds the app page under Books and renders it full screen, instead of the admin interface.
 *
 * The page is an admin page, so core handles the login, the capability and the user language;
 * the app reads the library through the REST API, with wp.apiFetch for the root URL and the nonce.
 *
 * @since      4.4.0
 * @package    Library
 * @subpackage Library/app
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_App {

	/**
	 * Slug of the app page, in its URL.
	 *
	 * @var string
	 */
	public const SLUG = 'library';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * URL of the app, the start URL of the installed app too.
	 *
	 * @return string
	 */
	public static function url(): string {
		return admin_url( 'edit.php?post_type=book&page=' . self::SLUG );
	}

	/**
	 * Add the app page under Books; it renders on its load hook, before the admin header.
	 *
	 * @return void
	 */
	public function add_page(): void {
		$hook = add_submenu_page(
			'edit.php?post_type=book',
			__( 'Library', 'library' ),
			__( 'App', 'library' ),
			'edit_posts',
			self::SLUG,
			'__return_null'
		);

		if ( $hook ) {
			add_action( "load-$hook", array( $this, 'render' ) );
		}
	}

	/**
	 * Render the app, or its manifest, then stop before the admin header.
	 *
	 * @return void
	 */
	public function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reads which file to serve, changes nothing.
		if ( isset( $_GET['manifest'] ) ) {
			$this->render_manifest();
			exit;
		}

		$this->register_assets();

		$manifest_url       = add_query_arg( 'manifest', '1', self::url() );
		[ $favicon_url ]    = Library::asset( 'app/favicon.svg' );
		[ $touch_icon_url ] = Library::asset( 'app/apple-touch-icon.png' );
		$labels             = get_post_type_object( 'book' )->labels;
		$languages          = get_taxonomy( 'library-language' )->labels->name;
		$views              = $this->get_views( $labels->name );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reads which view to show, changes nothing.
		$view = sanitize_key( wp_unslash( $_GET['view'] ?? '' ) );
		$view = isset( $views[ $view ] ) ? $view : 'books';

		require LIBRARY_DIR_PATH . 'app/partials/library-app.php';
		exit;
	}

	/**
	 * Views of the app, by their name in the view query argument, with their label and URL; the books are the default view.
	 *
	 * @param string $books Label of the books view.
	 * @return array<string,array{label:string,url:string}>
	 */
	private function get_views( string $books ): array {
		$labels = array(
			'books'        => $books,
			'diary'        => __( 'Diary', 'library' ),
			'idea'         => __( 'Idea', 'library' ),
			'achievements' => __( 'Achievements', 'library' ),
		);
		$views  = array();

		foreach ( $labels as $view => $label ) {
			$views[ $view ] = array(
				'label' => $label,
				'url'   => 'books' === $view ? self::url() : add_query_arg( 'view', $view, self::url() ),
			);
		}

		return $views;
	}

	/**
	 * Render the web app manifest, which makes the app installable.
	 *
	 * The page links it with crossorigin="use-credentials": it is behind the login, like the app.
	 *
	 * @return void
	 */
	private function render_manifest(): void {
		[ $icon_url ]       = Library::asset( 'app/favicon.svg' );
		[ $touch_icon_url ] = Library::asset( 'app/apple-touch-icon.png' );

		$manifest = array(
			'name'             => __( 'Library', 'library' ),
			'short_name'       => __( 'Library', 'library' ),
			'start_url'        => self::url(),
			'scope'            => admin_url(),
			'display'          => 'standalone',
			'background_color' => '#ffffff',
			'theme_color'      => '#ffffff',
			'icons'            => array(
				array(
					'src'   => $icon_url,
					'sizes' => 'any',
					'type'  => 'image/svg+xml',
				),
				array(
					'src'   => $touch_icon_url,
					'sizes' => '180x180',
					'type'  => 'image/png',
				),
			),
		);

		header( 'Content-Type: application/manifest+json; charset=' . get_option( 'blog_charset' ) );
		echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Register the styles and scripts the page prints: the store in the head, before the first paint, the bricks after the markup.
	 *
	 * The styles of the app come after app-style.css, the base shared by 19h47's small apps, copied from its gist.
	 *
	 * @return void
	 */
	private function register_assets(): void {
		[ $base_url, $base_version ] = Library::asset( 'app/css/app-style.css' );
		wp_register_style( 'library-app-style', $base_url, array(), $base_version );

		[ $style_url, $style_version ] = Library::asset( 'app/css/library-app.css' );
		wp_register_style( 'library-app', $style_url, array( 'library-app-style' ), $style_version );

		[ $store_url, $store_version ] = Library::asset( 'app/js/library-app-store.js' );
		wp_register_script( 'library-app-store', $store_url, array( 'wp-api-fetch' ), $store_version, false );
		wp_localize_script( 'library-app-store', 'libraryApp', $this->get_config() );

		[ $script_url, $script_version ] = Library::asset( 'app/js/library-app.js' );
		wp_register_script( 'library-app', $script_url, array( 'library-app-store', 'wp-i18n' ), $script_version, true );
	}

	/**
	 * What the scripts need from the server: the statuses to list, the URLs and the strings built in JavaScript.
	 *
	 * A string with placeholders is formatted with wp.i18n.sprintf. A plural is given in its two forms,
	 * picked with Intl.PluralRules: "one" also takes 0 in French.
	 *
	 * @return array
	 */
	private function get_config(): array {
		[ $favicon_url ]      = Library::asset( 'app/favicon.svg' );
		[ $favicon_dark_url ] = Library::asset( 'app/favicon-dark.svg' );

		return array(
			'statuses' => implode( ',', Library_Books::statuses() ),
			'notKept'  => Library_Posts::NOT_KEPT,
			'editUrl'  => admin_url( 'post.php?action=edit&post=' ),
			'icons'    => array(
				'light' => $favicon_url,
				'dark'  => $favicon_dark_url,
			),
			'strings'  => array(
				'books'       => array(
					/* translators: %s: number of books. */
					'one'   => _n( '%s book', '%s books', 1, 'library' ),
					/* translators: %s: number of books. */
					'other' => _n( '%s book', '%s books', 2, 'library' ),
				),
				/* translators: %s: reading percentage. */
				'readPercent' => __( '%s%% read', 'library' ),
				/* translators: 1: total books, 2: read books, 3: percent read. */
				'summary'     => __( '%1$s books, %2$s read (%3$s%%)', 'library' ),
				/* translators: 1: books read this year, 2: year, 3: books read by the same date last year. */
				'yearToDate'  => __( '%1$s read in %2$d, %3$s by this date last year', 'library' ),
				/* translators: %s: number of books expected by the end of the year. */
				'projection'  => __( 'About %s by the end of the year at this pace', 'library' ),
				/* translators: 1: number done, 2: total, e.g. "13 of 14". */
				'progress'    => __( '%1$s of %2$s', 'library' ),
				/* translators: %s: rating, e.g. 7.5. */
				'rating'      => __( '%s/10', 'library' ),
				'read'        => __( 'Read', 'library' ),
				'readAgain'   => __( 'Read again', 'library' ),
				'toRead'      => __( 'To read', 'library' ),
				'yes'         => __( 'Yes', 'library' ),
				'no'          => __( 'No', 'library' ),
				'notKept'     => _x( 'Not kept', 'book status', 'library' ),
				'loading'     => __( 'Loading…', 'library' ),
				'noBooks'     => __( 'No book found.', 'library' ),
				'error'       => __( 'The books could not be loaded.', 'library' ),
			),
		);
	}
}
