<?php
/**
 * Achievements dashboard widget.
 *
 * @link       https://github.com/19h47/library
 * @since      2.2.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Achievements drawn from the books, computed on each view: nothing is stored, so a completion is lost
 * when a book to read joins it, and comes back once it is read.
 *
 * Completions (an author, a series or a collection read in full), milestones (books and pages read,
 * record year and month, months in a row), discoveries (hot off the press, a centenarian, the oldest of
 * the pile, the decades of the 20th century, a translator) and a few easter eggs. Books not kept count,
 * since they are read. An achievement is dated by the reading that earned it, when it has a date.
 *
 * @since      2.2.0
 * @package    Library
 * @subpackage Library/admin
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
	 * Number of books of a collection from which reading half and three quarters of them counts.
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
	 * Number of achievements in the latest ones and in the close ones of the widget.
	 *
	 * @var int
	 */
	private const LIST_LENGTH = 5;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
	}

	/**
	 * Register the dashboard widget.
	 */
	public function register_widget(): void {
		wp_add_dashboard_widget( 'library_dashboard_achievements', __( 'Achievements', 'library' ), array( $this, 'render' ) );
	}

	/**
	 * Render the latest achievements, the close ones and all of them.
	 */
	public function render(): void {
		$achievements = $this->get_achievements();
		$latest       = array_map( array( self::class, 'ranking_item' ), array_slice( $achievements['earned'], 0, self::LIST_LENGTH ) );
		$close        = array_map( array( self::class, 'ranking_item' ), array_slice( $achievements['close'], 0, self::LIST_LENGTH ) );
		$all          = array_map( array( self::class, 'ranking_item' ), $achievements['earned'] );

		include plugin_dir_path( __FILE__ ) . 'partials/library-dashboard-achievements.php';
	}

	/**
	 * Achievements earned, latest first and undated last, and those close to be earned, closest first.
	 *
	 * @return array{earned:array<int,array{kind:string,title:string,subject:string,detail:string,url:string,date:string}>,close:array<int,array{kind:string,title:string,subject:string,detail:string,url:string,date:string}>}
	 */
	public function get_achievements(): array {
		$books = Library_Books::all();
		$reads = self::get_reads( $books );

		[ $completions, $close ]             = $this->get_completions( $books );
		[ $tour, $close_tour ]               = $this->get_decade_tour( $reads );
		[ $translators, $close_translators ] = $this->get_translators( $reads );

		$earned = array_merge(
			$completions,
			$this->get_collection_steps( $books ),
			$this->get_read_milestones( $reads ),
			$this->get_page_milestones( $reads ),
			$this->get_records( $reads ),
			$this->get_month_streak( $reads ),
			$this->get_discoveries( $books, $reads ),
			$tour,
			$translators,
			$this->get_easter_eggs( $books, $reads )
		);

		usort( $earned, fn( array $a, array $b ) => array( '' === $a['date'], $b['date'] ) <=> array( '' === $b['date'], $a['date'] ) );

		$close = array_merge( $close, $close_tour, $close_translators );

		usort( $close, fn( array $a, array $b ) => array( $a['left'], $a['subject'] ) <=> array( $b['left'], $b['subject'] ) );

		return array(
			'earned' => $earned,
			'close'  => array_map( fn( array $item ) => array_diff_key( $item, array( 'left' => true ) ), $close ),
		);
	}

	/**
	 * An achievement.
	 *
	 * @param string $kind    Kind, e.g. "author".
	 * @param string $title   Title, e.g. "Complete author".
	 * @param string $subject What it is about: an author, a series, a book… or empty.
	 * @param string $detail  Detail, e.g. "9 books".
	 * @param string $url     Link, or empty.
	 * @param string $date    Date earned, Y-m-d, or empty when unknown or not dated.
	 * @return array{kind:string,title:string,subject:string,detail:string,url:string,date:string}
	 */
	private static function achievement( string $kind, string $title, string $subject, string $detail, string $url, string $date ): array {
		return array(
			'kind'    => $kind,
			'title'   => $title,
			'subject' => $subject,
			'detail'  => $detail,
			'url'     => $url,
			'date'    => $date,
		);
	}

	/**
	 * An entry of a widget ranking: the subject, or else the title, then the rest.
	 *
	 * @param array $achievement Achievement.
	 * @return array{name:string,url:string,text:string}
	 */
	private static function ranking_item( array $achievement ): array {
		$date = '' !== $achievement['date'] ? date_i18n( get_option( 'date_format' ), strtotime( $achievement['date'] ) ) : '';
		$name = '' !== $achievement['subject'] ? $achievement['subject'] : $achievement['title'];
		$text = array( '' !== $achievement['subject'] ? $achievement['title'] : '', $achievement['detail'], $date );

		return array(
			'name' => $name,
			'url'  => $achievement['url'],
			'text' => implode( ' · ', array_filter( $text ) ),
		);
	}

	/**
	 * The books read, those without read date first, then by read date.
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
	 * The latest read date of books, or empty when none has one.
	 *
	 * @param array<int,array> $books Books.
	 * @return string
	 */
	private static function last_read_date( array $books ): string {
		return (string) max( array_merge( array( '' ), array_column( $books, 'read_date' ) ) );
	}

	/**
	 * The link to the books of a term in the books list.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param int    $term_id  Term ID.
	 * @return string
	 */
	private static function term_url( string $taxonomy, int $term_id ): string {
		$term = get_term( $term_id, $taxonomy );

		return $term instanceof WP_Term ? add_query_arg( $taxonomy, $term->slug, admin_url( 'edit.php?post_type=book' ) ) : '';
	}

	/**
	 * The link to the books list searched for a text, e.g. a series or a translator.
	 *
	 * @param string $search Search.
	 * @return string
	 */
	private static function search_url( string $search ): string {
		return add_query_arg( 's', rawurlencode( $search ), admin_url( 'edit.php?post_type=book' ) );
	}

	/**
	 * Number of books as text.
	 *
	 * @param int $books Number of books.
	 * @return string
	 */
	private static function books_text( int $books ): string {
		/* translators: %s: number of books. */
		return sprintf( _n( '%s book', '%s books', $books, 'library' ), number_format_i18n( $books ) );
	}

	/**
	 * Authors, series and collections read in full, and those with few books left.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array{0:array<int,array>,1:array<int,array>} Earned, then close.
	 */
	private function get_completions( array $books ): array {
		$groups = array(
			'author'     => array(),
			'series'     => array(),
			'collection' => array(),
		);

		foreach ( $books as $book ) {
			foreach ( $book['author_ids'] as $author_id ) {
				$groups['author'][ $author_id ][] = $book;
			}

			if ( '' !== $book['series'] ) {
				$groups['series'][ $book['series'] ][] = $book;
			}

			foreach ( $book['collection_ids'] as $collection_id ) {
				$groups['collection'][ $collection_id ][] = $book;
			}
		}

		$titles = array(
			'author'     => __( 'Complete author', 'library' ),
			'series'     => __( 'Series finished', 'library' ),
			'collection' => __( 'Collection finished', 'library' ),
		);
		$earned = array();
		$close  = array();

		foreach ( $groups as $kind => $kind_groups ) {
			foreach ( $kind_groups as $key => $group ) {
				$total = count( $group );

				if ( $total < self::COMPLETION_BOOKS[ $kind ] ) {
					continue;
				}

				$read = array_filter( $group, fn( array $book ) => $book['read'] );
				$left = $total - count( $read );

				if ( $left > self::CLOSE_BOOKS ) {
					continue;
				}

				if ( 'series' === $kind ) {
					$subject = (string) $key;
					$url     = self::search_url( $subject );
				} else {
					$taxonomy = 'author' === $kind ? 'library-author' : 'library-publisher';
					$term     = get_term( (int) $key, $taxonomy );
					$subject  = $term instanceof WP_Term ? $term->name : '';
					$url      = self::term_url( $taxonomy, (int) $key );
				}

				if ( 0 === $left ) {
					$earned[] = self::achievement( $kind, $titles[ $kind ], $subject, self::books_text( $total ), $url, self::last_read_date( $read ) );
					continue;
				}

				$next = current( array_filter( $group, fn( array $book ) => ! $book['read'] ) );

				$close[] = self::achievement(
					$kind,
					$titles[ $kind ],
					$subject,
					sprintf(
						/* translators: 1: number of books left, 2: title of a book left. */
						_n( '%1$s book left: %2$s', '%1$s books left, such as %2$s', $left, 'library' ),
						number_format_i18n( $left ),
						$next['title']
					),
					$url,
					''
				) + array( 'left' => $left );
			}
		}

		return array( $earned, $close );
	}

	/**
	 * Half and three quarters of the big collections read, dated by the reading that reached them.
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

			if ( $total < self::COLLECTION_STEP_BOOKS || count( $reads ) === $total ) {
				continue;
			}

			$steps = array(
				75 => __( 'Three quarters of a collection', 'library' ),
				50 => __( 'Half of a collection', 'library' ),
			);

			foreach ( $steps as $percent => $title ) {
				$needed = (int) ceil( $total * $percent / 100 );

				if ( count( $reads ) < $needed ) {
					continue;
				}

				$term     = get_term( $collection_id, 'library-publisher' );
				$earned[] = self::achievement(
					'collection-step',
					$title,
					$term instanceof WP_Term ? $term->name : '',
					/* translators: 1: number of books read, 2: number of books. */
					sprintf( __( '%1$s read of %2$s', 'library' ), number_format_i18n( count( $reads ) ), number_format_i18n( $total ) ),
					self::term_url( 'library-publisher', $collection_id ),
					$reads[ $needed - 1 ]['read_date']
				);
				break;
			}
		}

		return $earned;
	}

	/**
	 * Numbers of books read, dated by the reading that reached them; reads without date come first.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array<int,array>
	 */
	private function get_read_milestones( array $reads ): array {
		$earned = array();

		foreach ( self::READ_MILESTONES as $milestone ) {
			if ( count( $reads ) < $milestone ) {
				break;
			}

			$earned[] = self::achievement(
				'read',
				/* translators: %s: number of books read. */
				sprintf( __( '%s books read', 'library' ), number_format_i18n( $milestone ) ),
				'',
				$reads[ $milestone - 1 ]['title'],
				(string) get_edit_post_link( $reads[ $milestone - 1 ]['id'], 'raw' ),
				$reads[ $milestone - 1 ]['read_date']
			);
		}

		return $earned;
	}

	/**
	 * Numbers of pages read, over the books read with their number of pages.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array<int,array>
	 */
	private function get_page_milestones( array $reads ): array {
		$earned     = array();
		$pages      = 0;
		$milestones = self::PAGE_MILESTONES;

		foreach ( $reads as $book ) {
			$pages += (int) $book['pages'];

			while ( $milestones && $pages >= $milestones[0] ) {
				$earned[] = self::achievement(
					'pages',
					/* translators: %s: number of pages read. */
					sprintf( __( '%s pages read', 'library' ), number_format_i18n( array_shift( $milestones ) ) ),
					'',
					$book['title'],
					(string) get_edit_post_link( $book['id'], 'raw' ),
					$book['read_date']
				);
			}
		}

		return $earned;
	}

	/**
	 * The year and the month with the most books read, dated by the reading that beat the former record.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array<int,array>
	 */
	private function get_records( array $reads ): array {
		$dated   = array_values( array_filter( $reads, fn( array $book ) => '' !== $book['read_date'] ) );
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
				self::books_text( $best ),
				'',
				$by_period[ $record ][ $former ]['read_date']
			);
		}

		return $earned;
	}

	/**
	 * The longest run of months in a row with a reading.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array<int,array>
	 */
	private function get_month_streak( array $reads ): array {
		$months = array();

		foreach ( $reads as $book ) {
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
				'',
				end( $longest )
			),
		);
	}

	/**
	 * Year a work first came out: its date first published, or else its edition date.
	 *
	 * @param array $book Book.
	 * @return int|null
	 */
	private static function first_year( array $book ): ?int {
		$date = '' !== $book['published_date'] ? $book['published_date'] : $book['edition_date'];

		return '' !== $date ? (int) substr( $date, 0, 4 ) : null;
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
		$detail = count( $books ) > 1 ? sprintf( __( '%1$s (%2$s)', 'library' ), $description, self::books_text( count( $books ) ) ) : $description;

		return array(
			self::achievement(
				$kind,
				$title,
				$book['title'],
				$detail,
				(string) get_edit_post_link( $book['id'], 'raw' ),
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
	 * A book read first published in each decade of the 20th century.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array{0:array<int,array>,1:array<int,array>} Earned, then close.
	 */
	private function get_decade_tour( array $reads ): array {
		$decades = array();

		foreach ( $reads as $book ) {
			$year = '' !== $book['published_date'] ? (int) substr( $book['published_date'], 0, 4 ) : 0;

			if ( $year >= 1900 && $year < 2000 && ! isset( $decades[ intdiv( $year, 10 ) ] ) ) {
				$decades[ intdiv( $year, 10 ) ] = $book;
			}
		}

		$title = __( 'Tour of the 20th century', 'library' );
		/* translators: %s: number of decades of the 20th century with a book read. */
		$detail = sprintf( __( '%s decades of 10', 'library' ), number_format_i18n( count( $decades ) ) );

		if ( 10 === count( $decades ) ) {
			return array( array( self::achievement( 'decades', $title, '', $detail, '', self::last_read_date( $decades ) ) ), array() );
		}

		if ( count( $decades ) >= self::CLOSE_DECADES ) {
			return array( array(), array( self::achievement( 'decades', $title, '', $detail, '', '' ) + array( 'left' => 10 - count( $decades ) ) ) );
		}

		return array( array(), array() );
	}

	/**
	 * Translators with many books read, and those close to it.
	 *
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array{0:array<int,array>,1:array<int,array>} Earned, then close.
	 */
	private function get_translators( array $reads ): array {
		$translators = array();
		$earned      = array();
		$close       = array();

		foreach ( $reads as $book ) {
			foreach ( $book['translators'] as $translator ) {
				$translators[ $translator ][] = $book;
			}
		}

		foreach ( $translators as $translator => $books ) {
			$left   = self::TRANSLATOR_BOOKS - count( $books );
			$detail = self::books_text( count( $books ) );

			if ( $left <= 0 ) {
				$earned[] = self::achievement( 'translator', __( 'Faithful to a translator', 'library' ), (string) $translator, $detail, self::search_url( (string) $translator ), $books[ self::TRANSLATOR_BOOKS - 1 ]['read_date'] );
			} elseif ( $left <= self::CLOSE_BOOKS ) {
				$close[] = self::achievement( 'translator', __( 'Faithful to a translator', 'library' ), (string) $translator, $detail, self::search_url( (string) $translator ), '' ) + array( 'left' => $left );
			}
		}

		return array( $earned, $close );
	}

	/**
	 * Easter eggs: odd days, odd books and odd habits.
	 *
	 * @param array<int,array> $books All the books.
	 * @param array<int,array> $reads Books read, in reading order.
	 * @return array<int,array>
	 */
	private function get_easter_eggs( array $books, array $reads ): array {
		$dated   = array_values( array_filter( $reads, fn( array $book ) => '' !== $book['read_date'] ) );
		$on      = fn( string $month_day ) => array_values( array_filter( $dated, fn( array $book ) => substr( $book['read_date'], 5 ) === $month_day ) );
		$to_read = count( $books ) - count( $reads );
		$eggs    = array();

		if ( $to_read >= self::TSUNDOKU_BOOKS ) {
			/* translators: %s: number of books to read. */
			$eggs[] = self::achievement( 'tsundoku', __( 'Tsundoku', 'library' ), '', sprintf( __( '%s books waiting to be read', 'library' ), number_format_i18n( $to_read ) ), '', '' );
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
			$this->get_marathon( $dated ),
			$this->get_hat_trick( $dated ),
			$this->get_namesakes( $reads ),
			$this->get_back_to_the_start( $dated )
		);

		return $eggs;
	}

	/**
	 * The latest day with many books read.
	 *
	 * @param array<int,array> $dated Books read with their date, in reading order.
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
		return array( self::achievement( 'marathon', __( 'Marathon', 'library' ), '', sprintf( __( '%s books read the same day', 'library' ), number_format_i18n( count( $marathons[ $day ] ) ) ), '', $day ) );
	}

	/**
	 * Three books rated 10 read in a row.
	 *
	 * @param array<int,array> $dated Books read with their date, in reading order.
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

		return array( self::achievement( 'hat-trick', __( 'Hat-trick', 'library' ), $found['title'], __( 'Three books rated 10 in a row', 'library' ), (string) get_edit_post_link( $found['id'], 'raw' ), $found['read_date'] ) );
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
			$titles[ mb_strtolower( trim( $book['title'] ) ) ][] = $book;
		}

		foreach ( array_reverse( $titles ) as $books ) {
			foreach ( $books as $book ) {
				foreach ( $books as $other ) {
					if ( $book['author_ids'] && $other['author_ids'] && ! array_intersect( $book['author_ids'], $other['author_ids'] ) ) {
						$latest = max( $book['read_date'], $other['read_date'] );

						return array( self::achievement( 'namesakes', __( 'Namesakes', 'library' ), $book['title'], __( 'The same title, different authors', 'library' ), self::search_url( $book['title'] ), $latest ) );
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

			if ( 1 === $book['volume'] && isset( $later[ $book['series'] ] ) ) {
				$found = $book;
			} elseif ( $book['volume'] > 1 ) {
				$later[ $book['series'] ] = true;
			}
		}

		if ( ! $found ) {
			return array();
		}

		return array( self::achievement( 'back-to-the-start', __( 'Back to the start', 'library' ), $found['title'], __( 'Volume 1 read after a later volume', 'library' ), (string) get_edit_post_link( $found['id'], 'raw' ), $found['read_date'] ) );
	}
}
