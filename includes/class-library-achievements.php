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

/**
 * Achievements drawn from the books, computed on each view: nothing is stored, so a completion is lost
 * when a book to read joins it, and comes back once it is read.
 *
 * By family: completions (series, authors and collections read in full, collections read by half or
 * more), milestones (books and pages read, record year and month, months in a row, a book read
 * again, whose level is the most times a book was read again), discoveries
 * (hot off the press, a centenarian, the oldest of the pile, the decades of the 20th century), languages
 * and translators, and easter eggs. Books not kept count, since they are read. Completions and translators
 * are one achievement each, whose level is the number of subjects that earned it. An achievement is dated
 * by the reading that earned it, when it has a date. Achievements that count books take their first
 * reading; those about readings over time count a book read again once per reading.
 *
 * @since      2.2.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Achievements {

	/**
	 * Number of books from which reading all those of an author, a series or a collection counts.
	 *
	 * @var array<string,int>
	 */
	private const COMPLETION_BOOKS = array(
		'author'     => 3,
		'series'     => 2,
		'collection' => 3,
	);

	/**
	 * Number of books of a collection from which reading half of them counts.
	 *
	 * @var int
	 */
	private const COLLECTION_STEP_BOOKS = 10;

	/**
	 * Number of books left up to which a completion is close.
	 *
	 * @var int
	 */
	private const CLOSE_BOOKS = 2;

	/**
	 * Numbers of books read that count.
	 *
	 * @var int[]
	 */
	private const READ_MILESTONES = array( 100, 250, 500, 750, 1000, 1500, 2000, 3000 );

	/**
	 * Numbers of pages read that count.
	 *
	 * @var int[]
	 */
	private const PAGE_MILESTONES = array( 10000, 25000, 50000, 100000 );

	/**
	 * Numbers of original languages read that count.
	 *
	 * @var int[]
	 */
	private const LANGUAGE_MILESTONES = array( 5, 10, 20, 30 );

	/**
	 * Number of months in a row with a reading from which the longest run counts.
	 *
	 * @var int
	 */
	private const MONTH_STREAK = 6;

	/**
	 * Number of books read translated by the same translator that counts.
	 *
	 * @var int
	 */
	private const TRANSLATOR_BOOKS = 10;

	/**
	 * Years between the first publication and the reading from which a work is a centenarian.
	 *
	 * @var int
	 */
	private const CENTENARIAN_YEARS = 100;

	/**
	 * Number of decades of the 20th century read from which the tour is close.
	 *
	 * @var int
	 */
	private const CLOSE_DECADES = 7;

	/**
	 * Number of books to read from which the pile is a tsundoku.
	 *
	 * @var int
	 */
	private const TSUNDOKU_BOOKS = 500;

	/**
	 * Number of books read the same day that makes a marathon.
	 *
	 * @var int
	 */
	private const MARATHON_BOOKS = 3;

	/**
	 * Number of days in a row with a book finished each day that counts.
	 *
	 * @var int
	 */
	private const DAILY_STREAK = 7;

	/**
	 * Numbers of pages of the odd books: an error, the temperature at which paper burns, the answer.
	 *
	 * @var array<string,int>
	 */
	private const ODD_PAGES = array(
		'page-404'          => 404,
		'fahrenheit-451'    => 451,
		'ultimate-question' => 42,
	);

	/**
	 * Numbers of pages under which a book is short and over which it is long, read the same month for the splits.
	 *
	 * @var array{short:int,long:int}
	 */
	private const SPLITS_PAGES = array(
		'short' => 50,
		'long'  => 800,
	);

	/**
	 * Height of the Everest in metres, as pages read within a year.
	 *
	 * @var int
	 */
	private const EVEREST_PAGES = 8848;

	/**
	 * Number of original languages read the same month that makes a polyglot.
	 *
	 * @var int
	 */
	private const POLYGLOT_LANGUAGES = 5;

	/**
	 * Number of original languages read the same week, as on the Rosetta Stone.
	 *
	 * @var int
	 */
	private const ROSETTA_LANGUAGES = 3;

	/**
	 * Number of centuries of works read the same month for the time machine.
	 *
	 * @var int
	 */
	private const TIME_MACHINE_CENTURIES = 3;

	/**
	 * Number of pages from which a book is a brick.
	 *
	 * @var int
	 */
	private const BRICK_PAGES = 1000;

	/**
	 * Rating, out of 10, from which a book not kept is one that got away.
	 *
	 * @var int
	 */
	private const LOVED_RATING = 9;

	/**
	 * Number of characters from which a title is a mouthful.
	 *
	 * @var int
	 */
	private const LONG_TITLE = 60;

	/**
	 * Kinds of achievement by family, in the order of the achievements page, with their dashicon.
	 *
	 * @var array<string,array<string,string>>
	 */
	public const KINDS = array(
		'completions' => array(
			'author'          => 'admin-users',
			'series'          => 'book',
			'collection'      => 'category',
			'collection-step' => 'chart-pie',
		),
		'milestones'  => array(
			'read'         => 'awards',
			'pages'        => 'media-document',
			'record-year'  => 'chart-bar',
			'record-month' => 'chart-line',
			'streak'       => 'calendar-alt',
			'reread'       => 'image-rotate',
		),
		'discoveries' => array(
			'hot-off-the-press' => 'clock',
			'centenarian'       => 'backup',
			'archaeologist'     => 'search',
			'decades'           => 'admin-site-alt3',
		),
		'languages'   => array(
			'languages'         => 'admin-site',
			'original-language' => 'format-chat',
			'polyglot'          => 'admin-site-alt',
			'rosetta-stone'     => 'admin-site-alt2',
			'translator'        => 'translation',
			'go-between'        => 'randomize',
		),
		'easter-eggs' => array(
			'tsundoku'            => 'book-alt',
			'leap-day'            => 'calendar',
			'pi-day'              => 'calculator',
			'new-year'            => 'flag',
			'new-years-eve'       => 'beer',
			'friday-13th'         => 'warning',
			'palindrome'          => 'image-flip-horizontal',
			'love-at-first-sight' => 'heart',
			'brick'               => 'database',
			'one-that-got-away'   => 'migrate',
			'mouthful'            => 'editor-quote',
			'page-404'            => 'no-alt',
			'fahrenheit-451'      => 'lightbulb',
			'ultimate-question'   => 'editor-help',
			'splits'              => 'leftright',
			'everest'             => 'chart-area',
			'time-machine'        => 'update',
			'twins'               => 'controls-repeat',
			'marathon'            => 'performance',
			'seven-days-a-week'   => 'schedule',
			'hat-trick'           => 'star-filled',
			'namesakes'           => 'groups',
			'back-to-the-start'   => 'undo',
		),
	);

	/**
	 * Achievements earned, latest first and undated last, and those close to be earned, with what is done
	 * and the total, books or pages, closest first.
	 *
	 * @return array{earned:array<int,array{kind:string,title:string,subject:string,detail:string,target:array|null,date:string,easter_egg:bool}>,close:array<int,array{kind:string,title:string,subject:string,detail:string,target:array|null,date:string,easter_egg:bool,done:int,total:int}>}
	 */
	public function get_achievements(): array {
		// A book is dated by its first reading.
		$books    = array_map( fn( array $book ) => $book + array( 'read_date' => $book['read_dates'][0] ?? '' ), Library_Books::all() );
		$reads    = self::get_reads( $books );
		$readings = self::get_readings( $books );

		[ $completions, $close ]             = $this->get_completions( $books );
		[ $read, $close_read ]               = $this->get_read_milestones( $reads );
		[ $pages, $close_pages ]             = $this->get_page_milestones( $readings );
		[ $tour, $close_tour ]               = $this->get_decade_tour( $reads );
		[ $translators, $close_translators ] = $this->get_translators( $reads );
		[ $languages, $close_languages ]     = $this->get_language_milestones( $reads );

		$earned = array_merge(
			$completions,
			$this->get_collection_steps( $books ),
			$read,
			$pages,
			$this->get_records( $readings ),
			$this->get_month_streak( $readings ),
			$this->get_rereads( $readings ),
			$this->get_discoveries( $books, $reads ),
			$tour,
			$translators,
			$languages,
			$this->get_language_feats( $reads, $readings ),
			$this->get_easter_eggs( $books, $reads, $readings )
		);

		usort( $earned, fn( array $a, array $b ) => array( '' === $a['date'], $b['date'] ) <=> array( '' === $b['date'], $a['date'] ) );

		$close = array_merge( $close, $close_read, $close_pages, $close_tour, $close_translators, $close_languages );

		// Fewest left first, then the furthest along.
		usort( $close, fn( array $a, array $b ) => array( $a['total'] - $a['done'], $b['done'] / $b['total'], $a['subject'] ) <=> array( $b['total'] - $b['done'], $a['done'] / $a['total'], $b['subject'] ) );

		return array(
			'earned' => $earned,
			'close'  => $close,
		);
	}

	/**
	 * An achievement.
	 *
	 * @param string     $kind    Kind, e.g. "author".
	 * @param string     $title   Title, e.g. "Complete author".
	 * @param string     $subject What it is about: an author, a series, a book… or empty.
	 * @param string     $detail  Detail, e.g. "9 books".
	 * @param array|null $target  What its subject leads to, from book_target(), search_target() or term_target(), or null.
	 * @param string     $date    Date earned, Y-m-d, or empty when unknown or not dated.
	 * @return array{kind:string,title:string,subject:string,detail:string,target:array|null,date:string,easter_egg:bool}
	 */
	private static function achievement( string $kind, string $title, string $subject, string $detail, ?array $target, string $date ): array {
		return array(
			'kind'       => $kind,
			'title'      => $title,
			'subject'    => $subject,
			'detail'     => $detail,
			'target'     => $target,
			'date'       => $date,
			'easter_egg' => isset( self::KINDS['easter-eggs'][ $kind ] ),
		);
	}

	/**
	 * Key of a name typed by hand, such as a series or a translator, the same whatever its case.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	private static function key( string $name ): string {
		return mb_strtolower( trim( $name ) );
	}

	/**
	 * The books read, dated by their first reading, those without read date first, then by read date.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array<int,array>
	 */
	private static function get_reads( array $books ): array {
		$reads = array_values( array_filter( $books, fn( array $book ) => $book['read'] ) );

		usort( $reads, fn( array $a, array $b ) => array( $a['read_date'], $a['id'] ) <=> array( $b['read_date'], $b['id'] ) );

		return $reads;
	}

	/**
	 * The readings of the books read, one per read date and one for a book read without date,
	 * those without date first, then by date.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array<int,array> Books read, each one with the date of a reading as read date.
	 */
	private static function get_readings( array $books ): array {
		$readings = array();

		foreach ( self::get_reads( $books ) as $book ) {
			foreach ( $book['read_dates'] ? $book['read_dates'] : array( '' ) as $read_date ) {
				$readings[] = array( 'read_date' => $read_date ) + $book;
			}
		}

		usort( $readings, fn( array $a, array $b ) => array( $a['read_date'], $a['id'] ) <=> array( $b['read_date'], $b['id'] ) );

		return $readings;
	}

	/**
	 * The latest read date of books, or empty when none has one.
	 *
	 * @param array<int,array> $books Books.
	 * @return string
	 */
	private static function last_read_date( array $books ): string {
		return (string) max( array_merge( array( '' ), array_column( $books, 'read_date' ) ) );
	}

	/**
	 * A book as the target of an achievement.
	 *
	 * @param int $book_id Book ID.
	 * @return array{type:string,id:int}
	 */
	private static function book_target( int $book_id ): array {
		return array(
			'type' => 'book',
			'id'   => $book_id,
		);
	}

	/**
	 * The books a search finds as the target of an achievement, e.g. a series or a translator with a keyword.
	 *
	 * @param string           $search Search.
	 * @param array<int,array> $books  Books found.
	 * @return array{type:string,search:string,not_kept:bool}
	 */
	private static function search_target( string $search, array $books ): array {
		return array(
			'type'     => 'search',
			'search'   => $search,
			'not_kept' => self::not_kept( $books ),
		);
	}

	/**
	 * The books of an author or a collection as the target of an achievement.
	 *
	 * @param string           $type    "author" or "collection".
	 * @param int              $term_id Term ID.
	 * @param array<int,array> $books   Books of the term.
	 * @return array{type:string,id:int,not_kept:bool}
	 */
	private static function term_target( string $type, int $term_id, array $books ): array {
		return array(
			'type'     => $type,
			'id'       => $term_id,
			'not_kept' => self::not_kept( $books ),
		);
	}

	/**
	 * Whether none of the books is kept: the books list leaves them out of its "All" view.
	 *
	 * @param array<int,array> $books Books.
	 * @return bool
	 */
	private static function not_kept( array $books ): bool {
		return array( Library_Posts::NOT_KEPT ) === array_unique( array_column( $books, 'status' ) );
	}

	/**
	 * Series, authors and collections read in full, one achievement for each kind, and those with few books left.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array{0:array<int,array>,1:array<int,array>} Earned, then close.
	 */
	private function get_completions( array $books ): array {
		$earned = array();
		$close  = array();

		foreach ( self::get_completion_groups( $books ) as $kinds ) {
			$completion = self::completion( $kinds );

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
			$levels = array_merge( $levels, self::level( $kind_earned, $level_titles[ $kind ] ) );
		}

		return array( $levels, $close );
	}

	/**
	 * The completion the books of an author count for, however many are left; none when they are too few.
	 *
	 * @param int $author_id Term ID of the author, under their real name.
	 * @return array|null
	 */
	public function get_author_completion( int $author_id ): ?array {
		foreach ( self::get_completion_groups( Library_Books::all() ) as $kinds ) {
			if ( isset( $kinds['author'][ $author_id ] ) ) {
				return self::completion( $kinds );
			}
		}

		return null;
	}

	/**
	 * Series, authors and collections with enough books to be completed, keyed by their books, then by kind.
	 *
	 * The same books count once: for their series, else for their authors together, else for their collection.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array<string,array<string,array<int|string,array<int,array>>>>
	 */
	private static function get_completion_groups( array $books ): array {
		$groups = array(
			'series'     => array(),
			'author'     => array(),
			'collection' => array(),
		);

		foreach ( $books as $book ) {
			if ( '' !== $book['series'] ) {
				$groups['series'][ self::key( $book['series'] ) ][] = $book;
			}

			foreach ( $book['author_ids'] as $author_id ) {
				$groups['author'][ $author_id ][] = $book;
			}

			foreach ( $book['collection_ids'] as $collection_id ) {
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
	 * @param array<string,array<int|string,array<int,array>>> $kinds Groups of the same books, by kind, then by key.
	 * @return array
	 */
	private static function completion( array $kinds ): array {
		$titles = array(
			'author'     => __( 'Complete author', 'library' ),
			'series'     => __( 'Series finished', 'library' ),
			'collection' => __( 'Collection finished', 'library' ),
		);
		$kind   = (string) array_key_first( $kinds );
		$key    = array_key_first( $kinds[ $kind ] );
		$group  = $kinds[ $kind ][ $key ];
		$total  = count( $group );
		$read   = array_filter( $group, fn( array $book ) => $book['read'] );
		$left   = $total - count( $read );

		if ( 'series' === $kind ) {
			$subject = $group[0]['series'];
			$target  = self::search_target( Library_WP_Query::keyword_search( 'series', $subject ), $group );
		} else {
			$taxonomy = 'author' === $kind ? 'library-author' : 'library-publisher';
			$terms    = array_map( fn( int $term_id ) => get_term( $term_id, $taxonomy ), array_keys( $kinds[ $kind ] ) );
			$subject  = wp_sprintf( '%l', wp_list_pluck( array_filter( $terms, fn( $term ) => $term instanceof WP_Term ), 'name' ) );
			$target   = self::term_target( $kind, (int) $key, $group );
		}

		if ( 0 === $left ) {
			return self::achievement( $kind, $titles[ $kind ], $subject, Library_Books::books_text( $total ), $target, self::last_read_date( $read ) );
		}

		$next = current( array_filter( $group, fn( array $book ) => ! $book['read'] ) );

		return self::achievement(
			$kind,
			$titles[ $kind ],
			$subject,
			sprintf(
				/* translators: 1: number of books left, 2: title of a book left. */
				_n( '%1$s book left: %2$s', '%1$s books left, such as %2$s', $left, 'library' ),
				number_format_i18n( $left ),
				$next['full_title']
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
	 * @param array<int,array> $earned Achievements of a kind, one per subject.
	 * @param callable         $title  Title from the number of subjects, e.g. "38 complete authors".
	 * @return array<int,array> The achievement, or none.
	 */
	private static function level( array $earned, callable $title ): array {
		if ( ! $earned ) {
			return array();
		}

		usort( $earned, fn( array $a, array $b ) => strcmp( $a['date'], $b['date'] ) );

		return array( array( 'title' => $title( count( $earned ) ) ) + end( $earned ) );
	}

	/**
	 * The big collections read by half or more, not in full, as one achievement; each one dated by the reading that reached half.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array<int,array>
	 */
	private function get_collection_steps( array $books ): array {
		$collections = array();
		$earned      = array();

		foreach ( $books as $book ) {
			foreach ( $book['collection_ids'] as $collection_id ) {
				$collections[ $collection_id ][] = $book;
			}
		}

		foreach ( $collections as $collection_id => $group ) {
			$total = count( $group );
			$reads = self::get_reads( $group );

			$half = (int) ceil( $total / 2 );

			if ( $total < self::COLLECTION_STEP_BOOKS || count( $reads ) === $total || count( $reads ) < $half ) {
				continue;
			}

			$term     = get_term( $collection_id, 'library-publisher' );
			$earned[] = self::achievement(
				'collection-step',
				'',
				$term instanceof WP_Term ? $term->name : '',
				/* translators: 1: number of books read, 2: number of books. */
				sprintf( __( '%1$s read of %2$s', 'library' ), number_format_i18n( count( $reads ) ), number_format_i18n( $total ) ),
				self::term_target( 'collection', $collection_id, $group ),
				$reads[ $half - 1 ]['read_date']
			);
		}

		return self::level(
			$earned,
			/* translators: %s: number of collections read by half or more. */
			fn( int $count ) => sprintf( _n( '%s collection read by half or more', '%s collections read by half or more', $count, 'library' ), number_format_i18n( $count ) )
		);
	}

	/**
	 * The highest number of books read reached, dated by the reading that reached it, and the next one
	 * with its progress; reads without date come first.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array{0:array<int,array>,1:array<int,array>} Earned, then next.
	 */
	private function get_read_milestones( array $reads ): array {
		/* translators: %s: number of books read. */
		$title  = fn( int $milestone ) => sprintf( __( '%s books read', 'library' ), number_format_i18n( $milestone ) );
		$earned = array();
		$next   = array();

		foreach ( self::READ_MILESTONES as $milestone ) {
			if ( count( $reads ) < $milestone ) {
				$next[] = self::achievement( 'read', $title( $milestone ), '', '', null, '' ) + array(
					'done'  => count( $reads ),
					'total' => $milestone,
				);
				break;
			}

			$earned = array(
				self::achievement(
					'read',
					$title( $milestone ),
					'',
					$reads[ $milestone - 1 ]['full_title'],
					null,
					$reads[ $milestone - 1 ]['read_date']
				),
			);
		}

		return array( $earned, $next );
	}

	/**
	 * The highest number of pages read reached, over the books read with their number of pages, and the next
	 * one with its progress, once a book read has its number of pages.
	 *
	 * @param array<int,array> $readings Readings, in reading order.
	 * @return array{0:array<int,array>,1:array<int,array>} Earned, then next.
	 */
	private function get_page_milestones( array $readings ): array {
		/* translators: %s: number of pages read. */
		$title      = fn( int $milestone ) => sprintf( __( '%s pages read', 'library' ), number_format_i18n( $milestone ) );
		$earned     = array();
		$pages      = 0;
		$milestones = self::PAGE_MILESTONES;

		foreach ( $readings as $book ) {
			$pages += (int) $book['pages'];

			while ( $milestones && $pages >= $milestones[0] ) {
				$earned = array(
					self::achievement(
						'pages',
						$title( array_shift( $milestones ) ),
						'',
						$book['full_title'],
						null,
						$book['read_date']
					),
				);
			}
		}

		// Like the Reading widget, nothing to show until a book read has its number of pages.
		if ( ! $milestones || ! $pages ) {
			return array( $earned, array() );
		}

		$next = self::achievement( 'pages', $title( $milestones[0] ), '', '', null, '' ) + array(
			'done'  => $pages,
			'total' => $milestones[0],
		);

		return array( $earned, array( $next ) );
	}

	/**
	 * The year and the month with the most books read, dated by the reading that beat the former record.
	 *
	 * @param array<int,array> $readings Readings, in reading order.
	 * @return array<int,array>
	 */
	private function get_records( array $readings ): array {
		$dated   = array_values( array_filter( $readings, fn( array $book ) => '' !== $book['read_date'] ) );
		$periods = array(
			'year'  => array(
				'length' => 4,
				'title'  => __( 'Record year', 'library' ),
				'format' => 'Y',
			),
			'month' => array(
				'length' => 7,
				'title'  => __( 'Record month', 'library' ),
				'format' => 'F Y',
			),
		);
		$earned  = array();

		foreach ( $periods as $kind => $period ) {
			$by_period = array();

			foreach ( $dated as $book ) {
				$by_period[ substr( $book['read_date'], 0, $period['length'] ) ][] = $book;
			}

			if ( count( $by_period ) < 2 ) {
				continue;
			}

			$counts = array_map( 'count', $by_period );
			$best   = max( $counts );
			$record = (string) array_search( $best, $counts, true );
			$before = array_filter( $counts, fn( string $key ) => $key < $record, ARRAY_FILTER_USE_KEY );
			$former = $before ? max( $before ) : 0;

			$earned[] = self::achievement(
				"record-$kind",
				$period['title'],
				date_i18n( $period['format'], strtotime( str_pad( $record, 10, '-01', STR_PAD_RIGHT ) ) ),
				Library_Books::books_text( $best ),
				null,
				$by_period[ $record ][ $former ]['read_date']
			);
		}

		return $earned;
	}

	/**
	 * The longest run of months in a row with a reading.
	 *
	 * @param array<int,array> $readings Readings, in reading order.
	 * @return array<int,array>
	 */
	private function get_month_streak( array $readings ): array {
		$months = array();

		foreach ( $readings as $book ) {
			if ( '' !== $book['read_date'] ) {
				$months[ substr( $book['read_date'], 0, 7 ) ] = $book['read_date'];
			}
		}

		$longest = array();
		$run     = array();

		foreach ( $months as $month => $date ) {
			$previous = $run ? gmdate( 'Y-m', strtotime( array_key_last( $run ) . '-01 +1 month' ) ) : '';
			$run      = $previous === $month ? $run + array( $month => $date ) : array( $month => $date );

			if ( count( $run ) > count( $longest ) ) {
				$longest = $run;
			}
		}

		if ( count( $longest ) < self::MONTH_STREAK ) {
			return array();
		}

		return array(
			self::achievement(
				'streak',
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
	 * @param array<int,array> $readings Readings, in reading order.
	 * @return array<int,array>
	 */
	private function get_rereads( array $readings ): array {
		$counts = array();
		$level  = 0;
		$found  = null;

		foreach ( $readings as $reading ) {
			$counts[ $reading['id'] ] = ( $counts[ $reading['id'] ] ?? 0 ) + 1;

			if ( $counts[ $reading['id'] ] - 1 > $level ) {
				$level = $counts[ $reading['id'] ] - 1;
				$found = $reading;
			}
		}

		if ( ! $found ) {
			return array();
		}

		return array(
			self::achievement(
				'reread',
				/* translators: %s: most times a book was read again. */
				sprintf( _n( 'Read again %s time', 'Read again %s times', $level, 'library' ), number_format_i18n( $level ) ),
				$found['full_title'],
				/* translators: %s: year of the first reading of the book. */
				sprintf( __( 'First read in %s', 'library' ), substr( $found['read_dates'][0], 0, 4 ) ),
				self::book_target( $found['id'] ),
				$found['read_date']
			),
		);
	}

	/**
	 * Year a work first came out.
	 *
	 * @param array $book Book.
	 * @return int|null
	 */
	private static function first_year( array $book ): ?int {
		return '' !== $book['first_date'] ? (int) substr( $book['first_date'], 0, 4 ) : null;
	}

	/**
	 * The latest of books that earned an achievement, with how many did.
	 *
	 * @param string           $kind        Kind.
	 * @param string           $title       Title.
	 * @param string           $description What the book did, e.g. "Read on 29 February".
	 * @param array<int,array> $books       Books that earned it, in reading order.
	 * @return array<int,array> The achievement, or none.
	 */
	private static function latest( string $kind, string $title, string $description, array $books ): array {
		if ( ! $books ) {
			return array();
		}

		$book = end( $books );
		/* translators: 1: what the books did, e.g. "Read on 29 February", 2: number of books, e.g. "3 books". */
		$detail = count( $books ) > 1 ? sprintf( __( '%1$s (%2$s)', 'library' ), $description, Library_Books::books_text( count( $books ) ) ) : $description;

		return array(
			self::achievement(
				$kind,
				$title,
				$book['full_title'],
				$detail,
				self::book_target( $book['id'] ),
				$book['read_date']
			),
		);
	}

	/**
	 * Books read the year they came out, a work read a century after, and the oldest book of the pile read.
	 *
	 * @param array<int,array> $books All the books.
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array<int,array>
	 */
	private function get_discoveries( array $books, array $reads ): array {
		$dated = array_values( array_filter( $reads, fn( array $book ) => '' !== $book['read_date'] ) );

		$hot_off_the_press = array_filter( $dated, fn( array $book ) => self::first_year( $book ) === (int) substr( $book['read_date'], 0, 4 ) );
		$centenarians      = array_filter(
			$dated,
			fn( array $book ) => null !== self::first_year( $book ) && (int) substr( $book['read_date'], 0, 4 ) - self::first_year( $book ) >= self::CENTENARIAN_YEARS
		);

		return array_merge(
			self::latest( 'hot-off-the-press', __( 'Hot off the press', 'library' ), __( 'Read the year it came out', 'library' ), array_values( $hot_off_the_press ) ),
			self::latest( 'centenarian', __( 'Centenarian', 'library' ), __( 'Read a century or more after it first came out', 'library' ), array_values( $centenarians ) ),
			self::latest( 'archaeologist', __( 'Archaeologist', 'library' ), __( 'Read while the oldest book of the pile', 'library' ), $this->get_oldest_of_the_pile_reads( $books, $dated ) )
		);
	}

	/**
	 * Books read while no book added before them was left to read.
	 *
	 * A book read without date counts as read before any other.
	 *
	 * @param array<int,array> $books All the books.
	 * @param array<int,array> $dated Books read with their date, in reading order.
	 * @return array<int,array>
	 */
	private function get_oldest_of_the_pile_reads( array $books, array $dated ): array {
		$by_added = array_values( $books );

		usort( $by_added, fn( array $a, array $b ) => strcmp( $a['added'], $b['added'] ) );

		// The latest date by which each book added so far was read; a book to read is never read.
		$read_by = array();
		$latest  = '';
		$added   = '';
		$pending = '';

		foreach ( $by_added as $book ) {
			if ( $book['added'] !== $added ) {
				$latest = max( $latest, $pending );
				$added  = $book['added'];
			}

			$read_by[ $book['id'] ] = $latest;
			$pending                = max( $pending, $book['read'] ? $book['read_date'] : '9999-12-31' );
		}

		return array_values( array_filter( $dated, fn( array $book ) => $read_by[ $book['id'] ] <= $book['read_date'] ) );
	}

	/**
	 * A book read that came out in each decade of the 20th century.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array{0:array<int,array>,1:array<int,array>} Earned, then close.
	 */
	private function get_decade_tour( array $reads ): array {
		$decades = array();

		foreach ( $reads as $book ) {
			$year = self::first_year( $book ) ?? 0;

			if ( $year >= 1900 && $year < 2000 && ! isset( $decades[ intdiv( $year, 10 ) ] ) ) {
				$decades[ intdiv( $year, 10 ) ] = $book;
			}
		}

		$title = __( 'Tour of the 20th century', 'library' );
		/* translators: %s: number of decades of the 20th century with a book read. */
		$detail = sprintf( __( '%s decades of 10', 'library' ), number_format_i18n( count( $decades ) ) );

		if ( 10 === count( $decades ) ) {
			return array( array( self::achievement( 'decades', $title, '', $detail, null, self::last_read_date( $decades ) ) ), array() );
		}

		if ( count( $decades ) >= self::CLOSE_DECADES ) {
			$progress = array(
				'done'  => count( $decades ),
				'total' => 10,
			);

			return array( array(), array( self::achievement( 'decades', $title, '', $detail, null, '' ) + $progress ) );
		}

		return array( array(), array() );
	}

	/**
	 * Translators with many books read, as one achievement, and those close to it.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array{0:array<int,array>,1:array<int,array>} Earned, then close.
	 */
	private function get_translators( array $reads ): array {
		$translators = array();
		$names       = array();
		$earned      = array();
		$close       = array();

		foreach ( $reads as $book ) {
			foreach ( $book['translators'] as $translator ) {
				$translators[ self::key( $translator ) ][] = $book;
				$names[ self::key( $translator ) ]       ??= $translator;
			}
		}

		foreach ( $translators as $key => $books ) {
			$translator = $names[ $key ];
			$left       = self::TRANSLATOR_BOOKS - count( $books );
			$detail     = Library_Books::books_text( count( $books ) );

			if ( $left <= 0 ) {
				$earned[] = self::achievement( 'translator', '', $translator, $detail, self::search_target( Library_WP_Query::keyword_search( 'translator', $translator ), $books ), $books[ self::TRANSLATOR_BOOKS - 1 ]['read_date'] );
			} elseif ( $left <= self::CLOSE_BOOKS ) {
				$close[] = self::achievement( 'translator', __( 'Faithful to a translator', 'library' ), $translator, $detail, self::search_target( Library_WP_Query::keyword_search( 'translator', $translator ), $books ), '' ) + array(
					'done'  => count( $books ),
					'total' => self::TRANSLATOR_BOOKS,
				);
			}
		}

		$level = self::level(
			$earned,
			/* translators: %s: number of translators with many books read. */
			fn( int $count ) => sprintf( _n( 'Faithful to %s translator', 'Faithful to %s translators', $count, 'library' ), number_format_i18n( $count ) )
		);

		return array( $level, $close );
	}

	/**
	 * Numbers of original languages read, dated by the reading that reached them, and the next one when close.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array{0:array<int,array>,1:array<int,array>} Earned, then close.
	 */
	private function get_language_milestones( array $reads ): array {
		$languages  = array();
		$earned     = array();
		$milestones = self::LANGUAGE_MILESTONES;

		foreach ( $reads as $book ) {
			foreach ( $book['languages'] as $language ) {
				if ( isset( $languages[ $language ] ) ) {
					continue;
				}

				$languages[ $language ] = $book;

				if ( $milestones && count( $languages ) === $milestones[0] ) {
					$earned[] = self::achievement(
						'languages',
						/* translators: %s: number of original languages read. */
						sprintf( __( '%s original languages', 'library' ), number_format_i18n( array_shift( $milestones ) ) ),
						'',
						$book['full_title'],
						null,
						$book['read_date']
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

		return array( $earned, array( self::achievement( 'languages', $title, '', $detail, null, '' ) + $progress ) );
	}

	/**
	 * Books read in their original language, a polyglot month, a Rosetta week and translators from several languages.
	 *
	 * The original language is foreign when its code, the slug of the term, is not the language of the admin: "fr" for fr_FR.
	 *
	 * @param array<int,array> $reads    Books read, in reading order.
	 * @param array<int,array> $readings Readings, in reading order.
	 * @return array<int,array>
	 */
	private function get_language_feats( array $reads, array $readings ): array {
		$own_language = strtok( get_user_locale(), '_' );
		$dated        = array_values( array_filter( $readings, fn( array $book ) => '' !== $book['read_date'] ) );
		$languages    = fn( array $book ) => $book['languages'];
		$feats        = self::latest(
			'original-language',
			__( 'In the original', 'library' ),
			__( 'Read in its original language, without translator', 'library' ),
			array_values(
				array_filter(
					$reads,
					fn( array $book ) => $book['languages'] && ! $book['translators'] && ! isset( $book['languages'][ $own_language ] )
				)
			)
		);

		$polyglot = self::latest_variety( $dated, fn( array $book ) => substr( $book['read_date'], 0, 7 ), $languages, self::POLYGLOT_LANGUAGES );

		if ( $polyglot ) {
			$feats[] = self::achievement(
				'polyglot',
				__( 'Polyglot of the month', 'library' ),
				date_i18n( 'F Y', strtotime( $polyglot['period'] . '-01' ) ),
				/* translators: %s: number of original languages. */
				sprintf( __( '%s original languages in a month', 'library' ), number_format_i18n( self::POLYGLOT_LANGUAGES ) ),
				null,
				$polyglot['book']['read_date']
			);
		}

		$rosetta = self::latest_variety( $dated, fn( array $book ) => gmdate( 'o-W', strtotime( $book['read_date'] ) ), $languages, self::ROSETTA_LANGUAGES );

		if ( $rosetta ) {
			$feats[] = self::achievement(
				'rosetta-stone',
				__( 'Rosetta Stone', 'library' ),
				'',
				/* translators: %s: number of original languages. */
				sprintf( __( '%s original languages in a week', 'library' ), number_format_i18n( self::ROSETTA_LANGUAGES ) ),
				null,
				$rosetta['book']['read_date']
			);
		}

		return array_merge( $feats, $this->get_go_betweens( $reads ) );
	}

	/**
	 * Translators read from several original languages, dated by the reading that brought the second one.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array<int,array>
	 */
	private function get_go_betweens( array $reads ): array {
		$from   = array();
		$names  = array();
		$earned = array();

		foreach ( $reads as $book ) {
			foreach ( $book['translators'] as $translator ) {
				$names[ self::key( $translator ) ] ??= $translator;

				foreach ( $book['languages'] as $language ) {
					$from[ self::key( $translator ) ][ $language ] ??= $book;
				}
			}
		}

		foreach ( array_filter( $from, fn( array $languages ) => count( $languages ) > 1 ) as $key => $languages ) {
			$translator = $names[ $key ];
			$earned[]   = self::achievement(
				'go-between',
				__( 'Go-between', 'library' ),
				$translator,
				/* translators: %s: original languages, e.g. "Japanese, English". */
				sprintf( __( 'Translated from %s', 'library' ), implode( ', ', array_keys( $languages ) ) ),
				self::search_target( Library_WP_Query::keyword_search( 'translator', $translator ), array_values( $languages ) ),
				array_values( $languages )[1]['read_date']
			);
		}

		return $earned;
	}

	/**
	 * The latest period, e.g. a month, in which books read reached a number of different values, e.g. languages,
	 * with the reading that reached it.
	 *
	 * @param array<int,array> $dated  Readings with their date, in reading order.
	 * @param callable         $period Period of a reading, e.g. "2026-10".
	 * @param callable         $values Values of a book, e.g. its languages.
	 * @param int              $count  Number of different values to reach.
	 * @return array{period:string,book:array}|null
	 */
	private static function latest_variety( array $dated, callable $period, callable $values, int $count ): ?array {
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
	 * @param array<int,array> $reads Books read, in reading order.
	 * @param array<int,array> $dated Readings with their date, in reading order.
	 * @return array<int,array>
	 */
	private function get_odd_pages( array $reads, array $dated ): array {
		$by_pages = fn( int $pages ) => array_values( array_filter( $reads, fn( array $book ) => $pages === $book['pages'] ) );
		$by_year  = array();

		foreach ( $dated as $book ) {
			$by_year[ substr( $book['read_date'], 0, 4 ) ][] = $book;
		}

		$answers = array_merge( $by_pages( self::ODD_PAGES['ultimate-question'] ), array_column( $by_year, 41 ) );

		usort( $answers, fn( array $a, array $b ) => strcmp( $a['read_date'], $b['read_date'] ) );

		return array_merge(
			self::latest(
				'page-404',
				__( 'Error 404', 'library' ),
				/* translators: %s: number of pages. */
				sprintf( __( 'Page not found: a book of %s pages', 'library' ), number_format_i18n( self::ODD_PAGES['page-404'] ) ),
				$by_pages( self::ODD_PAGES['page-404'] )
			),
			self::latest(
				'fahrenheit-451',
				__( 'Fahrenheit 451', 'library' ),
				/* translators: %s: number of pages. */
				sprintf( __( 'A book of %s pages, the temperature at which paper burns', 'library' ), number_format_i18n( self::ODD_PAGES['fahrenheit-451'] ) ),
				$by_pages( self::ODD_PAGES['fahrenheit-451'] )
			),
			self::latest( 'ultimate-question', __( 'The Ultimate Question', 'library' ), __( 'A book of 42 pages, or the 42nd read of a year', 'library' ), $answers )
		);
	}

	/**
	 * The latest month with a short book and a long one read.
	 *
	 * @param array<int,array> $dated Readings with their date, in reading order.
	 * @return array<int,array>
	 */
	private function get_splits( array $dated ): array {
		$size   = fn( array $book ) => match ( true ) {
			null === $book['pages'] => array(),
			$book['pages'] < self::SPLITS_PAGES['short'] => array( 'short' ),
			$book['pages'] > self::SPLITS_PAGES['long'] => array( 'long' ),
			default => array(),
		};
		$splits = self::latest_variety( $dated, fn( array $book ) => substr( $book['read_date'], 0, 7 ), $size, 2 );

		if ( ! $splits ) {
			return array();
		}

		return array(
			self::achievement(
				'splits',
				__( 'The splits', 'library' ),
				date_i18n( 'F Y', strtotime( $splits['period'] . '-01' ) ),
				sprintf(
					/* translators: 1: number of pages of a short book, 2: number of pages of a long one. */
					__( 'Under %1$s and over %2$s pages in the same month', 'library' ),
					number_format_i18n( self::SPLITS_PAGES['short'] ),
					number_format_i18n( self::SPLITS_PAGES['long'] )
				),
				null,
				$splits['book']['read_date']
			),
		);
	}

	/**
	 * The latest reading that brought the pages read over the previous 365 days to the height of the Everest.
	 *
	 * @param array<int,array> $dated Readings with their date, in reading order.
	 * @return array<int,array>
	 */
	private function get_everest( array $dated ): array {
		$window = array();
		$pages  = 0;
		$summit = '';

		foreach ( array_filter( $dated, fn( array $book ) => null !== $book['pages'] ) as $book ) {
			$window[] = $book;
			$pages   += $book['pages'];
			$start    = gmdate( 'Y-m-d', strtotime( $book['read_date'] . ' -364 days' ) );

			while ( $window[0]['read_date'] < $start ) {
				$pages -= array_shift( $window )['pages'];
			}

			if ( $pages >= self::EVEREST_PAGES ) {
				$summit = $book['read_date'];
			}
		}

		if ( '' === $summit ) {
			return array();
		}

		/* translators: %s: number of pages. */
		$detail = sprintf( __( '%s pages read within a year', 'library' ), number_format_i18n( self::EVEREST_PAGES ) );

		return array( self::achievement( 'everest', __( 'Everest', 'library' ), '', $detail, null, $summit ) );
	}

	/**
	 * The latest month with works read from several centuries.
	 *
	 * @param array<int,array> $dated Readings with their date, in reading order.
	 * @return array<int,array>
	 */
	private function get_time_machine( array $dated ): array {
		$century = fn( array $book ) => null !== self::first_year( $book ) ? array( intdiv( self::first_year( $book ), 100 ) ) : array();
		$trip    = self::latest_variety( $dated, fn( array $book ) => substr( $book['read_date'], 0, 7 ), $century, self::TIME_MACHINE_CENTURIES );

		if ( ! $trip ) {
			return array();
		}

		return array(
			self::achievement(
				'time-machine',
				__( 'Time machine', 'library' ),
				date_i18n( 'F Y', strtotime( $trip['period'] . '-01' ) ),
				/* translators: %s: number of centuries. */
				sprintf( __( 'Works from %s centuries in the same month', 'library' ), number_format_i18n( self::TIME_MACHINE_CENTURIES ) ),
				null,
				$trip['book']['read_date']
			),
		);
	}

	/**
	 * The latest two works that came out the same year, by different authors, read the same day.
	 *
	 * @param array<int,array> $dated Readings with their date, in reading order.
	 * @return array<int,array>
	 */
	private function get_twins( array $dated ): array {
		$seen  = array();
		$twins = null;

		foreach ( array_filter( $dated, fn( array $book ) => null !== self::first_year( $book ) && $book['author_ids'] ) as $book ) {
			$key = $book['read_date'] . self::first_year( $book );

			foreach ( $seen[ $key ] ?? array() as $twin ) {
				if ( ! array_intersect( $twin['author_ids'], $book['author_ids'] ) ) {
					$twins = array( $twin, $book );
				}
			}

			$seen[ $key ][] = $book;
		}

		if ( ! $twins ) {
			return array();
		}

		return array(
			self::achievement(
				'twins',
				__( 'Twins', 'library' ),
				$twins[1]['full_title'],
				sprintf(
					/* translators: 1: title of the other book, 2: year both first came out. */
					__( 'Read the same day as %1$s, both came out in %2$s', 'library' ),
					$twins[0]['full_title'],
					self::first_year( $twins[1] )
				),
				self::book_target( $twins[1]['id'] ),
				$twins[1]['read_date']
			),
		);
	}

	/**
	 * Easter eggs: odd days, odd books and odd habits.
	 *
	 * @param array<int,array> $books    All the books.
	 * @param array<int,array> $reads    Books read, in reading order.
	 * @param array<int,array> $readings Readings, in reading order.
	 * @return array<int,array>
	 */
	private function get_easter_eggs( array $books, array $reads, array $readings ): array {
		$dated       = array_values( array_filter( $readings, fn( array $book ) => '' !== $book['read_date'] ) );
		$dated_reads = array_values( array_filter( $reads, fn( array $book ) => '' !== $book['read_date'] ) );
		$on          = fn( string $month_day ) => array_values( array_filter( $dated, fn( array $book ) => substr( $book['read_date'], 5 ) === $month_day ) );
		$to_read     = count( $books ) - count( $reads );
		$eggs        = array();

		if ( $to_read >= self::TSUNDOKU_BOOKS ) {
			/* translators: %s: number of books to read. */
			$eggs[] = self::achievement( 'tsundoku', __( 'Tsundoku', 'library' ), '', sprintf( __( '%s books waiting to be read', 'library' ), number_format_i18n( $to_read ) ), null, '' );
		}

		$eggs = array_merge(
			$eggs,
			self::latest( 'leap-day', __( 'Leap day', 'library' ), __( 'Read on 29 February', 'library' ), $on( '02-29' ) ),
			self::latest( 'pi-day', __( 'Pi day', 'library' ), __( 'Read on 14 March', 'library' ), $on( '03-14' ) ),
			self::latest( 'new-year', __( 'New Year\'s resolution', 'library' ), __( 'Read on 1 January', 'library' ), $on( '01-01' ) ),
			self::latest( 'new-years-eve', __( 'Last of the year', 'library' ), __( 'Read on 31 December', 'library' ), $on( '12-31' ) ),
			self::latest(
				'friday-13th',
				__( 'Friday the 13th', 'library' ),
				__( 'Read on a Friday the 13th', 'library' ),
				array_values( array_filter( $dated, fn( array $book ) => '13' === substr( $book['read_date'], 8 ) && '5' === gmdate( 'N', strtotime( $book['read_date'] ) ) ) )
			),
			self::latest(
				'palindrome',
				__( 'Palindrome date', 'library' ),
				__( 'Read on a date that reads the same backwards', 'library' ),
				array_values( array_filter( $dated, fn( array $book ) => str_replace( '-', '', $book['read_date'] ) === strrev( str_replace( '-', '', $book['read_date'] ) ) ) )
			),
			self::latest(
				'love-at-first-sight',
				__( 'Love at first sight', 'library' ),
				__( 'Added and read the same day', 'library' ),
				array_values( array_filter( $dated, fn( array $book ) => substr( $book['added'], 0, 10 ) === $book['read_date'] ) )
			),
			self::latest(
				'brick',
				__( 'Brick', 'library' ),
				/* translators: %s: number of pages. */
				sprintf( __( 'A book of %s pages or more', 'library' ), number_format_i18n( self::BRICK_PAGES ) ),
				array_values( array_filter( $reads, fn( array $book ) => (int) $book['pages'] >= self::BRICK_PAGES ) )
			),
			self::latest(
				'one-that-got-away',
				__( 'The one that got away', 'library' ),
				/* translators: %s: rating, out of 10. */
				sprintf( __( 'Not kept, rated %s or more', 'library' ), number_format_i18n( self::LOVED_RATING ) ),
				array_values( array_filter( $reads, fn( array $book ) => Library_Posts::NOT_KEPT === $book['status'] && $book['rating'] >= self::LOVED_RATING ) )
			),
			self::latest(
				'mouthful',
				__( 'Mouthful', 'library' ),
				/* translators: %s: number of characters. */
				sprintf( __( 'A title of %s characters or more', 'library' ), number_format_i18n( self::LONG_TITLE ) ),
				array_values( array_filter( $reads, fn( array $book ) => mb_strlen( $book['title'] ) >= self::LONG_TITLE ) )
			),
			$this->get_odd_pages( $reads, $dated ),
			$this->get_splits( $dated ),
			$this->get_everest( $dated ),
			$this->get_time_machine( $dated ),
			$this->get_twins( $dated ),
			$this->get_marathon( $dated ),
			$this->get_daily_streak( $dated ),
			$this->get_hat_trick( $dated ),
			$this->get_namesakes( $reads ),
			$this->get_back_to_the_start( $dated_reads )
		);

		return $eggs;
	}

	/**
	 * The latest day with many books read.
	 *
	 * @param array<int,array> $dated Readings with their date, in reading order.
	 * @return array<int,array>
	 */
	private function get_marathon( array $dated ): array {
		$days = array();

		foreach ( $dated as $book ) {
			$days[ $book['read_date'] ][] = $book;
		}

		$marathons = array_filter( $days, fn( array $books ) => count( $books ) >= self::MARATHON_BOOKS );

		if ( ! $marathons ) {
			return array();
		}

		$day = (string) array_key_last( $marathons );

		/* translators: %s: number of books read the same day. */
		return array( self::achievement( 'marathon', __( 'Marathon', 'library' ), '', sprintf( __( '%s books read the same day', 'library' ), number_format_i18n( count( $marathons[ $day ] ) ) ), null, $day ) );
	}

	/**
	 * The longest run of days in a row with a book finished each day, the latest one among the longest.
	 *
	 * @param array<int,array> $dated Readings with their date, in reading order.
	 * @return array<int,array>
	 */
	private function get_daily_streak( array $dated ): array {
		$longest = array();
		$run     = array();

		foreach ( array_unique( array_column( $dated, 'read_date' ) ) as $day ) {
			$previous = $run ? gmdate( 'Y-m-d', strtotime( end( $run ) . ' +1 day' ) ) : '';
			$run      = $previous === $day ? array( ...$run, $day ) : array( $day );

			if ( count( $run ) >= count( $longest ) ) {
				$longest = $run;
			}
		}

		if ( count( $longest ) < self::DAILY_STREAK ) {
			return array();
		}

		return array(
			self::achievement(
				'seven-days-a-week',
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
	 * Three books rated 10 read in a row.
	 *
	 * @param array<int,array> $dated Readings with their date, in reading order.
	 * @return array<int,array>
	 */
	private function get_hat_trick( array $dated ): array {
		$run   = 0;
		$found = null;

		foreach ( $dated as $book ) {
			$run = 10.0 === $book['rating'] ? $run + 1 : 0;

			if ( $run >= 3 ) {
				$found = $book;
			}
		}

		if ( ! $found ) {
			return array();
		}

		return array( self::achievement( 'hat-trick', __( 'Hat-trick', 'library' ), $found['full_title'], __( 'Three books rated 10 in a row', 'library' ), self::book_target( $found['id'] ), $found['read_date'] ) );
	}

	/**
	 * Two books read with the same title by different authors.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array<int,array>
	 */
	private function get_namesakes( array $reads ): array {
		$titles = array();

		foreach ( $reads as $book ) {
			$titles[ self::key( $book['title'] ) ][] = $book;
		}

		foreach ( array_reverse( $titles ) as $books ) {
			foreach ( $books as $book ) {
				foreach ( $books as $other ) {
					if ( $book['author_ids'] && $other['author_ids'] && ! array_intersect( $book['author_ids'], $other['author_ids'] ) ) {
						$latest = max( $book['read_date'], $other['read_date'] );

						return array( self::achievement( 'namesakes', __( 'Namesakes', 'library' ), $book['title'], __( 'The same title, different authors', 'library' ), self::search_target( $book['title'], $books ), $latest ) );
					}
				}
			}
		}

		return array();
	}

	/**
	 * The first volume of a series read after a later one.
	 *
	 * @param array<int,array> $dated Books read with their date, in reading order.
	 * @return array<int,array>
	 */
	private function get_back_to_the_start( array $dated ): array {
		$later = array();
		$found = null;

		foreach ( $dated as $book ) {
			if ( null === $book['volume'] || '' === $book['series'] ) {
				continue;
			}

			if ( 1 === $book['volume'] && isset( $later[ self::key( $book['series'] ) ] ) ) {
				$found = $book;
			} elseif ( $book['volume'] > 1 ) {
				$later[ self::key( $book['series'] ) ] = true;
			}
		}

		if ( ! $found ) {
			return array();
		}

		return array( self::achievement( 'back-to-the-start', __( 'Back to the start', 'library' ), $found['full_title'], __( 'Volume 1 read after a later volume', 'library' ), self::book_target( $found['id'] ), $found['read_date'] ) );
	}
}
