<?php
/**
 * Achievements, drawn from the books.
 *
 * @link       https://github.com/19h47/library
 * @since      2.2.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

use WP_Term;

/**
 * Achievements drawn from the books, computed on each view: nothing is stored, so a completion is lost
 * when a book to read joins it, and comes back once it is read.
 *
 * By family: completions (series and their cycles, authors and collections read in full, collections read by half or
 * more), milestones (books and pages read, record year and month, months in a row, a book read
 * again, whose level is the most times a book was read again), discoveries
 * (hot off the press, a centenarian, the oldest of the pile, the decades of the 20th century), languages
 * and translators, and easter eggs. Books not kept count, since they are read; books to buy count as books
 * to read in a completion, which lacks them, but are not on the pile, unlike the books not kept. Completions and translators
 * are one achievement each, whose level is the number of subjects that earned it. An achievement is dated
 * by the reading that earned it, when it has a date. Achievements that count books take their first
 * reading; those about readings over time count a book read again once per reading.
 *
 * @since      2.2.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 *
 * @phpstan-type Completion_Kind 'author'|'series'|'collection'
 * @phpstan-type Completion_Group non-empty-array<Completion_Kind,non-empty-array<int,non-empty-list<Book>>>
 * @phpstan-type Book_Target array{type:'book',id:int}
 * @phpstan-type Search_Target array{type:'search',search:string,not_kept:bool}
 * @phpstan-type Term_Target array{type:Completion_Kind,id:int,not_kept:bool}
 * @phpstan-type Target Book_Target|Search_Target|Term_Target
 * @phpstan-type Achievement array{kind:string,title:string,subject:string,detail:string,target:Target|null,date:string,easter_egg:bool}
 * @phpstan-type Close_Achievement array{kind:string,title:string,subject:string,detail:string,target:Target|null,date:string,easter_egg:bool,done:int,total:int}
 * @phpstan-type Achievements_List array{earned:list<Achievement>,close:list<Close_Achievement>}
 */
class Achievements {

	/**
	 * Number of books from which reading all those of an author, a series or its cycle, or a collection counts.
	 *
	 * @var array<string,int>
	 */
	private const array COMPLETION_BOOKS = array(
		'author'     => 3,
		'series'     => 2,
		'collection' => 3,
	);

	/**
	 * Number of books of a collection from which reading half of them counts.
	 *
	 * @var int
	 */
	private const int COLLECTION_STEP_BOOKS = 10;

	/**
	 * Number of books left up to which a completion is close.
	 *
	 * @var int
	 */
	private const int CLOSE_BOOKS = 2;

	/**
	 * Numbers of books read that count.
	 *
	 * @var int[]
	 */
	private const array READ_MILESTONES = array( 100, 250, 500, 750, 1000, 1500, 2000, 3000 );

	/**
	 * Numbers of pages read that count.
	 *
	 * @var int[]
	 */
	private const array PAGE_MILESTONES = array( 10000, 25000, 50000, 100000 );

	/**
	 * Numbers of original languages read that count.
	 *
	 * @var int[]
	 */
	private const array LANGUAGE_MILESTONES = array( 5, 10, 20, 30 );

	/**
	 * Numbers of books read of a form that count, such as 50 novels.
	 *
	 * @var int[]
	 */
	private const array FORM_MILESTONES = array( 10, 25, 50, 100, 250, 500 );

	/**
	 * Number of forms read the same month that makes a jack of all trades.
	 *
	 * @var int
	 */
	private const int JACK_OF_ALL_TRADES_FORMS = 4;

	/**
	 * Number of forms left up to which the tour of the library is close.
	 *
	 * @var int
	 */
	private const int CLOSE_FORMS = 3;

	/**
	 * Number of months in a row with a reading from which the longest run counts.
	 *
	 * @var int
	 */
	private const int STREAK_MONTHS = 6;

	/**
	 * Number of books read translated by the same translator that counts.
	 *
	 * @var int
	 */
	private const int TRANSLATOR_BOOKS = 10;

	/**
	 * Years between the first publication and the reading from which a work is a centenarian.
	 *
	 * @var int
	 */
	private const int CENTENARIAN_YEARS = 100;

	/**
	 * Number of decades of the 20th century read from which the tour is close.
	 *
	 * @var int
	 */
	private const int CLOSE_DECADES = 7;

	/**
	 * Number of books to read from which the pile is a tsundoku.
	 *
	 * @var int
	 */
	private const int TSUNDOKU_BOOKS = 500;

	/**
	 * Number of books read the same day that makes a marathon.
	 *
	 * @var int
	 */
	private const int MARATHON_BOOKS = 3;

	/**
	 * Number of days in a row with a book finished each day that counts.
	 *
	 * @var int
	 */
	private const int SEVEN_DAYS_A_WEEK_DAYS = 7;

	/**
	 * Numbers of pages of the odd books: an error, the temperature at which paper burns, the answer.
	 *
	 * @var array<string,int>
	 */
	private const array ODD_PAGES = array(
		'page-404'          => 404,
		'fahrenheit-451'    => 451,
		'ultimate-question' => 42,
	);

	/**
	 * Numbers of pages under which a book is short and over which it is long, read the same month for the splits.
	 *
	 * @var array{short:int,long:int}
	 */
	private const array SPLITS_PAGES = array(
		'short' => 50,
		'long'  => 800,
	);

	/**
	 * Height of the Everest in metres, as pages read within a year.
	 *
	 * @var int
	 */
	private const int EVEREST_PAGES = 8848;

	/**
	 * Number of original languages read the same month that makes a polyglot.
	 *
	 * @var int
	 */
	private const int POLYGLOT_LANGUAGES = 5;

	/**
	 * Number of original languages read the same week, as on the Rosetta Stone.
	 *
	 * @var int
	 */
	private const int ROSETTA_STONE_LANGUAGES = 3;

	/**
	 * Number of centuries of works read the same month for the time machine.
	 *
	 * @var int
	 */
	private const int TIME_MACHINE_CENTURIES = 3;

	/**
	 * Number of pages from which a book is a brick.
	 *
	 * @var int
	 */
	private const int BRICK_PAGES = 1000;

	/**
	 * Rating, out of 10, from which a book not kept is one that got away.
	 *
	 * @var int
	 */
	private const int ONE_THAT_GOT_AWAY_RATING = 9;

	/**
	 * Number of characters from which a title is a mouthful.
	 *
	 * @var int
	 */
	private const int MOUTHFUL_CHARACTERS = 60;

	/**
	 * Achievements earned, latest first and undated last, and those close to be earned, with what is done
	 * and the total, books or pages, closest first; kept until a book changes, as they take every book.
	 *
	 * @return Achievements_List
	 */
	public function get_achievements(): array {
		return Cache::remember( 'achievements', fn() => $this->compute_achievements() );
	}

	/**
	 * Achievements, from every book: see get_achievements().
	 *
	 * @return Achievements_List
	 */
	private function compute_achievements(): array {
		$books    = self::get_dated_books();
		$reads    = self::get_reads( $books );
		$readings = self::get_readings( $books );

		[ $completions, $close ]             = $this->get_completions( $books );
		[ $read, $close_read ]               = $this->get_read_milestones( $reads );
		[ $pages, $close_pages ]             = $this->get_page_milestones( $readings );
		[ $decades, $close_decades ]         = $this->get_decades( $reads );
		[ $translators, $close_translators ] = $this->get_translators( $reads );
		[ $languages, $close_languages ]     = $this->get_language_milestones( $reads );
		[ $forms, $close_forms ]             = $this->get_form_milestones( $reads );
		[ $tour, $close_tour ]               = $this->get_library_tour( $books, $reads );

		$earned = array_merge(
			$completions,
			$this->get_collection_steps( $books ),
			$read,
			$pages,
			$this->get_records( $readings ),
			$this->get_streak( $readings ),
			$this->get_rereads( $readings ),
			$this->get_discoveries( $books, $reads ),
			$decades,
			$forms,
			$this->get_jack_of_all_trades( $readings ),
			$tour,
			$translators,
			$languages,
			$this->get_language_feats( $reads, $readings ),
			$this->get_easter_eggs( $books, $reads, $readings )
		);

		usort( $earned, fn( array $a, array $b ) => array( '' === $a['date'], $b['date'] ) <=> array( '' === $b['date'], $a['date'] ) );

		$close = array_merge( $close, $close_read, $close_pages, $close_decades, $close_forms, $close_tour, $close_translators, $close_languages );

		// Fewest left first, then the furthest along.
		usort( $close, fn( array $a, array $b ) => array( $a['total'] - $a['done'], $b['done'] / $b['total'], $a['subject'] ) <=> array( $b['total'] - $b['done'], $a['done'] / $a['total'], $b['subject'] ) );

		return array(
			'earned' => $earned,
			'close'  => $close,
		);
	}

