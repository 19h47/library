<?php
/**
 * Tests of the language of the REST API.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Rest_Locale;

/**
 * A REST request is answered in the language its Accept-Language header prefers, among those of the plugin.
 */
class Test_Rest_Locale extends TestCase {

	/**
	 * Locales of the plugin, as its translations give them.
	 *
	 * @var list<string>
	 */
	private const array LOCALES = array( 'en_US', 'fr_FR' );

	/**
	 * Empty the request after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		unset( $_SERVER['HTTP_ACCEPT_LANGUAGE'], $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'], $_GET['_locale'], $_GET['rest_route'], $_GET['_method'] );
		$_SERVER['REQUEST_URI'] = '';

		parent::tear_down();
	}

	/**
	 * The locale a request determines, from its URL and its Accept-Language header, on a site in French.
	 *
	 * @param string $request_uri     URL of the request, from the root of the site.
	 * @param string $accept_language Accept-Language header.
	 * @param string $method          Method of the request.
	 * @return string
	 */
	private function negotiate( string $request_uri, string $accept_language, string $method = 'GET' ): string {
		$_SERVER['REQUEST_URI']          = $request_uri;
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $accept_language;
		$_SERVER['REQUEST_METHOD']       = $method;

		return new Rest_Locale()->negotiate( 'fr_FR' );
	}

	/**
	 * English, the language of the strings, and the translations of the plugin.
	 *
	 * @return void
	 */
	public function test_the_plugin_answers_in_english_and_its_translations(): void {
		$this->assertSame( self::LOCALES, Rest_Locale::get_locales() );
	}

	/**
	 * A locale matches as it is, else by its language alone, the highest quality first, the order of the header next.
	 *
	 * @return void
	 */
	public function test_the_preferred_locale_is_the_best_one_the_plugin_has(): void {
		$cases = array(
			'en'                       => 'en_US',
			'fr-FR'                    => 'fr_FR',
			'fr-CA'                    => 'fr_FR',
			'EN-gb, fr;q=0.5'          => 'en_US',
			'fr;q=0.5, en'             => 'en_US',
			'en;q=0.8, fr;q=0.8'       => 'en_US',
			'de, en;q=0.2'             => 'en_US',
			'fr;q=0, en;q=0.1'         => 'en_US',
			'fr ; q = 0.4 , en ;q=0.3' => 'fr_FR',
		);

		foreach ( $cases as $accept_language => $locale ) {
			$this->assertSame( $locale, Rest_Locale::get_preferred_locale( $accept_language, self::LOCALES ), $accept_language );
		}
	}

	/**
	 * No locale for a header empty, of languages the plugin does not have, or of any language.
	 *
	 * @return void
	 */
	public function test_no_locale_when_none_matches(): void {
		foreach ( array( '', 'de', 'de-DE, it;q=0.5', '*', 'en;q=0' ) as $accept_language ) {
			$this->assertNull( Rest_Locale::get_preferred_locale( $accept_language, self::LOCALES ), $accept_language );
		}
	}

	/**
	 * A REST request, by its pretty URL or its route parameter, is in the language it asks for; any other, in the one of
	 * the site, as is a REST request asking for none the plugin has.
	 *
	 * @return void
	 */
	public function test_a_rest_request_is_in_the_language_it_asks_for(): void {
		$this->assertSame( 'en_US', $this->negotiate( '/wp-json/library/v1/stats', 'en' ) );
		$this->assertSame( 'en_US', $this->negotiate( '/wp-json', 'en' ) );
		$this->assertSame( 'fr_FR', $this->negotiate( '/wp-json/library/v1/stats', 'de' ) );
		$this->assertSame( 'fr_FR', $this->negotiate( '/wp-jsonp/', 'en' ) );
		$this->assertSame( 'fr_FR', $this->negotiate( '/books/dune/', 'en' ) );
		$this->assertSame( 'fr_FR', $this->negotiate( '/wp-admin/', 'en' ) );

		$_GET['rest_route'] = '/library/v1/stats';

		$this->assertSame( 'en_US', $this->negotiate( '/', 'en' ) );
	}

	/**
	 * A request that writes is in the language of the site, whatever method it is sent with, as core dispatches it: what
	 * it saves does not depend on who sends it.
	 *
	 * @return void
	 */
	public function test_a_request_that_writes_is_in_the_language_of_the_site(): void {
		$this->assertSame( 'en_US', $this->negotiate( '/wp-json/wp/v2/books', 'en', 'OPTIONS' ) );
		$this->assertSame( 'en_US', $this->negotiate( '/wp-json/wp/v2/books', 'en', 'HEAD' ) );
		$this->assertSame( 'fr_FR', $this->negotiate( '/wp-json/library/v1/covers', 'en', 'POST' ) );
		$this->assertSame( 'fr_FR', $this->negotiate( '/wp-json/library/v1/books/1', 'en', 'PUT' ) );

		$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'DELETE';

		$this->assertSame( 'fr_FR', $this->negotiate( '/wp-json/wp/v2/books/1', 'en', 'POST' ) );

		$_GET['_method'] = 'POST';

		$this->assertSame( 'fr_FR', $this->negotiate( '/wp-json/library/v1/covers', 'en' ) );
	}

	/**
	 * The language of the user, which the reading app and the admin ask for, comes before the header.
	 *
	 * @return void
	 */
	public function test_the_language_of_the_user_comes_first(): void {
		$_GET['_locale'] = 'user';

		$this->assertSame( 'fr_FR', $this->negotiate( '/wp-json/library/v1/stats', 'en' ) );
	}
}
