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

namespace Library\App;

use Library\Books;
use Library\Meta;
use Library\Plugin;
use Library\Posts;
use Library\Status;
use Library\Taxonomies;
use WP_Term;

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
class Page {

	/**
	 * Slug of the app page, in its URL.
	 *
	 * @var string
	 */
	public const string SLUG = 'library';

	/**
	 * View of the page of a person, opened from a book or an achievement rather than from the navigation.
	 *
	 * @var string
	 */
	public const string PERSON_VIEW = 'person';

	/**
	 * View of a book, opened from its card, its cover or a link rather than from the navigation.
	 *
	 * @var string
	 */
	public const string BOOK_VIEW = 'book';

	/**
	 * View of a series or a cycle, opened from a book, an achievement or another series.
	 *
	 * @var string
	 */
	public const string SERIES_VIEW = 'series';

	/**
	 * View of a collection or a publisher, opened from an achievement or another collection.
	 *
	 * @var string
	 */
	public const string PUBLISHER_VIEW = 'publisher';

	/**
	 * View of the recap of a year, with the year in the argument of the same name, opened from the diary or, in January,
	 * from the home.
	 *
	 * @var string
	 */
	public const string RECAP_VIEW = 'recap';

	/**
	 * Fields of a book the app reads, from the core REST API: its cover and terms as the plugin gives them, nothing embedded.
	 *
	 * @var string
	 */
	public const string BOOK_FIELDS = 'id,title,main_title,excerpt,content,credits,status,meta,readings,cover,names,series,series_name,language_codes,edition_language_codes,form_name,publisher_names,date';

	/**
	 * Books a list of the app loads at once, then as its end comes.
	 *
	 * @var int
	 */
	public const int PER_PAGE = 24;

	/**
	 * Most books a page of the REST API gives, as for the series of a book.
	 *
	 * @var int
	 */
	public const int MAX_PER_PAGE = 100;

	/**
	 * View of the components of the app, for development, opened by searching its name then Enter rather than from the navigation.
	 *
	 * @var string
	 */
	public const string STYLEGUIDE_VIEW = 'styleguide';

	/**
	 * The reference of the API, for development, opened by searching "api" then Enter.
	 *
	 * @var string
	 */
	public const string API_VIEW = 'api';

	/**
	 * Key of localStorage the app keeps its choices in, its theme read before the first paint, of the login too.
	 *
	 * @var string
	 */
	public const string STORAGE_KEY = 'library-app';

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
	public static function get_url(): string {
		return admin_url( 'edit.php?post_type=library_book&page=' . self::SLUG );
	}

	/**
	 * Add the app page under Books; it renders on its load hook, before the admin header.
	 *
	 * @return void
	 */
	public function add_page(): void {
		$hook = add_submenu_page(
			'edit.php?post_type=library_book',
			__( 'Library', 'library' ),
			__( 'App', 'library' ),
			'edit_posts',
			self::SLUG,
			'__return_null'
		);

		if ( $hook ) {
			add_action( "load-$hook", array( $this, 'render_page' ) );
		}
	}