	/**
	 * Every book, dated by its first reading, by its day: the achievements count days, such as a Friday the 13th.
	 *
	 * @return array<int,Book>
	 */
	private static function get_dated_books(): array {
		return array_map(
			function ( Book $book ): Book {
				$read_dates = array_map( fn( string $read_date ) => substr( $read_date, 0, 10 ), $book->read_dates );

				return $book->with( read_dates: $read_dates, read_date: $read_dates[0] ?? '' );
			},
			Books::get_books()
		);
	}

	/**
	 * An achievement.
	 *
	 * @param Achievement_Kind $kind    Kind.
	 * @param string           $title   Title, e.g. "Complete author".
	 * @param string           $subject What it is about: an author, a series, a book… or empty.
	 * @param string           $detail  Detail, e.g. "9 books".
	 * @param Target|null      $target  What its subject leads to, from build_book_target(), build_search_target() or build_term_target(), or null.
	 * @param string           $date    Date earned, Y-m-d, or empty when unknown or not dated.
	 * @return Achievement
	 */
	private static function build_achievement( Achievement_Kind $kind, string $title, string $subject, string $detail, ?array $target, string $date ): array {
		return array(
			'kind'       => $kind->value,
			'title'      => $title,
			'subject'    => $subject,
			'detail'     => $detail,
			'target'     => $target,
			'date'       => $date,
			'easter_egg' => Achievement_Family::Easter_Eggs === $kind->family(),
		);
	}

	/**
	 * Key of a name, such as a translator or a title, the same whatever its case.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	private static function get_name_key( string $name ): string {
		return mb_strtolower( trim( $name ) );
	}

	/**
	 * The books read, dated by their first reading, those without read date first, then by read date.
	 *
	 * @param array<int,Book> $books All the books.
	 * @return array<int,Book>
	 */
	private static function get_reads( array $books ): array {
		$reads = array_values( array_filter( $books, fn( Book $book ) => $book->read ) );

		usort( $reads, fn( Book $a, Book $b ) => array( $a->read_date, $a->id ) <=> array( $b->read_date, $b->id ) );

		return $reads;
	}

	/**
	 * The readings of the books read, one per read date, those whose date is unknown first, then by date.
	 *
	 * @param array<int,Book> $books All the books.
	 * @return array<int,Book> Books read, each one with the date of a reading as read date.
	 */
	private static function get_readings( array $books ): array {
		$readings = array();

		foreach ( self::get_reads( $books ) as $book ) {
			foreach ( $book->read_dates as $read_date ) {
				$readings[] = $book->with( read_date: $read_date );
			}
		}

		usort( $readings, fn( Book $a, Book $b ) => array( $a->read_date, $a->id ) <=> array( $b->read_date, $b->id ) );

		return $readings;
	}

	/**
	 * The latest read date of books, or empty when none has one.
	 *
	 * @param Book[] $books Books.
	 * @return string
	 */
	private static function get_latest_read_date( array $books ): string {
		return (string) max( array_merge( array( '' ), array_column( $books, 'read_date' ) ) );
	}

	/**
	 * A book as the target of an achievement.
	 *
	 * @param int $book_id Book ID.
	 * @return Book_Target
	 */
	private static function build_book_target( int $book_id ): array {
		return array(
			'type' => 'book',
			'id'   => $book_id,
		);
	}

	/**
	 * The books a search finds as the target of an achievement, e.g. a series or a translator with a keyword.
	 *
	 * @param string          $search Search.
	 * @param array<int,Book> $books  Books found.
	 * @return Search_Target
	 */
	private static function build_search_target( string $search, array $books ): array {
		return array(
			'type'     => 'search',
			'search'   => $search,
			'not_kept' => self::is_none_kept( $books ),
		);
	}

	/**
	 * The books of an author, a series or a collection as the target of an achievement.
	 *
	 * @param string          $type    "author", "series" or "collection".
	 * @param int             $term_id Term ID.
	 * @param array<int,Book> $books   Books of the term.
	 * @return Term_Target
	 *
	 * @phpstan-param Completion_Kind $type
	 */
	private static function build_term_target( string $type, int $term_id, array $books ): array {
		return array(
			'type'     => $type,
			'id'       => $term_id,
			'not_kept' => self::is_none_kept( $books ),
		);
	}

	/**
	 * Whether none of the books is kept: the books list leaves them out of its "All" view.
	 *
	 * @param array<int,Book> $books Books.
	 * @return bool
	 */
	private static function is_none_kept( array $books ): bool {
		return $books && array_all( $books, fn( Book $book ) => Status::Not_Kept === $book->status );
	}

	/**
	 * Series and their cycles, authors and collections read in full, one achievement for each kind, and those with few books left.
	 *
	 * @param array<int,Book> $books All the books.
	 * @return array{0:list<Achievement>,1:list<Close_Achievement>} Earned, then close.
	 */
	private function get_completions( array $books ): array {
		$earned = array();
		$close  = array();

		// Each completion names its series, authors or collections, and the ones they are part of: the terms in one query.
		_prime_term_caches( array_unique( array_merge( ...array_column( $books, 'series_ids' ), ...array_column( $books, 'author_ids' ), ...array_column( $books, 'collection_ids' ) ) ) );

		foreach ( self::get_completion_groups( $books ) as $kinds ) {
			$completion = self::get_group_completion( $kinds );

			if ( ! isset( $completion['total'] ) ) {
				$earned[ $completion['kind'] ][] = $completion;
			} elseif ( $completion['total'] - $completion['done'] <= self::CLOSE_BOOKS ) {
				$close[] = $completion;
			}
		}

		$level_titles = array(
			/* translators: %s: number of series read in full. */
			'series'     => fn( int $count ) => sprintf( _n( '%s series finished', '%s series finished', $count, 'library' ), number_format_i18n( $count ) ),
			/* translators: %s: number of authors read in full. */
			'author'     => fn( int $count ) => sprintf( _n( '%s complete author', '%s complete authors', $count, 'library' ), number_format_i18n( $count ) ),
			/* translators: %s: number of collections read in full. */
			'collection' => fn( int $count ) => sprintf( _n( '%s collection finished', '%s collections finished', $count, 'library' ), number_format_i18n( $count ) ),
		);
		$levels       = array();

		foreach ( $earned as $kind => $kind_earned ) {
			$levels = array_merge( $levels, self::get_level_achievement( $kind_earned, $level_titles[ $kind ] ) );
		}

		return array( $levels, $close );
	}

	/**
	 * The completion the books of an author, a series or a cycle, or a collection count for, however many are left; none
	 * when they are too few, or for a publisher.
	 *
	 * @param string $kind    "author", "series" or "collection".
	 * @param int    $term_id Term ID: an author under the name they are known by.
	 * @return Achievement|Close_Achievement|null
	 */
	public function get_completion( string $kind, int $term_id ): ?array {
		foreach ( self::get_completion_groups( self::get_dated_books() ) as $kinds ) {
			if ( isset( $kinds[ $kind ][ $term_id ] ) ) {
				return self::get_group_completion( $kinds );
			}
		}

		return null;
	}

	/**
	 * Authors whose work the book is part of: none for a collective book, which is only found through its authors.
	 *
	 * @param Book $book Book.
	 * @return int[]
	 */
	private static function get_work_author_ids( Book $book ): array {
		return $book->collective ? array() : $book->author_ids;
	}

