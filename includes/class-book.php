<?php
/**
 * A book as the stats, the reading ideas, the achievements and the REST API read it.
 *
 * @link       https://github.com/19h47/library
 * @since      9.25.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * A book, with its meta and its terms, read once from its post by Books::get_books(): see it for what each field holds.
 *
 * A book of a list of readings, which the achievements and the reading ideas go through, stands for one of its readings,
 * its read date; with() gives such a copy, as a book cannot change.
 *
 * @since      9.25.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
final readonly class Book {

	/**
	 * Read dates, the ends of its readings ended, from the first to the latest, the unknown ones empty and first.
	 *
	 * @var string[]
	 */
	public array $read_dates;

	/**
	 * Whether it is read: whether it has a reading ended, of a known date or not.
	 *
	 * @var bool
	 */
	public bool $read;

	/**
	 * A book.
	 *
	 * @param int                  $id                Post ID.
	 * @param string               $title             Title of the post.
	 * @param string               $full_title        Title to show, with its series, volume and subtitle.
	 * @param Status               $status            Status: in the library, not kept or to buy.
	 * @param string               $series            Series after its cycles, as typed, or the title of a volume without series.
	 * @param int                  $series_id         Term ID of its series, 0 without.
	 * @param int[]                $series_ids        Term IDs of its series and of the cycles it is part of, the widest last.
	 * @param int|null             $volume            Volume number, null without.
	 * @param bool                 $collective        Whether it is a collective work.
	 * @param string[]             $readings          Readings, from their starts to their ends, in order: see Meta::get_readings().
	 * @param float|null           $rating            Rating out of 10, null without.
	 * @param int|null             $pages             Number of pages, null without.
	 * @param string               $published_date    Date it first came out, Y, Y-m or Y-m-d, or empty.
	 * @param string               $edition_date      Date its edition first came out, Y, Y-m or Y-m-d, or empty.
	 * @param string               $printed_date      Date its copy was printed, Y, Y-m or Y-m-d, or empty.
	 * @param string               $added             Date added, the date of the post.
	 * @param string[]             $authors           Names of its authors, by the name they are known by.
	 * @param int[]                $author_ids        Term IDs of its authors.
	 * @param int[]                $contributor_ids   Term IDs of the names with another role, such as a colorist.
	 * @param string[]             $translators       Names of its translators.
	 * @param string[]             $collections       Its collections and those they are part of, named after them.
	 * @param int[]                $collection_ids    Term IDs of its collections and those they are part of.
	 * @param int[]                $publisher_ids     Term IDs of its publishers, the widest ancestors of its collections.
	 * @param string               $collection_number Number in its collection, or empty.
	 * @param array<string,string> $languages         Names of its original languages, by their code.
	 * @param int[]                $language_ids      Term IDs of its original languages.
	 * @param array<string,string> $edition_languages Names of the languages of its text, by their code.
	 * @param string[]             $forms             Names of its forms.
	 * @param int[]                $form_ids          Term IDs of its forms.
	 * @param string               $read_date         The reading it stands for in a list of readings, Y-m-d, or empty.
	 */
	public function __construct(
		public int $id,
		public string $title,
		public string $full_title,
		public Status $status,
		public string $series,
		public int $series_id,
		public array $series_ids,
		public ?int $volume,
		public bool $collective,
		public array $readings,
		public ?float $rating,
		public ?int $pages,
		public string $published_date,
		public string $edition_date,
		public string $printed_date,
		public string $added,
		public array $authors,
		public array $author_ids,
		public array $contributor_ids,
		public array $translators,
		public array $collections,
		public array $collection_ids,
		public array $publisher_ids,
		public string $collection_number,
		public array $languages,
		public array $language_ids,
		public array $edition_languages,
		public array $forms,
		public array $form_ids,
		public string $read_date = '',
	) {
		$this->read_dates = array_map( fn( string $reading ) => Meta::split_reading( $reading )[1], Meta::get_ended( $readings ) );
		$this->read       = (bool) $this->read_dates;
	}

	/**
	 * A copy of the book with other values, such as a reading it stands for: `$book->with( read_date: '2024-05-12' )`.
	 *
	 * @param mixed ...$changes Values by field.
	 * @return self
	 */
	public function with( mixed ...$changes ): self {
		$fields = get_object_vars( $this );

		// Worked out from its readings.
		unset( $fields['read_dates'], $fields['read'] );

		return new self( ...array_replace( $fields, $changes ) );
	}

	/**
	 * The authors whose work it is part of, their names by term ID: none for a collective book, a magazine or an anthology,
	 * which is only found through its authors.
	 *
	 * @return array<int,string>
	 */
	public function get_work_authors(): array {
		return $this->collective ? array() : array_combine( $this->author_ids, $this->authors );
	}

	/**
	 * The year it first came out, or else the year of its edition, from Books::get_date(): null without either.
	 *
	 * @return int|null
	 */
	public function get_first_year(): ?int {
		$date = Books::get_date( $this );

		return '' !== $date ? (int) substr( $date, 0, 4 ) : null;
	}

	/**
	 * Its first reading: its first read date, empty when unknown, which comes first, or when it is not read.
	 *
	 * @return string
	 */
	public function get_first_read_date(): string {
		return $this->read_dates[0] ?? '';
	}

	/**
	 * Its latest reading: its latest read date, empty when unknown or when it is not read.
	 *
	 * @return string
	 */
	public function get_latest_read_date(): string {
		return $this->read_dates ? $this->read_dates[ array_key_last( $this->read_dates ) ] : '';
	}

	/**
	 * Whether it is translated: a translator is credited, or its text is in a language it was not written in, such as the
	 * French of a magazine in several languages, whose translation is not credited.
	 *
	 * @return bool
	 */
	public function is_translated(): bool {
		return $this->translators || ( $this->languages && array_diff_key( $this->edition_languages, $this->languages ) );
	}

	/**
	 * Whether it is read in its original language, rather than in `$own_language`: its text is in its original
	 * languages only, none of them the one of the reader. A book in the language of the reader, or of an edition
	 * unknown, says nothing.
	 *
	 * @param string $own_language Language of the reader, as an ISO 639 code, such as fr.
	 * @return bool
	 */
	public function is_in_original( string $own_language ): bool {
		return $this->languages && $this->edition_languages && ! array_diff_key( $this->edition_languages, $this->languages ) && ! isset( $this->edition_languages[ $own_language ] );
	}

	/**
	 * Whether it is at hand: in the library or passing through, not kept once read; a book to buy is not yet.
	 *
	 * @return bool
	 */
	public function is_at_hand(): bool {
		return Status::To_Buy !== $this->status;
	}

	/**
	 * Whether it is on the reading pile: not read, and at hand. The one rule of the books to read, for the stats, the
	 * reading ideas and the achievements.
	 *
	 * @return bool
	 */
	public function is_on_the_pile(): bool {
		return ! $this->read && $this->is_at_hand();
	}
}