	/**
	 * Render the app, its manifest or its service worker, then stop before the admin header.
	 *
	 * @return void
	 */
	public function render_page(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reads which file to serve, changes nothing.
		if ( isset( $_GET['manifest'] ) ) {
			$this->render_manifest();
			exit;
		}

		if ( isset( $_GET['service-worker'] ) ) {
			$this->render_service_worker();
			exit;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$this->register_assets();

		$manifest_url       = add_query_arg( 'manifest', '1', self::get_url() );
		[ $favicon_url ]    = Plugin::get_asset( 'app/favicon.svg' );
		[ $touch_icon_url ] = Plugin::get_asset( 'app/apple-touch-icon.png' );
		$labels             = get_post_type_object( 'library_book' )->labels;
		$person_labels      = get_taxonomy( 'library_person' )->labels;
		$publisher_labels   = get_taxonomy( 'library_publisher' )->labels;
		$language_labels    = get_taxonomy( 'library_language' )->labels;
		$form_labels        = get_taxonomy( 'library_form' )->labels;
		$form_choices       = Taxonomies::get_term_choices( 'library_form' );
		$language_choices   = Taxonomies::get_term_choices( 'library_language' );
		$own_language       = get_term_by( 'slug', Taxonomies::get_own_language_code(), 'library_language' );
		$own_language_id    = $own_language instanceof WP_Term ? $own_language->term_id : 0;
		$rating_choices     = Meta::get_rating_choices();
		$status_choices     = Status::get_choices();
		$views              = $this->get_views( $labels->name );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reads which view to show, changes nothing.
		$view = sanitize_key( wp_unslash( $_GET['view'] ?? '' ) );
		// The page of a person or of a book needs its ID, in the argument of the same name: ?view=person&person=12, as the recap its year.
		$id = absint( $_GET[ $view ] ?? 0 );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$view = isset( $views[ $view ] ) || ( in_array( $view, array( self::PERSON_VIEW, self::BOOK_VIEW, self::SERIES_VIEW, self::PUBLISHER_VIEW, self::RECAP_VIEW ), true ) && $id ) || in_array( $view, array( self::STYLEGUIDE_VIEW, self::API_VIEW ), true ) ? $view : 'books';

		$this->preload( $view, $id );

		require LIBRARY_DIR_PATH . 'app/partials/library-app.php';
		exit;
	}

	/**
	 * Give the app the data it asks for first, in the page itself, as the editor of WordPress does: the server runs those
	 * requests of the REST API while it renders the page, and wp.apiFetch answers them from it, without network. They
	 * are the stats, the books to buy and, as the view needs them, the books found and the home, the book shown, the
	 * page of a series or of a collection, or the recap of a year; not the volumes of the series of the book, which load
	 * once the page has, when no preloaded answer is read any more. Their paths are the ones the store asks for, in any order: a filter kept on the phone asks for another,
	 * which goes to the network. The reading idea and the achievements load after, as they take longer than the rest.
	 *
	 * @param string $view View shown.
	 * @param int    $id   ID of the book or the term shown, or the year of the recap, in its view.
	 * @return void
	 */
	private function preload( string $view, int $id ): void {
		// The paths of books with a space as %20, as wp.url encodes it: wp.apiFetch matches the paths once decoded, a + as itself.
		$statuses = implode( ',', Status::get_values() );
		$books    = fn( array $args ) => '/wp/v2/books?' . http_build_query( array( '_fields' => self::BOOK_FIELDS ) + $args, '', '&', PHP_QUERY_RFC3986 );
		$list     = fn( array $args ) => $books(
			$args + array(
				'per_page' => self::PER_PAGE,
				'offset'   => 0,
			)
		);
		$paths    = array( '/library/v1/stats', $list( array( 'status' => Status::To_Buy->value ) ) );

		if ( 'books' === $view ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reads the search shown, changes nothing.
			$query   = trim( sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ) );
			$paths[] = $list( ( '' !== $query ? array( 'search' => $query ) : array() ) + array( 'status' => $statuses ) );

			if ( '' === $query ) {
				$paths[] = $list(
					array(
						'search'  => 'progress:yes',
						'status'  => $statuses,
						'orderby' => 'started',
					)
				);
				$paths[] = '/library/v1/readings?page=1';
			}
		}

		if ( self::BOOK_VIEW === $view ) {
			$paths[] = $this->get_books_path( $books, array( $id ), $statuses );
		}

		// The page of a person, a series or a collection, or the recap of a year, by the route of its view, as the store asks for it.
		$page_routes = array(
			self::PERSON_VIEW    => 'people',
			self::SERIES_VIEW    => 'series',
			self::PUBLISHER_VIEW => 'publishers',
			self::RECAP_VIEW     => 'recaps',
		);

		if ( isset( $page_routes[ $view ] ) ) {
			$paths[] = "/library/v1/{$page_routes[ $view ]}/$id";
		}

		$data = array_reduce( $paths, 'rest_preload_api_request', array() );

		// The books of the first readings of the diary, as the store asks for them.
		$months = $data['/library/v1/readings?page=1']['body'] ?? array();
		$read   = array_merge( ...array_map( fn( array $month ) => array_column( $month['readings'], 'book_id' ), $months ) );

		if ( $read ) {
			$data = rest_preload_api_request( $data, $this->get_books_path( $books, $read, $statuses ) );
		}

		// The books read in the year of the recap, a page of the REST API at a time, as the store asks for them.
		$recap_books = $data[ "/library/v1/recaps/$id" ]['body']['books'] ?? array();

		sort( $recap_books );

		foreach ( self::RECAP_VIEW === $view ? array_chunk( $recap_books, self::MAX_PER_PAGE ) : array() as $chunk ) {
			$data = rest_preload_api_request( $data, $this->get_books_path( $books, $chunk, $statuses ) );
		}

		// Only the first requests of the page, until it has loaded: a request after, such as a search again without a filter
		// kept on the phone, would get a preloaded answer left unused, as the page loaded.
		$script = <<<'JS'
( () => {
	const preloading = wp.apiFetch.createPreloadingMiddleware( %s );
	let isLoading = true;

	addEventListener( 'load', () => {
		isLoading = false;
	} );
	wp.apiFetch.use( ( options, next ) => ( isLoading ? preloading( options, next ) : next( options ) ) );
} )();
JS;

		wp_add_inline_script( 'wp-api-fetch', sprintf( $script, wp_json_encode( $data ) ), 'after' );
	}