	/**
	 * Series and their cycles, authors, collections and the collections they are part of with enough books to be completed,
	 * keyed by their books, then by kind.
	 *
	 * The same books count once: for their series, else for their cycle, else for their authors together, else for their
	 * collection, else for the collection it is part of.
	 *
	 * @param array<int,Book> $books All the books.
	 * @return array<string,Completion_Group>
	 */
	private static function get_completion_groups( array $books ): array {
		$groups = array(
			'series'     => array(),
			'author'     => array(),
			'collection' => array(),
		);

		foreach ( $books as $book ) {
			foreach ( $book->series_ids as $series_id ) {
				$groups['series'][ $series_id ][] = $book;
			}

			foreach ( self::get_work_author_ids( $book ) as $author_id ) {
				$groups['author'][ $author_id ][] = $book;
			}

			foreach ( $book->collection_ids as $collection_id ) {
				$groups['collection'][ $collection_id ][] = $book;
			}
		}

		$completions = array();

		foreach ( $groups as $kind => $kind_groups ) {
			foreach ( $kind_groups as $key => $group ) {
				if ( count( $group ) >= self::COMPLETION_BOOKS[ $kind ] ) {
					$completions[ implode( ',', array_column( $group, 'id' ) ) ][ $kind ][ $key ] = $group;
				}
			}
		}

		return $completions;
	}

	/**
	 * The completion of the same books, for their first kind: earned once they are all read, else close, with the books read and their total.
	 *
	 * Authors with the same books are named together; the completion of an author leads to the first one.
	 *
	 * @param array $kinds Groups of the same books, by kind, then by term ID.
	 * @return Achievement|Close_Achievement
	 *
	 * @phpstan-param Completion_Group $kinds
	 */
	private static function get_group_completion( array $kinds ): array {
		$titles = array(
			'author'     => __( 'Complete author', 'library' ),
			'series'     => __( 'Series finished', 'library' ),
			'collection' => __( 'Collection finished', 'library' ),
		);
		$kind   = array_key_first( $kinds );
		$key    = array_key_first( $kinds[ $kind ] );
		$group  = $kinds[ $kind ][ $key ];
		$total  = count( $group );
		$read   = array_filter( $group, fn( Book $book ) => $book->read );
		$left   = $total - count( $read );

		$taxonomies = array(
			'author'     => 'library_person',
			'series'     => 'library_series',
			'collection' => 'library_publisher',
		);
		$names      = array(
			'author'     => fn( WP_Term $term ) => $term->name,
			'series'     => array( Taxonomies::class, 'get_series_path' ),
			'collection' => array( Taxonomies::class, 'get_collection_name' ),
		);
		// A series or a collection is named alone, the narrowest, without the cycles or collections it is part of.
		$term_ids = 'author' === $kind ? array_keys( $kinds[ $kind ] ) : array( $key );
		$terms    = array_filter( array_map( fn( int $term_id ) => get_term( $term_id, $taxonomies[ $kind ] ), $term_ids ), fn( $term ) => $term instanceof WP_Term );
		$subject  = wp_sprintf( '%l', array_map( $names[ $kind ], $terms ) );
		$target   = self::build_term_target( $kind, (int) $key, $group );

		if ( 0 === $left ) {
			return self::build_achievement( Achievement_Kind::from( $kind ), $titles[ $kind ], $subject, Books::format_books( $total ), $target, self::get_latest_read_date( $read ) );
		}

		$next = array_find( $group, fn( Book $book ) => ! $book->read );

		return self::build_achievement(
			Achievement_Kind::from( $kind ),
			$titles[ $kind ],
			$subject,
			sprintf(
				/* translators: 1: number of books left, 2: title of a book left. */
				_n( '%1$s book left: %2$s', '%1$s books left, such as %2$s', $left, 'library' ),
				number_format_i18n( $left ),
				$next?->full_title
			),
			$target,
			''
		) + array(
			'done'  => count( $read ),
			'total' => $total,
		);
	}

	/**
	 * One achievement for those of a kind earned by several subjects, at the level of their number: the latest
	 * subject to earn it, with its detail and link, dated by the reading that did.
	 *
	 * @param array                $earned Achievements of a kind, one per subject.
	 * @param callable(int):string $title  Title from the number of subjects, e.g. "38 complete authors".
	 * @return list<Achievement> The achievement, or none.
	 *
	 * @phpstan-param list<Achievement> $earned
	 */
	private static function get_level_achievement( array $earned, callable $title ): array {
		if ( ! $earned ) {
			return array();
		}

		usort( $earned, fn( array $a, array $b ) => strcmp( $a['date'], $b['date'] ) );

		return array( array( 'title' => $title( count( $earned ) ) ) + end( $earned ) );
	}

	/**
	 * The big collections read by half or more, not in full, as one achievement; each one dated by the reading that reached half.
	 *
	 * @param array<int,Book> $books All the books.
	 * @return list<Achievement>
	 */
	private function get_collection_steps( array $books ): array {
		$collections = array();
		$earned      = array();

		foreach ( $books as $book ) {
			foreach ( $book->collection_ids as $collection_id ) {
				$collections[ $collection_id ][] = $book;
			}
		}

		// The same books count once, for the narrowest collection, as in a completion.
		$book_ids    = array_map( fn( array $group ) => implode( ',', array_column( $group, 'id' ) ), $collections );
		$collections = array_intersect_key( $collections, array_unique( $book_ids ) );

		foreach ( $collections as $collection_id => $group ) {
			$total = count( $group );
			$reads = self::get_reads( $group );

			$half = (int) ceil( $total / 2 );

			if ( $total < self::COLLECTION_STEP_BOOKS || count( $reads ) === $total || count( $reads ) < $half ) {
				continue;
			}

			$term     = get_term( $collection_id, 'library_publisher' );
			$earned[] = self::build_achievement(
				Achievement_Kind::Collection_Step,
				'',
				$term instanceof WP_Term ? Taxonomies::get_collection_name( $term ) : '',
				/* translators: 1: number of books read, 2: number of books. */
				sprintf( __( '%1$s read of %2$s', 'library' ), number_format_i18n( count( $reads ) ), number_format_i18n( $total ) ),
				self::build_term_target( 'collection', $collection_id, $group ),
				$reads[ $half - 1 ]->read_date
			);
		}

		return self::get_level_achievement(
			$earned,
			/* translators: %s: number of collections read by half or more. */
			fn( int $count ) => sprintf( _n( '%s collection read by half or more', '%s collections read by half or more', $count, 'library' ), number_format_i18n( $count ) )
		);
	}

	/**
	 * The highest number of books read reached, dated by the reading that reached it, and the next one
	 * with its progress; reads without date come first.
	 *
	 * @param array<int,Book> $reads Books read, in reading order.
	 * @return array{0:list<Achievement>,1:list<Close_Achievement>} Earned, then next.
	 */
	private function get_read_milestones( array $reads ): array {
		/* translators: %s: number of books read. */
		$title  = fn( int $milestone ) => sprintf( __( '%s books read', 'library' ), number_format_i18n( $milestone ) );
		$earned = array();
		$next   = array();

		foreach ( self::READ_MILESTONES as $milestone ) {
			if ( count( $reads ) < $milestone ) {
				$next[] = self::build_achievement( Achievement_Kind::Read, $title( $milestone ), '', '', null, '' ) + array(
					'done'  => count( $reads ),
					'total' => $milestone,
				);
				break;
			}

			$earned = array(
				self::build_achievement(
					Achievement_Kind::Read,
					$title( $milestone ),
					'',
					$reads[ $milestone - 1 ]->full_title,
					null,
					$reads[ $milestone - 1 ]->read_date
				),
			);
		}

		return array( $earned, $next );
	}

	/**
	 * The highest number of pages read reached, over the books read with their number of pages, and the next
	 * one with its progress, once a book read has its number of pages.
	 *
	 * @param array<int,Book> $readings Readings, in reading order.
	 * @return array{0:list<Achievement>,1:list<Close_Achievement>} Earned, then next.
	 */
	private function get_page_milestones( array $readings ): array {
		/* translators: %s: number of pages read. */
		$title      = fn( int $milestone ) => sprintf( __( '%s pages read', 'library' ), number_format_i18n( $milestone ) );
		$earned     = array();
		$pages      = 0;
		$milestones = self::PAGE_MILESTONES;

		foreach ( $readings as $book ) {
			$pages += (int) $book->pages;

			while ( $milestones && $pages >= $milestones[0] ) {
				$earned = array(
					self::build_achievement(
						Achievement_Kind::Pages,
						$title( array_shift( $milestones ) ),
						'',
						$book->full_title,
						null,
						$book->read_date
					),
				);
			}
		}

		// Like the Reading widget, nothing to show until a book read has its number of pages.
		if ( ! $milestones || ! $pages ) {
			return array( $earned, array() );
		}

		$next = self::build_achievement( Achievement_Kind::Pages, $title( $milestones[0] ), '', '', null, '' ) + array(
			'done'  => $pages,
			'total' => $milestones[0],
		);

		return array( $earned, array( $next ) );
	}

