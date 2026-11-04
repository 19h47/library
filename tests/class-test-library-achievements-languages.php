<?php
/**
 * Tests of the languages: original languages read, in the original, polyglot, Rosetta Stone, translators and go-betweens.
 *
 * @package Library
 */

/**
 * Numbers of original languages read, books read without translator, languages read in a month or a week, and translators.
 */
class Test_Library_Achievements_Languages extends Library_TestCase {

	/**
	 * Original languages by code, with their name.
	 *
	 * @var array<string,string>
	 */
	private const array LANGUAGES = array(
		'ja' => 'Japanese',
		'it' => 'Italian',
		'de' => 'German',
		'es' => 'Spanish',
		'ru' => 'Russian',
		'pt' => 'Portuguese',
		'sv' => 'Swedish',
		'pl' => 'Polish',
		'nl' => 'Dutch',
		'fi' => 'Finnish',
		'en' => 'English',
	);

	/**
	 * Five original languages read earn it, by the book that brought the fifth one.
	 *
	 * @return void
	 */
	public function test_five_original_languages(): void {
		$this->add_read_in( 'ja', '2024-01-01' );
		$this->add_read_in( 'ja', '2024-01-02', array( 'title' => 'Japanese again' ) );
		$this->add_read_in( 'it', '2024-01-03' );
		$this->add_read_in( 'de', '2024-01-04' );
		$this->add_read_in( 'es', '2024-01-05' );
		$this->add_read_in( 'ru', '2024-01-06', array( 'title' => 'Fifth language' ) );
		$this->add_read_in( 'pt', '2024-01-07' );

		$languages = $this->get_earned()['languages'];

		$this->assertCount( 1, $languages );
		$this->assertSame( '5 original languages', $languages[0]['title'] );
		$this->assertSame( '', $languages[0]['subject'] );
		$this->assertSame( 'Fifth language', $languages[0]['detail'] );
		$this->assertSame( '2024-01-06', $languages[0]['date'] );
		$this->assertArrayNotHasKey( 'languages', $this->get_close() );
	}

	/**
	 * Each number of languages reached is earned, the latest first.
	 *
	 * @return void
	 */
	public function test_ten_original_languages_earn_both_numbers(): void {
		$this->add_languages_read( array_slice( array_keys( self::LANGUAGES ), 0, 10 ), '2024-01-01' );

		$languages = $this->get_earned()['languages'];

		$this->assertSame( array( '10 original languages', '5 original languages' ), array_column( $languages, 'title' ) );
		$this->assertSame( array( '2024-01-10', '2024-01-05' ), array_column( $languages, 'date' ) );
	}

	/**
	 * Four languages are close to five, undated, and so are three.
	 *
	 * @return void
	 */
	public function test_three_languages_are_close(): void {
		$this->add_languages_read( array( 'ja', 'it', 'de' ), '2024-01-01' );

		$this->assertArrayNotHasKey( 'languages', $this->get_earned() );

		$languages = $this->get_close()['languages'];

		$this->assertCount( 1, $languages );
		$this->assertSame( '5 original languages', $languages[0]['title'] );
		$this->assertSame( '3 languages read', $languages[0]['detail'] );
		$this->assertSame( '', $languages[0]['date'] );
		$this->assertSame( 3, $languages[0]['done'] );
		$this->assertSame( 5, $languages[0]['total'] );
	}

	/**
	 * Two languages are not close, however many books they have.
	 *
	 * @return void
	 */
	public function test_two_languages_are_not_close(): void {
		$this->add_languages_read( array( 'ja', 'ja', 'it', 'it' ), '2024-01-01' );

		$this->assertArrayNotHasKey( 'languages', $this->get_close() );
	}

	/**
	 * A book read in a foreign original language without translator is read in the original.
	 *
	 * @return void
	 */
	public function test_a_foreign_book_read_without_translator_is_in_the_original(): void {
		$this->add_read_in( 'ja', '2024-04-01', array( 'title' => 'Kokoro' ) );

		$original_language = $this->get_earned()['original-language'];

		$this->assertCount( 1, $original_language );
		$this->assertSame( 'In the original', $original_language[0]['title'] );
		$this->assertSame( 'Kokoro', $original_language[0]['subject'] );
		$this->assertSame( 'Read in its original language, without translator', $original_language[0]['detail'] );
		$this->assertSame( '2024-04-01', $original_language[0]['date'] );
	}