	/**
	 * The path the store asks for books with: their IDs once each and in order, of any status, as many as they are.
	 *
	 * @param callable $books    Path of books, from its arguments.
	 * @param int[]    $book_ids Book IDs.
	 * @param string   $statuses Statuses, separated with commas.
	 * @return string
	 */
	private function get_books_path( callable $books, array $book_ids, string $statuses ): string {
		$book_ids = array_unique( array_map( 'intval', $book_ids ) );

		sort( $book_ids );

		return $books(
			array(
				'status'   => $statuses,
				'include'  => implode( ',', $book_ids ),
				'per_page' => count( $book_ids ),
			)
		);
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
				'url'   => 'books' === $view ? self::get_url() : add_query_arg( 'view', $view, self::get_url() ),
			);
		}

		return $views;
	}

	/**
	 * Render the web app manifest, which makes the app installable.
	 *
	 * The page links it with crossorigin="use-credentials": it is behind the login, like the app.
	 * Its icons are the dark ones: the home screen icon of a web app does not follow the system theme.
	 *
	 * @return void
	 */
	private function render_manifest(): void {
		[ $icon_url ]       = Plugin::get_asset( 'app/favicon-dark.svg' );
		[ $touch_icon_url ] = Plugin::get_asset( 'app/apple-touch-icon.png' );

		$manifest = array(
			'name'             => __( 'Library', 'library' ),
			'short_name'       => __( 'Library', 'library' ),
			'start_url'        => self::get_url(),
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
	 * Render the service worker, which keeps the app and what it loaded for a visit without network.
	 *
	 * Served from the URL of the app rather than from the plugin folder: a service worker controls the pages under its own
	 * folder only, and the app is in the admin. Its scope is the URL of the app, so that it controls no other admin page.
	 *
	 * @return void
	 */
	private function render_service_worker(): void {
		nocache_headers();
		header( 'Content-Type: text/javascript; charset=' . get_option( 'blog_charset' ) );
		readfile( LIBRARY_DIR_PATH . 'app/js/library-app-service-worker.js' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- A file of the plugin, served as is.
	}

	/**
	 * Register the styles and scripts the page prints: the theme in the head, before the first paint, the router then the bricks after the markup.
	 *
	 * The styles of the app come after app-style.css, the base shared by 19h47's small apps, copied from its gist:
	 * library-app.css, the order of their layers and their tokens, then a sheet per component and per view.
	 * Each brick is a script module, which imports the modules it shares with the others: every brick depends on them
	 * all, so that the import map gives them all, with versioned URLs that an update loads again, the store among them.
	 * WordPress gives each module its translations.
	 *
	 * @return void
	 */
	private function register_assets(): void {
		self::register_base_styles();

		$sheets = array();

		foreach ( array( 'components', 'views' ) as $folder ) {
			$sheet_files = glob( LIBRARY_DIR_PATH . "app/css/$folder/*.css" );

			foreach ( $sheet_files ? $sheet_files : array() as $file ) {
				$name              = basename( $file, '.css' );
				$handle            = "library-app-$folder-$name";
				[ $url, $version ] = Plugin::get_asset( "app/css/$folder/$name.css" );
				$sheets[]          = $handle;
				wp_register_style( $handle, $url, array( 'library-app' ), $version );
			}
		}

		// No file of its own: printed, it prints its sheets, the components then the views, as their layers follow one another.
		wp_register_style( 'library-app-sheets', false, $sheets ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- No URL to version.

		// The theme before the first paint, with what the modules take from WordPress: wp.apiFetch, wp.i18n, wp.url and the config.
		[ $theme_url, $theme_version ] = Plugin::get_asset( 'app/js/library-app-theme.js' );
		wp_register_script( 'library-app-theme', $theme_url, array( 'wp-api-fetch', 'wp-i18n', 'wp-url' ), $theme_version, false );
		wp_localize_script( 'library-app-theme', 'libraryApp', $this->get_config() );

		$shared = array();

		$lib_files = glob( LIBRARY_DIR_PATH . 'app/js/lib/*.js' );

		foreach ( $lib_files ? $lib_files : array() as $file ) {
			$name              = basename( $file, '.js' );
			[ $url, $version ] = Plugin::get_asset( "app/js/lib/$name.js" );
			$shared[]          = "library-app/$name";
			wp_register_script_module( "library-app/$name", $url, array(), $version );
			wp_set_script_module_translations( "library-app/$name", 'library', LIBRARY_DIR_PATH . 'languages' );
		}

		// Before the bricks: it reads the URL, which says what they show.
		wp_enqueue_script_module( 'library-app/router' );

		$brick_files = glob( LIBRARY_DIR_PATH . 'app/js/bricks/*.js' );

		foreach ( $brick_files ? $brick_files : array() as $file ) {
			$name              = basename( $file, '.js' );
			[ $url, $version ] = Plugin::get_asset( "app/js/bricks/$name.js" );
			wp_enqueue_script_module( "library-app/$name", $url, $shared, $version );
			wp_set_script_module_translations( "library-app/$name", 'library', LIBRARY_DIR_PATH . 'languages' );
		}
	}

	/**
	 * Register the styles the app and its login share: app-style.css, the base shared by 19h47's small apps, then
	 * library-app.css, the order of the layers of the app and its tokens.
	 *
	 * @return void
	 */
	public static function register_base_styles(): void {
		[ $base_url, $base_version ] = Plugin::get_asset( 'app/css/app-style.css' );
		wp_register_style( 'library-app-style', $base_url, array(), $base_version );

		[ $style_url, $style_version ] = Plugin::get_asset( 'app/css/library-app.css' );
		wp_register_style( 'library-app', $style_url, array( 'library-app-style' ), $style_version );
	}

	/**
	 * What the scripts need from the server: the statuses and forms to list and the URLs. Their strings are translated by WordPress,
	 * from the JSON files of the languages folder.
	 *
	 * @return array<string,mixed>
	 */
	private function get_config(): array {
		[ $favicon_url ]      = Plugin::get_asset( 'app/favicon.svg' );
		[ $favicon_dark_url ] = Plugin::get_asset( 'app/favicon-dark.svg' );

		return array(
			'statuses'         => implode( ',', Status::get_values() ),
			'bookFields'       => self::BOOK_FIELDS,
			'perPage'          => self::PER_PAGE,
			'maxPerPage'       => self::MAX_PER_PAGE,
			'forms'            => implode( ',', array_keys( Taxonomies::get_term_choices( 'library_form' ) ) ),
			'inLibrary'        => Status::In_Library->value,
			'notKept'          => Status::Not_Kept->value,
			'toBuy'            => Status::To_Buy->value,
			// The roles other than author, as the people of a book name them: translator, colorist and the others.
			'roles'            => array_keys( Meta::ROLE_KEYS ),
			'editUrl'          => admin_url( 'post.php?action=edit&post=' ),
			// The router builds the URLs of the views from it.
			'appUrl'           => self::get_url(),
			// The URL of the app is the scope of its service worker.
			'serviceWorkerUrl' => add_query_arg( 'service-worker', '1', self::get_url() ),
			'storageKey'       => self::STORAGE_KEY,
			'icons'            => array(
				'light' => $favicon_url,
				'dark'  => $favicon_dark_url,
			),
		);
	}
}