	/**
	 * The year and the month with the most books read, dated by the reading that beat the former record.
	 *
	 * @param array<int,Book> $readings Readings, in reading order.
	 * @return list<Achievement>
	 */
	private function get_records( array $readings ): array {
		$dated   = array_values( array_filter( $readings, fn( Book $book ) => '' !== $book->read_date ) );
		$periods = array(
			'year'  => array(
				'kind'   => Achievement_Kind::Record_Year,
				'length' => 4,
				'title'  => __( 'Record year', 'library' ),
				'format' => 'Y',
			),
			'month' => array(
				'kind'   => Achievement_Kind::Record_Month,
				'length' => 7,
				'title'  => __( 'Record month', 'library' ),
				'format' => 'F Y',
			),
		);
		$earned  = array();

		foreach ( $periods as $kind => $period ) {
			$by_period = array();

			foreach ( $dated as $book ) {
				$by_period[ substr( $book->read_date, 0, $period['length'] ) ][] = $book;
			}

			if ( count( $by_period ) < 2 ) {
				continue;
			}

			$counts = array_map( 'count', $by_period );
			$best   = max( $counts );
			$record = (string) array_search( $best, $counts, true );
			$before = array_filter( $counts, fn( string $key ) => $key < $record, ARRAY_FILTER_USE_KEY );
			$former = $before ? max( $before ) : 0;

			$earned[] = self::build_achievement(
				$period['kind'],
				$period['title'],
				date_i18n( $period['format'], strtotime( str_pad( $record, 10, '-01', STR_PAD_RIGHT ) ) ),
				Books::format_books( $best ),
				null,
				$by_period[ $record ][ $former ]->read_date
			);
		}

		return $earned;
	}

	/**
	 * The longest run of months in a row with a reading.
	 *
	 * @param array<int,Book> $readings Readings, in reading order.
	 * @return list<Achievement>
	 */
	private function get_streak( array $readings ): array {
		$months = array();

		foreach ( $readings as $book ) {
			if ( '' !== $book->read_date ) {
				$months[ substr( $book->read_date, 0, 7 ) ] = $book->read_date;
			}
		}

		$longest = array();
		$run     = array();

		foreach ( $months as $month => $date ) {
			$previous = $run ? gmdate( 'Y-m', (int) strtotime( array_key_last( $run ) . '-01 +1 month' ) ) : '';
			$run      = $previous === $month ? $run + array( $month => $date ) : array( $month => $date );

			if ( count( $run ) > count( $longest ) ) {
				$longest = $run;
			}
		}

		if ( count( $longest ) < self::STREAK_MONTHS ) {
			return array();
		}

		return array(
			self::build_achievement(
				Achievement_Kind::Streak,
				/* translators: %s: number of months. */
				sprintf( __( '%s months in a row', 'library' ), number_format_i18n( count( $longest ) ) ),
				'',
				sprintf(
					/* translators: 1: first month, 2: last month, e.g. "January 2024 to June 2025". */
					__( '%1$s to %2$s', 'library' ),
					date_i18n( 'F Y', strtotime( array_key_first( $longest ) . '-01' ) ),
					date_i18n( 'F Y', strtotime( array_key_last( $longest ) . '-01' ) )
				),
				null,
				end( $longest )
			),
		);
	}

	/**
	 * A book read again, at the level of the most times a book was read again: by the first book to reach it,
	 * dated by the reading that did.
	 *
	 * @param array<int,Book> $readings Readings, in reading order.
	 * @return list<Achievement>
	 */
	private function get_rereads( array $readings ): array {
		$counts = array();
		$level  = 0;
		$found  = null;

		foreach ( $readings as $reading ) {
			$counts[ $reading->id ] = ( $counts[ $reading->id ] ?? 0 ) + 1;

			if ( $counts[ $reading->id ] - 1 > $level ) {
				$level = $counts[ $reading->id ] - 1;
				$found = $reading;
			}
		}

		if ( ! $found ) {
			return array();
		}

		$first_year = substr( $found->read_dates[0], 0, 4 );

		return array(
			self::build_achievement(
				Achievement_Kind::Reread,
				/* translators: %s: most times a book was read again. */
				sprintf( _n( 'Read again %s time', 'Read again %s times', $level, 'library' ), number_format_i18n( $level ) ),
				$found->full_title,
				/* translators: %s: year of the first reading of the book. */
				'' !== $first_year ? sprintf( __( 'First read in %s', 'library' ), $first_year ) : '',
				self::build_book_target( $found->id ),
				$found->read_date
			),
		);
	}

	/**
	 * Year a work first came out.
	 *
	 * @param Book $book Book.
	 * @return int|null
	 */
	private static function get_first_year( Book $book ): ?int {
		$date = Books::get_date( $book );

		return '' !== $date ? (int) substr( $date, 0, 4 ) : null;
	}

	/**
	 * The latest of books that earned an achievement, with how many did.
	 *
	 * @param Achievement_Kind $kind        Kind.
	 * @param string           $title       Title.
	 * @param string           $description What the book did, e.g. "Read on 29 February".
	 * @param array<int,Book>  $books       Books or readings that earned it, in reading order.
	 * @return list<Achievement> The achievement, or none.
	 */
	private static function get_latest_achievement( Achievement_Kind $kind, string $title, string $description, array $books ): array {
		if ( ! $books ) {
			return array();
		}

		// A book counts once, by its latest reading: a book read on two 29 Februaries is one book.
		$latest = array();

		foreach ( $books as $book ) {
			unset( $latest[ $book->id ] );
			$latest[ $book->id ] = $book;
		}

		$books = array_values( $latest );
		$book  = end( $books );
		/* translators: 1: what the books did, e.g. "Read on 29 February", 2: number of books, e.g. "3 books". */
		$detail = count( $books ) > 1 ? sprintf( __( '%1$s (%2$s)', 'library' ), $description, Books::format_books( count( $books ) ) ) : $description;

		return array(
			self::build_achievement(
				$kind,
				$title,
				$book->full_title,
				$detail,
				self::build_book_target( $book->id ),
				$book->read_date
			),
		);
	}

	/**
	 * Books read the year they came out, a work read a century after, and the oldest book of the pile read.
	 *
	 * @param array<int,Book> $books All the books.
	 * @param array<int,Book> $reads Books read, in reading order.
	 * @return list<Achievement>
	 */
	private function get_discoveries( array $books, array $reads ): array {
		$dated = array_values( array_filter( $reads, fn( Book $book ) => '' !== $book->read_date ) );

		$hot_off_the_press = array_filter( $dated, fn( Book $book ) => self::get_first_year( $book ) === (int) substr( $book->read_date, 0, 4 ) );
		$centenarians      = array_filter(
			$dated,
			fn( Book $book ) => null !== self::get_first_year( $book ) && (int) substr( $book->read_date, 0, 4 ) - self::get_first_year( $book ) >= self::CENTENARIAN_YEARS
		);

		return array_merge(
			self::get_latest_achievement( Achievement_Kind::Hot_Off_The_Press, __( 'Hot off the press', 'library' ), __( 'Read the year it came out', 'library' ), array_values( $hot_off_the_press ) ),
			self::get_latest_achievement( Achievement_Kind::Centenarian, __( 'Centenarian', 'library' ), __( 'Read a century or more after it first came out', 'library' ), array_values( $centenarians ) ),
			self::get_latest_achievement( Achievement_Kind::Archaeologist, __( 'Archaeologist', 'library' ), __( 'Read while the oldest book of the pile', 'library' ), $this->get_oldest_of_the_pile_reads( $books, $dated ) )
		);
	}

