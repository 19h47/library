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
	 * View of the page of an author, opened from a book or an achievement rather than from the navigation.
	 *
	 * @var string
	 */
	public const AUTHOR_VIEW = 'author';

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
		$author_labels      = get_taxonomy( 'library-author' )->labels;
		$publisher_labels   = get_taxonomy( 'library-publisher' )->labels;
		$language_labels    = get_taxonomy( 'library-language' )->labels;
		$ratings            = $this->get_ratings();
		$statuses           = Library_Posts::get_statuses();
		$views              = $this->get_views( $labels->name );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reads which view to show, changes nothing.
		$view   = sanitize_key( wp_unslash( $_GET['view'] ?? '' ) );
		$author = absint( $_GET['author'] ?? 0 );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$view = isset( $views[ $view ] ) || ( self::AUTHOR_VIEW === $view && $author ) ? $view : 'books';

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
			'to-buy'       => __( 'To buy', 'library' ),
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
	 * Ratings a book can take, by value, with their label: the ones its meta is registered with, from 1 to 10 by half points.
	 *
	 * @return array<string,string>
	 */
	private function get_ratings(): array {
		$schema  = get_registered_meta_keys( 'post', 'book' )['rating']['show_in_rest']['schema'];
		$ratings = array();

		for ( $rating = $schema['minimum']; $rating <= $schema['maximum']; $rating += $schema['multipleOf'] ) {
			/* translators: %s: rating, e.g. 7.5. */
			$ratings[ (string) $rating ] = sprintf( __( '%s/10', 'library' ), Library_Books::rating_text( $rating ) );
		}

		return $ratings;
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
	 * Each brick is a script module, which imports the modules it shares with the others: every brick depends on them
	 * all, so that the import map gives them all, with versioned URLs that an update loads again. WordPress gives the store
	 * and each module their translations.
	 *
	 * @return void
	 */
	private function register_assets(): void {
		[ $base_url, $base_version ] = Library::asset( 'app/css/app-style.css' );
		wp_register_style( 'library-app-style', $base_url, array(), $base_version );

		[ $style_url, $style_version ] = Library::asset( 'app/css/library-app.css' );
		wp_register_style( 'library-app', $style_url, array( 'library-app-style' ), $style_version );

		[ $store_url, $store_version ] = Library::asset( 'app/js/library-app-store.js' );
		wp_register_script( 'library-app-store', $store_url, array( 'wp-api-fetch', 'wp-i18n' ), $store_version, false );
		wp_set_script_translations( 'library-app-store', 'library', LIBRARY_DIR_PATH . 'languages' );
		wp_localize_script( 'library-app-store', 'libraryApp', $this->get_config() );

		$shared = array();

		foreach ( glob( LIBRARY_DIR_PATH . 'app/js/lib/*.js' ) as $file ) {
			$name              = basename( $file, '.js' );
			[ $url, $version ] = Library::asset( "app/js/lib/$name.js" );
			$shared[]          = "library-app/$name";
			wp_register_script_module( "library-app/$name", $url, array(), $version );
			wp_set_script_module_translations( "library-app/$name", 'library', LIBRARY_DIR_PATH . 'languages' );
		}

		foreach ( glob( LIBRARY_DIR_PATH . 'app/js/bricks/*.js' ) as $file ) {
			$name              = basename( $file, '.js' );
			[ $url, $version ] = Library::asset( "app/js/bricks/$name.js" );
			wp_enqueue_script_module( "library-app/$name", $url, $shared, $version );
			wp_set_script_module_translations( "library-app/$name", 'library', LIBRARY_DIR_PATH . 'languages' );
		}
	}

	/**
	 * What the scripts need from the server: the statuses to list and the URLs. Their strings are translated by WordPress,
	 * from the JSON files of the languages folder.
	 *
	 * @return array
	 */
	private function get_config(): array {
		[ $favicon_url ]      = Library::asset( 'app/favicon.svg' );
		[ $favicon_dark_url ] = Library::asset( 'app/favicon-dark.svg' );

		return array(
			'statuses'  => implode( ',', Library_Books::statuses() ),
			'notKept'   => Library_Posts::NOT_KEPT,
			'toBuy'     => Library_Posts::TO_BUY,
			'editUrl'   => admin_url( 'post.php?action=edit&post=' ),
			// add_query_arg() drops the "=" of an empty value.
			'authorUrl' => add_query_arg( 'view', self::AUTHOR_VIEW, self::url() ) . '&author=',
			'bookUrl'   => self::url() . '&book=',
			'searchUrl' => self::url() . '&q=',
			'icons'     => array(
				'light' => $favicon_url,
				'dark'  => $favicon_dark_url,
			),
		);
	}
}