	/**
	 * A translated book is not read in the original, nor a book in the language of the admin, nor a book without language.
	 *
	 * @return void
	 */
	public function test_a_translated_book_or_one_in_the_language_of_the_admin_is_not_in_the_original(): void {
		$this->add_read_in( 'ja', '2024-04-01', array( 'people' => array( $this->get_translator( 'René de Ceccatty' ) ) ) );
		$this->add_read_in( 'en', '2024-04-02' );
		$this->add_reads( array( '2024-04-03' ) );

		$this->assertArrayNotHasKey( 'original-language', $this->get_earned() );
	}

	/**
	 * Five original languages read in a month make a polyglot, dated by the reading of the fifth one.
	 *
	 * @return void
	 */
	public function test_five_languages_read_in_a_month(): void {
		$this->add_languages_read( array( 'ja', 'it', 'ja', 'de', 'es', 'ru', 'pt' ), '2024-05-01' );

		$polyglot = $this->get_earned()['polyglot'];

		$this->assertCount( 1, $polyglot );
		$this->assertSame( 'Polyglot of the month', $polyglot[0]['title'] );
		$this->assertSame( 'May 2024', $polyglot[0]['subject'] );
		$this->assertSame( '5 original languages in a month', $polyglot[0]['detail'] );
		$this->assertSame( '2024-05-06', $polyglot[0]['date'] );
	}

	/**
	 * Four languages in a month are not enough, nor five across the end of a month.
	 *
	 * @return void
	 */
	public function test_five_languages_across_two_months_are_not_enough(): void {
		$this->add_languages_read( array( 'ja', 'it', 'de' ), '2024-01-29' );
		$this->add_languages_read( array( 'es', 'ru' ), '2024-02-01' );

		$this->assertArrayNotHasKey( 'polyglot', $this->get_earned() );
	}

	/**
	 * The latest month with five languages counts, and a book read again counts in the month of each reading.
	 *
	 * @return void
	 */
	public function test_the_latest_polyglot_month_counts_each_reading(): void {
		$this->add_book(
			array(
				'title'    => 'Read twice',
				'language' => $this->get_language( 'ja', self::LANGUAGES['ja'] ),
				'meta'     => array( 'library_read_date' => array( '2024-01-01', '2024-06-01' ) ),
			)
		);
		$this->add_languages_read( array( 'it', 'de', 'es', 'ru' ), '2024-01-02' );
		$this->add_languages_read( array( 'it', 'de', 'es', 'ru' ), '2024-06-02' );

		$polyglot = $this->get_earned()['polyglot'];

		$this->assertCount( 1, $polyglot );
		$this->assertSame( 'June 2024', $polyglot[0]['subject'] );
		$this->assertSame( '2024-06-05', $polyglot[0]['date'] );
	}

	/**
	 * Three original languages read in a week, from Monday to Sunday, are a Rosetta Stone, dated by the third one.
	 *
	 * @return void
	 */
	public function test_three_languages_read_in_a_week(): void {
		$this->add_read_in( 'ja', '2024-03-04' );
		$this->add_read_in( 'it', '2024-03-06' );
		$this->add_read_in( 'de', '2024-03-10' );

		$rosetta_stone = $this->get_earned()['rosetta-stone'];

		$this->assertCount( 1, $rosetta_stone );
		$this->assertSame( 'Rosetta Stone', $rosetta_stone[0]['title'] );
		$this->assertSame( '', $rosetta_stone[0]['subject'] );
		$this->assertSame( '3 original languages in a week', $rosetta_stone[0]['detail'] );
		$this->assertSame( '2024-03-10', $rosetta_stone[0]['date'] );
	}

	/**
	 * A week ends on Sunday: three languages from a Sunday to the Tuesday after are two weeks.
	 *
	 * @return void
	 */
	public function test_a_week_ends_on_sunday(): void {
		$this->add_read_in( 'ja', '2024-03-10' );
		$this->add_read_in( 'it', '2024-03-11' );
		$this->add_read_in( 'de', '2024-03-12' );

		$this->assertArrayNotHasKey( 'rosetta-stone', $this->get_earned() );
	}