	/**
	 * Books read while no book added before them was left to read.
	 *
	 * A book read without date counts as read before any other.
	 *
	 * @param array<int,Book> $books All the books.
	 * @param array<int,Book> $dated Books read with their date, in reading order.
	 * @return array<int,Book>
	 */
	private function get_oldest_of_the_pile_reads( array $books, array $dated ): array {
		$by_added = array_values( $books );

		usort( $by_added, fn( Book $a, Book $b ) => strcmp( $a->added, $b->added ) );

		// The latest date by which each book added so far was read; a book on the pile is never read.
		$read_by = array();
		$latest  = '';
		$added   = '';
		$pending = '';

		foreach ( $by_added as $book ) {
			if ( $book->added !== $added ) {
				$latest = max( $latest, $pending );
				$added  = $book->added;
			}

			$read_by[ $book->id ] = $latest;

			if ( $book->read || $book->is_on_the_pile() ) {
				$pending = max( $pending, $book->read ? $book->read_date : '9999-12-31' );
			}
		}

		return array_values( array_filter( $dated, fn( Book $book ) => $read_by[ $book->id ] <= $book->read_date ) );
	}

	/**
	 * A book read that came out in each decade of the 20th century.
	 *
	 * @param array<int,Book> $reads Books read, in reading order.
	 * @return array{0:list<Achievement>,1:list<Close_Achievement>} Earned, then close.
	 */
	private function get_decades( array $reads ): array {
		$decades = array();

		foreach ( $reads as $book ) {
			$year = self::get_first_year( $book ) ?? 0;

			if ( $year >= 1900 && $year < 2000 && ! isset( $decades[ intdiv( $year, 10 ) ] ) ) {
				$decades[ intdiv( $year, 10 ) ] = $book;
			}
		}

		$title = __( 'Tour of the 20th century', 'library' );
		/* translators: %s: number of decades of the 20th century with a book read. */
		$detail = sprintf( __( '%s decades of 10', 'library' ), number_format_i18n( count( $decades ) ) );

		if ( 10 === count( $decades ) ) {
			return array( array( self::build_achievement( Achievement_Kind::Decades, $title, '', $detail, null, self::get_latest_read_date( $decades ) ) ), array() );
		}

		if ( count( $decades ) >= self::CLOSE_DECADES ) {
			$progress = array(
				'done'  => count( $decades ),
				'total' => 10,
			);

			return array( array(), array( self::build_achievement( Achievement_Kind::Decades, $title, '', $detail, null, '' ) + $progress ) );
		}

		return array( array(), array() );
	}

	/**
	 * The highest number of books read of each form reached, such as 50 novels, and the next one of the forms with few
	 * books left to it.
	 *
	 * @param array<int,Book> $reads Books read, in reading order.
	 * @return array{0:list<Achievement>,1:list<Close_Achievement>} Earned, then close.
	 */
	private function get_form_milestones( array $reads ): array {
		/* translators: %s: number of books read. */
		$title   = fn( int $milestone ) => sprintf( __( '%s books read', 'library' ), number_format_i18n( $milestone ) );
		$by_form = array();
		$earned  = array();
		$close   = array();

		foreach ( $reads as $book ) {
			foreach ( $book->forms as $form ) {
				$by_form[ $form ][] = $book;
			}
		}

		foreach ( $by_form as $form => $books ) {
			$target  = self::build_search_target( Query::build_keyword_search( 'form', $form ) . ' read:yes', $books );
			$reached = array_filter( self::FORM_MILESTONES, fn( int $milestone ) => count( $books ) >= $milestone );
			$next    = current( array_diff( self::FORM_MILESTONES, $reached ) );

			if ( $reached ) {
				$book     = $books[ max( $reached ) - 1 ];
				$earned[] = self::build_achievement( Achievement_Kind::Form, $title( max( $reached ) ), $form, $book->full_title, $target, $book->read_date );
			}

			if ( false !== $next && $next - count( $books ) <= self::CLOSE_BOOKS ) {
				$close[] = self::build_achievement( Achievement_Kind::Form, $title( $next ), $form, '', $target, '' ) + array(
					'done'  => count( $books ),
					'total' => $next,
				);
			}
		}

		return array( $earned, $close );
	}

	/**
	 * The latest month in which books of several forms were read, such as a novel, a comic, an essay and a magazine.
	 *
	 * @param array<int,Book> $readings Readings, in reading order.
	 * @return list<Achievement>
	 */
	private function get_jack_of_all_trades( array $readings ): array {
		$dated = array_values( array_filter( $readings, fn( Book $book ) => '' !== $book->read_date ) );
		$month = self::get_latest_variety( $dated, fn( Book $book ) => substr( $book->read_date, 0, 7 ), fn( Book $book ) => $book->forms, self::JACK_OF_ALL_TRADES_FORMS );

		if ( ! $month ) {
			return array();
		}

		return array(
			self::build_achievement(
				Achievement_Kind::Jack_Of_All_Trades,
				__( 'Jack of all trades', 'library' ),
				date_i18n( 'F Y', strtotime( $month['period'] . '-01' ) ),
				/* translators: %s: number of forms, such as novel or comic. */
				sprintf( __( '%s forms in a month', 'library' ), number_format_i18n( self::JACK_OF_ALL_TRADES_FORMS ) ),
				null,
				$month['book']->read_date
			),
		);
	}

	/**
	 * A book read of each form of the books at hand, kept or not, dated by the reading of the last form; with few forms
	 * left, those to read. The forms of the books to buy are not in the library yet.
	 *
	 * @param array<int,Book> $books All the books.
	 * @param array<int,Book> $reads Books read, in reading order.
	 * @return array{0:list<Achievement>,1:list<Close_Achievement>} Earned, then close.
	 */
	private function get_library_tour( array $books, array $reads ): array {
		$at_hand     = array_filter( $books, fn( Book $book ) => Status::To_Buy !== $book->status );
		$forms       = array_values( array_unique( array_merge( ...array_column( $at_hand, 'forms' ) ) ) );
		$first_reads = array();

		foreach ( $reads as $book ) {
			foreach ( $book->forms as $form ) {
				$first_reads[ $form ] ??= $book;
			}
		}

		$left  = array_values( array_diff( $forms, array_keys( $first_reads ) ) );
		$title = __( 'Tour of the library', 'library' );

		// A tour needs forms to go round.
		if ( count( $forms ) < 2 ) {
			return array( array(), array() );
		}

		if ( ! $left ) {
			/* translators: %s: number of forms, such as novel or comic. */
			$detail = sprintf( _n( '%s form read', '%s forms read', count( $forms ), 'library' ), number_format_i18n( count( $forms ) ) );

			return array( array( self::build_achievement( Achievement_Kind::Library_Tour, $title, '', $detail, null, self::get_latest_read_date( array_intersect_key( $first_reads, array_flip( $forms ) ) ) ) ), array() );
		}

		if ( count( $left ) > self::CLOSE_FORMS ) {
			return array( array(), array() );
		}

		$values = implode( ',', array_map( fn( string $form ) => '"' . $form . '"', $left ) );
		$unread = array_filter( $at_hand, fn( Book $book ) => ! $book->read && array_intersect( $book->forms, $left ) );
		/* translators: %s: forms left to read, such as "Poetry, Drama". */
		$detail   = sprintf( __( 'Still to read: %s', 'library' ), implode( ', ', $left ) );
		$progress = array(
			'done'  => count( $forms ) - count( $left ),
			'total' => count( $forms ),
		);

		return array( array(), array( self::build_achievement( Achievement_Kind::Library_Tour, $title, '', $detail, self::build_search_target( "form:{$values} read:no", array_values( $unread ) ), '' ) + $progress ) );
	}

