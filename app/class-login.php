<?php
/**
 * Login of the reading app: the login of WordPress, dressed as the app when it leads to it.
 *
 * @link       https://github.com/19h47/library
 * @since      9.34.0
 *
 * @package    Library
 * @subpackage Library/app
 */

namespace Library\App;

use Library\Plugin;

/**
 * Dresses the login of WordPress as the app when it leads back to the app, as when the app asks for the login: its name,
 * its icon, its theme and its styles, in place of the ones of WordPress. The login keeps the markup and the behavior
 * of core; any other login stays the one of WordPress.
 *
 * @since      9.34.0
 * @package    Library
 * @subpackage Library/app
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Login {

	/**
	 * Where the login leads, in the app, such as one of its views; empty when the login does not lead to the app.
	 *
	 * @var string
	 */
	private string $redirect_to = '';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'login_init', array( $this, 'dress' ) );
	}

	/**
	 * Where the login leads, when it leads to the app: WordPress sends there with the URL of the app, a view of it
	 * included, in redirect_to, which the form keeps after a wrong password; empty otherwise.
	 *
	 * @return string
	 */
	private function get_app_redirect(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reads where the login leads, changes nothing.
		$redirect_to = sanitize_url( wp_unslash( $_REQUEST['redirect_to'] ?? '' ) );
		$app_url     = Page::get_url();

		wp_parse_str( (string) wp_parse_url( $redirect_to, PHP_URL_QUERY ), $args );

		// The form of a lost password leads to the screen that says to check the email, which leads in its turn.
		if ( $this->get_page( $redirect_to ) === $this->get_page( site_url( 'wp-login.php', 'login' ) ) ) {
			$redirect_to = is_string( $args['redirect_to'] ?? null ) ? $args['redirect_to'] : '';

			wp_parse_str( (string) wp_parse_url( $redirect_to, PHP_URL_QUERY ), $args );
		}

		wp_parse_str( (string) wp_parse_url( $app_url, PHP_URL_QUERY ), $app_args );

		// The page of the app, whatever the scheme of its URL, with its arguments and others, such as its view.
		if ( $this->get_page( $redirect_to ) !== $this->get_page( $app_url ) ) {
			return '';
		}

		foreach ( $app_args as $key => $value ) {
			if ( ( $args[ $key ] ?? null ) !== $value ) {
				return '';
			}
		}

		return $redirect_to;
	}

	/**
	 * Host and path of a URL, without its scheme and its arguments.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function get_page( string $url ): string {
		return wp_parse_url( $url, PHP_URL_HOST ) . wp_parse_url( $url, PHP_URL_PATH );
	}

	/**
	 * A link of the login that leads where the login leads, in the app, unless it leads somewhere already.
	 *
	 * @param string $url URL of a screen of the login, such as the lost password.
	 * @return string
	 */
	public function lead_to_app( string $url ): string {
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $args );

		if ( isset( $args['redirect_to'] ) ) {
			return $url;
		}

		return add_query_arg( 'redirect_to', rawurlencode( $this->redirect_to ), $url );
	}

	/**
	 * Where a lost password asked leads: to the screen that says to check the email, dressed as the app, which leads
	 * back to its login.
	 *
	 * @return string
	 */
	public function get_check_email_url(): string {
		$args = array(
			'checkemail'  => 'confirm',
			'redirect_to' => rawurlencode( $this->redirect_to ),
		);

		return add_query_arg( $args, site_url( 'wp-login.php', 'login' ) );
	}

	/**
	 * Dress the login as the app, when it leads to it.
	 *
	 * @return void
	 */
	public function dress(): void {
		$this->redirect_to = $this->get_app_redirect();

		if ( '' === $this->redirect_to ) {
			return;
		}

		// The links between the screens of the login lead to the app too, so that each is dressed as the app: the lost
		// password and back to the login. A lost password asked, it says to check the email, rather than going to the app.
		add_filter( 'lostpassword_url', array( $this, 'lead_to_app' ) );
		add_filter( 'login_url', array( $this, 'lead_to_app' ) );
		add_filter( 'lostpassword_redirect', array( $this, 'get_check_email_url' ) );

		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'login_head', array( $this, 'print_head' ) );
		// The icon of the app instead of the one of the site.
		remove_action( 'login_head', 'wp_site_icon', 99 );
		add_filter( 'login_title', array( $this, 'get_title' ), 10, 2 );
		add_filter( 'login_headerurl', array( Page::class, 'get_url' ) );
		add_filter( 'login_headertext', array( $this, 'get_header_text' ) );
		add_action( 'login_footer', array( $this, 'remember_by_default' ) );
		// The app speaks the language of WordPress.
		add_filter( 'login_display_language_dropdown', '__return_false' );
	}

	/**
	 * The styles of the app instead of the ones of the login of WordPress, then the theme chosen in the app, before the
	 * first paint. The dashicons stay, for the icons of the buttons of core: the one that shows the password, the help
	 * of "Remember Me".
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		wp_dequeue_style( 'login' );

		Page::register_base_styles();

		[ $style_url, $style_version ] = Plugin::get_asset( 'app/css/library-login.css' );
		wp_enqueue_style( 'library-login', $style_url, array( 'library-app', 'dashicons' ), $style_version );

		[ $theme_url, $theme_version ] = Plugin::get_asset( 'app/js/library-app-theme.js' );
		wp_enqueue_script( 'library-app-theme', $theme_url, array(), $theme_version, false );
		wp_localize_script( 'library-app-theme', 'libraryApp', array( 'storageKey' => Page::STORAGE_KEY ) );
	}

	/**
	 * Print what the head of the app holds and the one of the login does not: the color schemes of the page before its
	 * styles, and the icons of the app.
	 *
	 * @return void
	 */
	public function print_head(): void {
		[ $favicon_url ]    = Plugin::get_asset( 'app/favicon.svg' );
		[ $touch_icon_url ] = Plugin::get_asset( 'app/apple-touch-icon.png' );

		?>
		<meta name="color-scheme" content="light dark">
		<link rel="icon" type="image/svg+xml" href="<?php echo esc_url( $favicon_url ); ?>">
		<link rel="apple-touch-icon" href="<?php echo esc_url( $touch_icon_url ); ?>">
		<?php
	}

	/**
	 * Title of the document, as the app names its pages: what the page shows, then the name of the app.
	 *
	 * @param string $login_title Title of the document, with the name of the site.
	 * @param string $title       Title of the page, such as Log In.
	 * @return string
	 */
	public function get_title( string $login_title, string $title ): string {
		/* translators: 1: what the page shows, such as a book or a view, 2: name of the app. */
		return sprintf( __( '%1$s – %2$s', 'library' ), $title, $this->get_name() );
	}

	/**
	 * Name of the app.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Library', 'library' );
	}

	/**
	 * The icon of the app, then its name, above the form instead of the logo of WordPress: the book of its icon on the
	 * home screen, in the color of the text, so that it follows the theme; the name alone names the link. Its box is the
	 * one of the book, without the margin of the grid of Material Symbols, so that it lines up with the name.
	 *
	 * The book is from Material Symbols, by Google, under the Apache License 2.0:
	 * https://github.com/google/material-design-icons
	 *
	 * @return string
	 */
	public function get_header_text(): string {
		$icon = '<svg class="library-login-icon" viewBox="160 -880 640 800" aria-hidden="true" focusable="false"><path d="M240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h480q33 0 56.5 23.5T800-800v640q0 33-23.5 56.5T720-80H240Zm200-440 100-60 100 60v-280H440v280Z"/></svg>';

		return $icon . esc_html( $this->get_name() );
	}

	/**
	 * Check "Remember Me" when the form first shows: the app is opened on a phone of one's own, where the login should
	 * last. After a wrong password, the form keeps the choice sent.
	 *
	 * @return void
	 */
	public function remember_by_default(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reads whether a form was sent, changes nothing.
		if ( ! empty( $_POST ) ) {
			return;
		}

		// In a block, so that its constant stays out of the global scope of the page.
		$script = <<<'JS'
{
	const $remember = document.getElementById( 'rememberme' );

	if ( $remember ) {
		$remember.checked = true;
	}
}
JS;

		wp_print_inline_script_tag( $script );
	}
}