	/**
	 * The last days of December can be the first week of the next year, as ISO weeks go.
	 *
	 * @return void
	 */
	public function test_a_week_across_a_new_year(): void {
		$this->add_read_in( 'ja', '2024-12-30' );
		$this->add_read_in( 'it', '2025-01-01' );
		$this->add_read_in( 'de', '2025-01-05' );

		$this->assertSame( '2025-01-05', $this->get_earned()['rosetta-stone'][0]['date'] );
	}

	/**
	 * The first days of January can be the last week of the year before, its 53rd.
	 *
	 * @return void
	 */
	public function test_the_53rd_week_across_a_new_year(): void {
		$this->add_read_in( 'ja', '2020-12-28' );
		$this->add_read_in( 'it', '2021-01-01' );
		$this->add_read_in( 'de', '2021-01-03' );

		$this->assertSame( '2021-01-03', $this->get_earned()['rosetta-stone'][0]['date'] );
	}

	/**
	 * The last Sunday of a year is not in the week that starts the next day, across the new year.
	 *
	 * @return void
	 */
	public function test_a_sunday_before_the_new_year_is_another_week(): void {
		$this->add_read_in( 'ja', '2024-12-29' );
		$this->add_read_in( 'it', '2024-12-30' );
		$this->add_read_in( 'de', '2025-01-01' );

		$this->assertArrayNotHasKey( 'rosetta-stone', $this->get_earned() );
	}

	/**
	 * Ten books read of a translator earn it, by the tenth one, with a search for the translator.
	 *
	 * @return void
	 */
	public function test_ten_books_of_a_translator(): void {
		$this->add_translated( 'Corinne Atlan', 11, '2024-01-01' );

		$translator = $this->get_earned()['translator'];

		$this->assertCount( 1, $translator );
		$this->assertSame( 'Faithful to 1 translator', $translator[0]['title'] );
		$this->assertSame( 'Corinne Atlan', $translator[0]['subject'] );
		$this->assertSame( '11 books', $translator[0]['detail'] );
		$this->assertSame( '2024-01-10', $translator[0]['date'] );
		$this->assertSame( 'translator:"Corinne Atlan"', $translator[0]['target']['search'] );
		$this->assertArrayNotHasKey( 'translator', $this->get_close() );
	}

	/**
	 * Several translators are one achievement, after the latest one to reach it.
	 *
	 * @return void
	 */
	public function test_several_translators_are_one_achievement(): void {
		$this->add_translated( 'Corinne Atlan', 10, '2024-03-01' );
		$this->add_translated( 'Jean-Paul Manganaro', 10, '2024-01-01' );

		$translator = $this->get_earned()['translator'];

		$this->assertCount( 1, $translator );
		$this->assertSame( 'Faithful to 2 translators', $translator[0]['title'] );
		$this->assertSame( 'Corinne Atlan', $translator[0]['subject'] );
		$this->assertSame( '2024-03-10', $translator[0]['date'] );
	}

	/**
	 * Nine books of a translator are close, undated.
	 *
	 * @return void
	 */
	public function test_nine_books_of_a_translator_are_close(): void {
		$this->add_translated( 'Corinne Atlan', 9, '2024-01-01' );

		$this->assertArrayNotHasKey( 'translator', $this->get_earned() );

		$translator = $this->get_close()['translator'];

		$this->assertCount( 1, $translator );
		$this->assertSame( 'Faithful to a translator', $translator[0]['title'] );
		$this->assertSame( 'Corinne Atlan', $translator[0]['subject'] );
		$this->assertSame( '9 books', $translator[0]['detail'] );
		$this->assertSame( '', $translator[0]['date'] );
		$this->assertSame( 9, $translator[0]['done'] );
		$this->assertSame( 10, $translator[0]['total'] );
	}

	/**
	 * Eight books of a translator are close, seven are not.
	 *
	 * @return void
	 */
	public function test_seven_books_of_a_translator_are_not_close(): void {
		$this->add_translated( 'Corinne Atlan', 8, '2024-01-01' );
		$this->add_translated( 'Jean-Paul Manganaro', 7, '2024-02-01' );

		$translator = $this->get_close()['translator'];

		$this->assertCount( 1, $translator );
		$this->assertSame( 'Corinne Atlan', $translator[0]['subject'] );
		$this->assertSame( 8, $translator[0]['done'] );
	}