	/**
	 * Translators with many books read, as one achievement, and those close to it.
	 *
	 * @param array<int,Book> $reads Books read, in reading order.
	 * @return array{0:list<Achievement>,1:list<Close_Achievement>} Earned, then close.
	 */
	private function get_translators( array $reads ): array {
		$translators = array();
		$names       = array();
		$earned      = array();
		$close       = array();

		foreach ( $reads as $book ) {
			foreach ( $book->translators as $translator ) {
				$translators[ self::get_name_key( $translator ) ][] = $book;
				$names[ self::get_name_key( $translator ) ]       ??= $translator;
			}
		}

		foreach ( $translators as $key => $books ) {
			$translator = $names[ $key ];
			$left       = self::TRANSLATOR_BOOKS - count( $books );
			$detail     = Books::format_books( count( $books ) );

			if ( $left <= 0 ) {
				$earned[] = self::build_achievement( Achievement_Kind::Translator, '', $translator, $detail, self::build_search_target( Query::build_keyword_search( 'translator', $translator ), $books ), $books[ self::TRANSLATOR_BOOKS - 1 ]->read_date );
			} elseif ( $left <= self::CLOSE_BOOKS ) {
				$close[] = self::build_achievement( Achievement_Kind::Translator, __( 'Faithful to a translator', 'library' ), $translator, $detail, self::build_search_target( Query::build_keyword_search( 'translator', $translator ), $books ), '' ) + array(
					'done'  => count( $books ),
					'total' => self::TRANSLATOR_BOOKS,
				);
			}
		}

		$level = self::get_level_achievement(
			$earned,
			/* translators: %s: number of translators with many books read. */
			fn( int $count ) => sprintf( _n( 'Faithful to %s translator', 'Faithful to %s translators', $count, 'library' ), number_format_i18n( $count ) )
		);

		return array( $level, $close );
	}

	/**
	 * Numbers of original languages read, dated by the reading that reached them, and the next one when close.
	 *
	 * @param array<int,Book> $reads Books read, in reading order.
	 * @return array{0:list<Achievement>,1:list<Close_Achievement>} Earned, then close.
	 */
	private function get_language_milestones( array $reads ): array {
		$languages  = array();
		$earned     = array();
		$milestones = self::LANGUAGE_MILESTONES;

		foreach ( $reads as $book ) {
			foreach ( $book->languages as $language ) {
				if ( isset( $languages[ $language ] ) ) {
					continue;
				}

				$languages[ $language ] = $book;

				if ( $milestones && count( $languages ) === $milestones[0] ) {
					$earned[] = self::build_achievement(
						Achievement_Kind::Languages,
						/* translators: %s: number of original languages read. */
						sprintf( __( '%s original languages', 'library' ), number_format_i18n( array_shift( $milestones ) ) ),
						'',
						$book->full_title,
						null,
						$book->read_date
					);
				}
			}
		}

		if ( ! $milestones || $milestones[0] - count( $languages ) > self::CLOSE_BOOKS ) {
			return array( $earned, array() );
		}

		$progress = array(
			'done'  => count( $languages ),
			'total' => $milestones[0],
		);

		/* translators: %s: number of original languages read. */
		$title = sprintf( __( '%s original languages', 'library' ), number_format_i18n( $milestones[0] ) );
		/* translators: %s: number of original languages read. */
		$detail = sprintf( _n( '%s language read', '%s languages read', count( $languages ), 'library' ), number_format_i18n( count( $languages ) ) );

		return array( $earned, array( self::build_achievement( Achievement_Kind::Languages, $title, '', $detail, null, '' ) + $progress ) );
	}

	/**
	 * Books read in their original language, a polyglot month, a Rosetta week and translators from several languages.
	 *
	 * The original language is foreign when its code, the slug of the term, is not the language of the admin: "fr" for fr_FR.
	 *
	 * @param array<int,Book> $reads    Books read, in reading order.
	 * @param array<int,Book> $readings Readings, in reading order.
	 * @return list<Achievement>
	 */
	private function get_language_feats( array $reads, array $readings ): array {
		$own_language = strtok( get_user_locale(), '_' );
		$dated        = array_values( array_filter( $readings, fn( Book $book ) => '' !== $book->read_date ) );
		$languages    = fn( Book $book ) => $book->languages;
		$feats        = self::get_latest_achievement(
			Achievement_Kind::Original_Language,
			__( 'In the original', 'library' ),
			__( 'Read in its original language, without translator', 'library' ),
			array_values(
				array_filter(
					$reads,
					fn( Book $book ) => $book->languages && ! $book->translators && ! isset( $book->languages[ $own_language ] )
				)
			)
		);

		$polyglot = self::get_latest_variety( $dated, fn( Book $book ) => substr( $book->read_date, 0, 7 ), $languages, self::POLYGLOT_LANGUAGES );

		if ( $polyglot ) {
			$feats[] = self::build_achievement(
				Achievement_Kind::Polyglot,
				__( 'Polyglot of the month', 'library' ),
				date_i18n( 'F Y', strtotime( $polyglot['period'] . '-01' ) ),
				/* translators: %s: number of original languages. */
				sprintf( __( '%s original languages in a month', 'library' ), number_format_i18n( self::POLYGLOT_LANGUAGES ) ),
				null,
				$polyglot['book']->read_date
			);
		}

		$rosetta_stone = self::get_latest_variety( $dated, fn( Book $book ) => gmdate( 'o-W', (int) strtotime( $book->read_date ) ), $languages, self::ROSETTA_STONE_LANGUAGES );

		if ( $rosetta_stone ) {
			$feats[] = self::build_achievement(
				Achievement_Kind::Rosetta_Stone,
				__( 'Rosetta Stone', 'library' ),
				'',
				/* translators: %s: number of original languages. */
				sprintf( __( '%s original languages in a week', 'library' ), number_format_i18n( self::ROSETTA_STONE_LANGUAGES ) ),
				null,
				$rosetta_stone['book']->read_date
			);
		}

		return array_merge( $feats, $this->get_go_betweens( $reads ) );
	}

	/**
	 * Translators read from several original languages, dated by the reading that brought the second one.
	 *
	 * @param array<int,Book> $reads Books read, in reading order.
	 * @return list<Achievement>
	 */
	private function get_go_betweens( array $reads ): array {
		$from   = array();
		$books  = array();
		$names  = array();
		$earned = array();

		foreach ( $reads as $book ) {
			foreach ( $book->translators as $translator ) {
				$names[ self::get_name_key( $translator ) ] ??= $translator;
				$books[ self::get_name_key( $translator ) ][] = $book;

				foreach ( $book->languages as $language ) {
					$from[ self::get_name_key( $translator ) ][ $language ] ??= $book;
				}
			}
		}

		foreach ( array_filter( $from, fn( array $languages ) => count( $languages ) > 1 ) as $key => $languages ) {
			$translator = $names[ $key ];
			$earned[]   = self::build_achievement(
				Achievement_Kind::Go_Between,
				__( 'Go-between', 'library' ),
				$translator,
				/* translators: %s: original languages, e.g. "Japanese, English". */
				sprintf( __( 'Translated from %s', 'library' ), implode( ', ', array_keys( $languages ) ) ),
				self::build_search_target( Query::build_keyword_search( 'translator', $translator ), $books[ $key ] ),
				array_values( $languages )[1]->read_date
			);
		}

		return $earned;
	}

	/**
	 * The latest period, e.g. a month, in which books read reached a number of different values, e.g. languages,
	 * with the reading that reached it.
	 *
	 * @param array<int,Book>                  $dated  Readings with their date, in reading order.
	 * @param callable(Book):string            $period Period of a reading, e.g. "2026-10".
	 * @param callable(Book):array<int|string> $values Values of a book, e.g. its languages.
	 * @param int                              $count  Number of different values to reach.
	 * @return array{period:string,book:Book}|null
	 */
	private static function get_latest_variety( array $dated, callable $period, callable $values, int $count ): ?array {
		$seen   = array();
		$latest = null;

		foreach ( $dated as $book ) {
			$key = $period( $book );

			foreach ( $values( $book ) as $value ) {
				$seen[ $key ][ $value ] = true;
			}

			if ( count( $seen[ $key ] ?? array() ) >= $count && ( $latest['period'] ?? '' ) !== $key ) {
				$latest = array(
					'period' => $key,
					'book'   => $book,
				);
			}
		}

		return $latest;
	}

