<?php
/**
 * Tests of the login of the reading app.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\App\Login;
use Library\App\Page;

/**
 * The login is dressed as the app when it leads to the app only.
 */
class Test_Login extends TestCase {

	/**
	 * Empty the request after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		unset( $_REQUEST['redirect_to'] );
		remove_all_filters( 'lostpassword_url' );
		remove_all_filters( 'login_url' );
		remove_all_filters( 'lostpassword_redirect' );

		parent::tear_down();
	}

	/**
	 * Whether the login is dressed as the app, once WordPress starts it with this redirect_to, slashed as a request.
	 *
	 * @param string|null $redirect_to Where the login leads, null for nowhere.
	 * @return bool
	 */
	private function is_dressed( ?string $redirect_to ): bool {
		if ( null !== $redirect_to ) {
			$_REQUEST['redirect_to'] = wp_slash( $redirect_to );
		}

		$login = new Login();

		$login->dress();

		$is_dressed = false !== has_action( 'login_enqueue_scripts', array( $login, 'enqueue_assets' ) );

		remove_action( 'login_enqueue_scripts', array( $login, 'enqueue_assets' ) );

		return $is_dressed;
	}

	/**
	 * The app, or any of its views, whatever the scheme of the URL.
	 *
	 * @return void
	 */
	public function test_the_login_of_the_app_is_dressed(): void {
		$this->assertTrue( $this->is_dressed( Page::get_url() ) );
		$this->assertTrue( $this->is_dressed( add_query_arg( 'view', 'diary', Page::get_url() ) ) );
		$this->assertTrue( $this->is_dressed( set_url_scheme( Page::get_url(), 'http' ) ) );
	}

	/**
	 * From the login of the app, the lost password and back to the login lead to the same view of the app, and a lost
	 * password asked says to check the email before.
	 *
	 * @return void
	 */
	public function test_the_screens_of_the_login_of_the_app_lead_to_it(): void {
		$diary = add_query_arg( 'view', 'diary', Page::get_url() );

		$this->is_dressed( $diary );

		$redirect_of = function ( string $url ): string {
			wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $args );

			return $args['redirect_to'] ?? '';
		};
		$check_email = apply_filters( 'lostpassword_redirect', $diary );

		$this->assertSame( $diary, $redirect_of( wp_lostpassword_url() ) );
		$this->assertSame( $diary, $redirect_of( wp_login_url() ) );
		$this->assertSame( $diary, $redirect_of( $check_email ) );
		$this->assertStringContainsString( 'checkemail=confirm', $check_email );
		// A link that leads somewhere already keeps it.
		$this->assertSame( admin_url(), $redirect_of( wp_login_url( admin_url() ) ) );

		remove_all_filters( 'login_url' );

		// The form of a lost password, sent with an error, is dressed again from where it leads.
		$this->assertTrue( $this->is_dressed( $check_email ) );
		$this->assertFalse( $this->is_dressed( site_url( 'wp-login.php?checkemail=confirm', 'login' ) ) );
	}

	/**
	 * The admin, another page of it or no redirect at all keep the login of WordPress.
	 *
	 * @return void
	 */
	public function test_another_login_is_the_one_of_wordpress(): void {
		$this->assertFalse( $this->is_dressed( null ) );
		$this->assertFalse( $this->is_dressed( admin_url() ) );
		$this->assertFalse( $this->is_dressed( admin_url( 'edit.php?post_type=library_book' ) ) );
		$this->assertFalse( $this->is_dressed( admin_url( 'edit.php?post_type=library_book&page=library-achievements' ) ) );
	}
}