	/**
	 * A translator read from two original languages is a go-between, dated by the book that brought the second one; the
	 * languages show in the order they were read.
	 *
	 * @return void
	 */
	public function test_a_translator_from_two_languages_is_a_go_between(): void {
		$translator = array( 'people' => array( $this->get_translator( 'Corinne Atlan' ) ) );

		$this->add_read_in( 'ja', '2024-01-01', $translator );
		$this->add_read_in( 'ja', '2024-02-01', $translator );
		$this->add_read_in( 'en', '2024-03-01', $translator );
		$this->add_read_in( 'de', '2024-04-01', $translator );

		$go_between = $this->get_earned()['go-between'];

		$this->assertCount( 1, $go_between );
		$this->assertSame( 'Go-between', $go_between[0]['title'] );
		$this->assertSame( 'Corinne Atlan', $go_between[0]['subject'] );
		$this->assertSame( 'Translated from Japanese, English, German', $go_between[0]['detail'] );
		$this->assertSame( '2024-03-01', $go_between[0]['date'] );
		$this->assertSame( 'translator:"Corinne Atlan"', $go_between[0]['target']['search'] );
	}

	/**
	 * A go-between leads to every book of the translator: a book kept after the first ones, which are not kept, shows
	 * the books kept.
	 *
	 * @return void
	 */
	public function test_a_go_between_leads_to_every_book_of_the_translator(): void {
		$translator = array( 'people' => array( $this->get_translator( 'Corinne Atlan' ) ) );
		$not_kept   = $translator + array( 'status' => Library_Posts::NOT_KEPT );

		$this->add_read_in( 'ja', '2024-01-01', $not_kept );
		$this->add_read_in( 'en', '2024-02-01', $not_kept );
		$this->add_read_in( 'ja', '2024-03-01', $translator );

		$this->assertFalse( $this->get_earned()['go-between'][0]['target']['not_kept'] );
	}

	/**
	 * A translator read from one language is no go-between, a book without original language aside.
	 *
	 * @return void
	 */
	public function test_a_translator_from_one_language_is_no_go_between(): void {
		$translator = array( 'people' => array( $this->get_translator( 'Corinne Atlan' ) ) );

		$this->add_read_in( 'ja', '2024-01-01', $translator );
		$this->add_read_in( 'ja', '2024-02-01', $translator );
		$this->add_reads( array( '2024-03-01' ), $translator );

		$this->assertArrayNotHasKey( 'go-between', $this->get_earned() );
	}

	/**
	 * Add a book read once in an original language.
	 *
	 * @param string $code      Code of the language, a key of LANGUAGES.
	 * @param string $read_date Read date, Y-m-d.
	 * @param array  $book      Other fields of the book, as add_book() takes them.
	 * @return int Book ID.
	 */
	private function add_read_in( string $code, string $read_date, array $book = array() ): int {
		return $this->add_book(
			array(
				'language' => $this->get_language( $code, self::LANGUAGES[ $code ] ),
				'meta'     => array( 'library_read_date' => array( $read_date ) ),
			) + $book
		);
	}

	/**
	 * Add a book read in each language given, one a day.
	 *
	 * @param string[] $codes Codes of the languages, keys of LANGUAGES.
	 * @param string   $first Read date of the first one, Y-m-d.
	 * @return void
	 */
	private function add_languages_read( array $codes, string $first ): void {
		foreach ( array_values( $codes ) as $index => $code ) {
			$this->add_read_in( $code, gmdate( 'Y-m-d', strtotime( $first . ' +' . $index . ' days' ) ) );
		}
	}

	/**
	 * Add books read translated by a translator, one a day.
	 *
	 * @param string $name  Name of the translator.
	 * @param int    $count Number of books.
	 * @param string $first Read date of the first one, Y-m-d.
	 * @return void
	 */
	private function add_translated( string $name, int $count, string $first ): void {
		$read_dates = array_map( fn( int $day ) => gmdate( 'Y-m-d', strtotime( $first . ' +' . $day . ' days' ) ), range( 0, $count - 1 ) );

		$this->add_reads( $read_dates, array( 'people' => array( $this->get_translator( $name ) ) ) );
	}

	/**
	 * A translator, as a person of the book form.
	 *
	 * @param string $name Name.
	 * @return array{name:string,role:string}
	 */
	private function get_translator( string $name ): array {
		return array(
			'name' => $name,
			'role' => 'translator',
		);
	}
}