	/**
	 * Books read of 404, 451 or 42 pages; the 42nd reading of a year is an answer too.
	 *
	 * @param array<int,Book> $reads Books read, in reading order.
	 * @param array<int,Book> $dated Readings with their date, in reading order.
	 * @return list<Achievement>
	 */
	private function get_odd_pages( array $reads, array $dated ): array {
		$by_pages = fn( int $pages ) => array_values( array_filter( $reads, fn( Book $book ) => $pages === $book->pages ) );
		$by_year  = array();

		foreach ( $dated as $book ) {
			$by_year[ substr( $book->read_date, 0, 4 ) ][] = $book;
		}

		$answers = array_merge( $by_pages( self::ODD_PAGES['ultimate-question'] ), array_column( $by_year, 41 ) );

		usort( $answers, fn( Book $a, Book $b ) => strcmp( $a->read_date, $b->read_date ) );

		return array_merge(
			self::get_latest_achievement(
				Achievement_Kind::Page_404,
				__( 'Error 404', 'library' ),
				/* translators: %s: number of pages. */
				sprintf( __( 'Page not found: a book of %s pages', 'library' ), number_format_i18n( self::ODD_PAGES['page-404'] ) ),
				$by_pages( self::ODD_PAGES['page-404'] )
			),
			self::get_latest_achievement(
				Achievement_Kind::Fahrenheit_451,
				__( 'Fahrenheit 451', 'library' ),
				/* translators: %s: number of pages. */
				sprintf( __( 'A book of %s pages, the temperature at which paper burns', 'library' ), number_format_i18n( self::ODD_PAGES['fahrenheit-451'] ) ),
				$by_pages( self::ODD_PAGES['fahrenheit-451'] )
			),
			self::get_latest_achievement( Achievement_Kind::Ultimate_Question, __( 'The Ultimate Question', 'library' ), __( 'A book of 42 pages, or the 42nd read of a year', 'library' ), $answers )
		);
	}

	/**
	 * The latest month with a short book and a long one read.
	 *
	 * @param array<int,Book> $dated Readings with their date, in reading order.
	 * @return list<Achievement>
	 */
	private function get_splits( array $dated ): array {
		$size   = fn( Book $book ) => match ( true ) {
			null === $book->pages => array(),
			$book->pages < self::SPLITS_PAGES['short'] => array( 'short' ),
			$book->pages > self::SPLITS_PAGES['long'] => array( 'long' ),
			default => array(),
		};
		$splits = self::get_latest_variety( $dated, fn( Book $book ) => substr( $book->read_date, 0, 7 ), $size, 2 );

		if ( ! $splits ) {
			return array();
		}

		return array(
			self::build_achievement(
				Achievement_Kind::Splits,
				__( 'The splits', 'library' ),
				date_i18n( 'F Y', strtotime( $splits['period'] . '-01' ) ),
				sprintf(
					/* translators: 1: number of pages of a short book, 2: number of pages of a long one. */
					__( 'Under %1$s and over %2$s pages in the same month', 'library' ),
					number_format_i18n( self::SPLITS_PAGES['short'] ),
					number_format_i18n( self::SPLITS_PAGES['long'] )
				),
				null,
				$splits['book']->read_date
			),
		);
	}

	/**
	 * The latest reading that brought the pages read over the previous 365 days to the height of the Everest.
	 *
	 * @param array<int,Book> $dated Readings with their date, in reading order.
	 * @return list<Achievement>
	 */
	private function get_everest( array $dated ): array {
		$window = array();
		$pages  = 0;
		$summit = '';

		foreach ( array_filter( $dated, fn( Book $book ) => null !== $book->pages ) as $book ) {
			$window[] = $book;
			$pages   += $book->pages;
			$start    = gmdate( 'Y-m-d', (int) strtotime( $book->read_date . ' -364 days' ) );

			while ( $window[0]->read_date < $start ) {
				$pages -= $window[0]->pages;
				array_shift( $window );
			}

			if ( $pages >= self::EVEREST_PAGES ) {
				$summit = $book->read_date;
			}
		}

		if ( '' === $summit ) {
			return array();
		}

		/* translators: %s: number of pages. */
		$detail = sprintf( __( '%s pages read within a year', 'library' ), number_format_i18n( self::EVEREST_PAGES ) );

		return array( self::build_achievement( Achievement_Kind::Everest, __( 'Everest', 'library' ), '', $detail, null, $summit ) );
	}

	/**
	 * The latest month with works read from several centuries.
	 *
	 * @param array<int,Book> $dated Readings with their date, in reading order.
	 * @return list<Achievement>
	 */
	private function get_time_machine( array $dated ): array {
		$century = fn( Book $book ) => null !== self::get_first_year( $book ) ? array( intdiv( self::get_first_year( $book ), 100 ) ) : array();
		$trip    = self::get_latest_variety( $dated, fn( Book $book ) => substr( $book->read_date, 0, 7 ), $century, self::TIME_MACHINE_CENTURIES );

		if ( ! $trip ) {
			return array();
		}

		return array(
			self::build_achievement(
				Achievement_Kind::Time_Machine,
				__( 'Time machine', 'library' ),
				date_i18n( 'F Y', strtotime( $trip['period'] . '-01' ) ),
				/* translators: %s: number of centuries. */
				sprintf( __( 'Works from %s centuries in the same month', 'library' ), number_format_i18n( self::TIME_MACHINE_CENTURIES ) ),
				null,
				$trip['book']->read_date
			),
		);
	}

	/**
	 * The latest two works that came out the same year, by different authors, read the same day.
	 *
	 * @param array<int,Book> $dated Readings with their date, in reading order.
	 * @return list<Achievement>
	 */
	private function get_twins( array $dated ): array {
		$seen  = array();
		$twins = null;

		foreach ( array_filter( $dated, fn( Book $book ) => null !== self::get_first_year( $book ) && self::get_work_author_ids( $book ) ) as $book ) {
			$key = $book->read_date . self::get_first_year( $book );

			foreach ( $seen[ $key ] ?? array() as $twin ) {
				if ( ! array_intersect( $twin->author_ids, $book->author_ids ) ) {
					$twins = array( $twin, $book );
				}
			}

			$seen[ $key ][] = $book;
		}

		if ( ! $twins ) {
			return array();
		}

		return array(
			self::build_achievement(
				Achievement_Kind::Twins,
				__( 'Twins', 'library' ),
				$twins[1]->full_title,
				sprintf(
					/* translators: 1: title of the other book, 2: year both first came out. */
					__( 'Read the same day as %1$s, both came out in %2$s', 'library' ),
					$twins[0]->full_title,
					self::get_first_year( $twins[1] )
				),
				self::build_book_target( $twins[1]->id ),
				$twins[1]->read_date
			),
		);
	}

	/**
	 * Easter eggs: odd days, odd books and odd habits.
	 *
	 * @param array<int,Book> $books    All the books.
	 * @param array<int,Book> $reads    Books read, in reading order.
	 * @param array<int,Book> $readings Readings, in reading order.
	 * @return list<Achievement>
	 */
	private function get_easter_eggs( array $books, array $reads, array $readings ): array {
		$dated       = array_values( array_filter( $readings, fn( Book $book ) => '' !== $book->read_date ) );
		$dated_reads = array_values( array_filter( $reads, fn( Book $book ) => '' !== $book->read_date ) );
		$on          = fn( string $month_day ) => array_values( array_filter( $dated, fn( Book $book ) => substr( $book->read_date, 5 ) === $month_day ) );
		$to_read     = count( array_filter( $books, fn( Book $book ) => $book->is_on_the_pile() ) );
		$eggs        = array();

		if ( $to_read >= self::TSUNDOKU_BOOKS ) {
			/* translators: %s: number of books to read. */
			$eggs[] = self::build_achievement( Achievement_Kind::Tsundoku, __( 'Tsundoku', 'library' ), '', sprintf( __( '%s books waiting to be read', 'library' ), number_format_i18n( $to_read ) ), null, '' );
		}

		$eggs = array_merge(
			$eggs,
			self::get_latest_achievement( Achievement_Kind::Leap_Day, __( 'Leap day', 'library' ), __( 'Read on 29 February', 'library' ), $on( '02-29' ) ),
			self::get_latest_achievement( Achievement_Kind::Pi_Day, __( 'Pi day', 'library' ), __( 'Read on 14 March', 'library' ), $on( '03-14' ) ),
			self::get_latest_achievement( Achievement_Kind::New_Year, __( 'New Year\'s resolution', 'library' ), __( 'Read on 1 January', 'library' ), $on( '01-01' ) ),
			self::get_latest_achievement( Achievement_Kind::New_Years_Eve, __( 'Last of the year', 'library' ), __( 'Read on 31 December', 'library' ), $on( '12-31' ) ),
			self::get_latest_achievement(
				Achievement_Kind::Friday_13th,
				__( 'Friday the 13th', 'library' ),
				__( 'Read on a Friday the 13th', 'library' ),
				array_values( array_filter( $dated, fn( Book $book ) => '13' === substr( $book->read_date, 8 ) && '5' === gmdate( 'N', (int) strtotime( $book->read_date ) ) ) )
			),
			self::get_latest_achievement(
				Achievement_Kind::Palindrome,
				__( 'Palindrome date', 'library' ),
				__( 'Read on a date that reads the same backwards', 'library' ),
				array_values( array_filter( $dated, fn( Book $book ) => str_replace( '-', '', $book->read_date ) === strrev( str_replace( '-', '', $book->read_date ) ) ) )
			),
			self::get_latest_achievement(
				Achievement_Kind::Love_At_First_Sight,
				__( 'Love at first sight', 'library' ),
				__( 'Added and read the same day', 'library' ),
				array_values( array_filter( $dated, fn( Book $book ) => substr( $book->added, 0, 10 ) === $book->read_date ) )
			),
			self::get_latest_achievement(
				Achievement_Kind::Brick,
				__( 'Brick', 'library' ),
				/* translators: %s: number of pages. */
				sprintf( __( 'A book of %s pages or more', 'library' ), number_format_i18n( self::BRICK_PAGES ) ),
				array_values( array_filter( $reads, fn( Book $book ) => (int) $book->pages >= self::BRICK_PAGES ) )
			),
			self::get_latest_achievement(
				Achievement_Kind::One_That_Got_Away,
				__( 'The one that got away', 'library' ),
				/* translators: %s: rating, out of 10. */
				sprintf( __( 'Not kept, rated %s or more', 'library' ), number_format_i18n( self::ONE_THAT_GOT_AWAY_RATING ) ),
				array_values( array_filter( $reads, fn( Book $book ) => Status::Not_Kept === $book->status && $book->rating >= self::ONE_THAT_GOT_AWAY_RATING ) )
			),
			self::get_latest_achievement(
				Achievement_Kind::Mouthful,
				__( 'Mouthful', 'library' ),
				/* translators: %s: number of characters. */
				sprintf( __( 'A title of %s characters or more', 'library' ), number_format_i18n( self::MOUTHFUL_CHARACTERS ) ),
				array_values( array_filter( $reads, fn( Book $book ) => mb_strlen( $book->title ) >= self::MOUTHFUL_CHARACTERS ) )
			),
			$this->get_odd_pages( $reads, $dated ),
			$this->get_splits( $dated ),
			$this->get_everest( $dated ),
			$this->get_time_machine( $dated ),
			$this->get_twins( $dated ),
			$this->get_marathon( $dated ),
			$this->get_seven_days_a_week( $dated ),
			$this->get_hat_trick( $dated ),
			$this->get_namesakes( $reads ),
			$this->get_back_to_the_start( $dated_reads )
		);

		return $eggs;
	}

	/**
	 * The latest day with many books read.
	 *
	 * @param array<int,Book> $dated Readings with their date, in reading order.
	 * @return list<Achievement>
	 */
	private function get_marathon( array $dated ): array {
		$days = array();

		foreach ( $dated as $book ) {
			$days[ $book->read_date ][] = $book;
		}

		$marathons = array_filter( $days, fn( array $books ) => count( $books ) >= self::MARATHON_BOOKS );

		if ( ! $marathons ) {
			return array();
		}

		$day = (string) array_key_last( $marathons );

		/* translators: %s: number of books read the same day. */
		return array( self::build_achievement( Achievement_Kind::Marathon, __( 'Marathon', 'library' ), '', sprintf( __( '%s books read the same day', 'library' ), number_format_i18n( count( $marathons[ $day ] ) ) ), null, $day ) );
	}

	/**
	 * The longest run of days in a row with a book finished each day, the latest one among the longest.
	 *
	 * @param array<int,Book> $dated Readings with their date, in reading order.
	 * @return list<Achievement>
	 */
	private function get_seven_days_a_week( array $dated ): array {
		$longest = array();
		$run     = array();

		foreach ( array_unique( array_column( $dated, 'read_date' ) ) as $day ) {
			$previous = $run ? gmdate( 'Y-m-d', (int) strtotime( end( $run ) . ' +1 day' ) ) : '';
			$run      = $previous === $day ? array( ...$run, $day ) : array( $day );

			if ( count( $run ) >= count( $longest ) ) {
				$longest = $run;
			}
		}

		if ( count( $longest ) < self::SEVEN_DAYS_A_WEEK_DAYS ) {
			return array();
		}

		return array(
			self::build_achievement(
				Achievement_Kind::Seven_Days_A_Week,
				__( 'Seven days a week', 'library' ),
				'',
				/* translators: %s: number of days. */
				sprintf( __( 'A book finished every day for %s days', 'library' ), number_format_i18n( count( $longest ) ) ),
				null,
				end( $longest )
			),
		);
	}

	/**
	 * Three different books rated 10 read in a row, dated by the third: a book read again in the run neither adds to it
	 * nor breaks it.
	 *
	 * @param array<int,Book> $dated Readings with their date, in reading order.
	 * @return list<Achievement>
	 */
	private function get_hat_trick( array $dated ): array {
		$run   = array();
		$found = null;

		foreach ( $dated as $book ) {
			if ( 10.0 !== $book->rating ) {
				$run = array();
				continue;
			}

			if ( isset( $run[ $book->id ] ) ) {
				continue;
			}

			$run[ $book->id ] = true;

			if ( count( $run ) >= 3 ) {
				$found = $book;
			}
		}

		if ( ! $found ) {
			return array();
		}

		return array( self::build_achievement( Achievement_Kind::Hat_Trick, __( 'Hat-trick', 'library' ), $found->full_title, __( 'Three books rated 10 in a row', 'library' ), self::build_book_target( $found->id ), $found->read_date ) );
	}

	/**
	 * The latest two books read with the same title by different authors, dated by the second one, named after the
	 * first book of that title.
	 *
	 * @param array<int,Book> $reads Books read, in reading order.
	 * @return list<Achievement>
	 */
	private function get_namesakes( array $reads ): array {
		$titles = array();
		$found  = null;

		foreach ( $reads as $book ) {
			$key        = self::get_name_key( $book->title );
			$author_ids = self::get_work_author_ids( $book );

			foreach ( $titles[ $key ] ?? array() as $other ) {
				$other_author_ids = self::get_work_author_ids( $other );

				if ( $author_ids && $other_author_ids && ! array_intersect( $author_ids, $other_author_ids ) ) {
					$found = $book;
					break;
				}
			}

			$titles[ $key ][] = $book;
		}

		if ( ! $found ) {
			return array();
		}

		$books = $titles[ self::get_name_key( $found->title ) ];
		$title = $books[0]->title;

		return array( self::build_achievement( Achievement_Kind::Namesakes, __( 'Namesakes', 'library' ), $title, __( 'The same title, different authors', 'library' ), self::build_search_target( $title, $books ), $found->read_date ) );
	}

	/**
	 * The first volume of a series read after a later one.
	 *
	 * @param array<int,Book> $dated Books read with their date, in reading order.
	 * @return list<Achievement>
	 */
	private function get_back_to_the_start( array $dated ): array {
		$later = array();
		$found = null;

		foreach ( $dated as $book ) {
			if ( null === $book->volume || ! $book->series_id ) {
				continue;
			}

			if ( 1 === $book->volume && isset( $later[ $book->series_id ] ) ) {
				$found = $book;
			} elseif ( $book->volume > 1 ) {
				$later[ $book->series_id ] = true;
			}
		}

		if ( ! $found ) {
			return array();
		}

		return array( self::build_achievement( Achievement_Kind::Back_To_The_Start, __( 'Back to the start', 'library' ), $found->full_title, __( 'Volume 1 read after a later volume', 'library' ), self::build_book_target( $found->id ), $found->read_date ) );
	}
}
