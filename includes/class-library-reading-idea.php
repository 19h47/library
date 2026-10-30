<?php
/**
 * Reading idea of the week, drawn from the books.
 *
 * @link       https://github.com/19h47/library
 * @since      1.1.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Suggests a book to read each week, among the books at hand, kept or not, and now and then a book to buy.
 *
 * Ideas alternate between kinds, each one with its reason drawn from the data: the next volume of
 * a series in progress, a book by an author, a translator or in a collection rated well, an author
 * never read despite many books, an anniversary of the work or the edition, the opposite of the latest reads, a short
 * book to catch up on last year, a book just added, a book rated well long ago to read again, the
 * rest of the reading pile, and a book to buy that comes next. A volume of a series is only suggested when it is the
 * first one not read yet, to buy or not; once a part of a cycle is read in full, the next part is the next of the cycle.
 *
 * @since      1.1.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Reading_Idea {

	/**
	 * Average rating, out of 10, from which an author, a translator or a collection counts as rated well.
	 *
	 * @var int
	 */
	private const WELL_RATED = 8;

	/**
	 * Number of books rated from which an average counts.
	 *
	 * @var int
	 */
	private const MIN_RATED = 2;

	/**
	 * Years without reading an author rated well from which the reason says when they were last read.
	 *
	 * @var int
	 */
	private const DORMANT_YEARS = 3;

	/**
	 * Number of books by an author from which not having read any of them stands out.
	 *
	 * @var int
	 */
	private const UNREAD_AUTHOR_BOOKS = 3;

	/**
	 * Years between two anniversaries suggested: 10, 20, 30… years.
	 *
	 * @var int
	 */
	private const ANNIVERSARY_YEARS = 10;

	/**
	 * Number of latest reads whose opposite is suggested.
	 *
	 * @var int
	 */
	private const LATEST_READS = 6;

	/**
	 * Number of pages up to which a book is short.
	 *
	 * @var int
	 */
	private const SHORT_BOOK = 200;

	/**
	 * Days during which a book just added is suggested.
	 *
	 * @var int
	 */
	private const FRESH_DAYS = 30;

	/**
	 * Rating, out of 10, from which a book is worth reading again.
	 *
	 * @var int
	 */
	private const REREAD_RATING = 9;

	/**
	 * Years since the latest reading from which a book is worth reading again.
	 *
	 * @var int
	 */
	private const REREAD_YEARS = 10;

	/**
	 * The idea at a position of this week's list, which loops.
	 *
	 * @param int $position Position, 0 for the idea of the week.
	 * @return array{id:int,reason:string,position:int,total:int,week:string}|null Null when there is nothing to suggest.
	 */
	public function get_idea( int $position = 0 ): ?array {
		$ideas = $this->get_ideas();

		if ( ! $ideas ) {
			return null;
		}

		$total    = count( $ideas );
		$position = $position % $total;

		return $ideas[ $position ] + array(
			'position' => $position,
			'total'    => $total,
			'week'     => wp_date( 'o-\WW' ),
		);
	}

	/**
	 * This week's ideas: the kinds taken in turn, from a different one each week, each one shuffled the same way all week.
	 *
	 * A book comes once, with the reason of the first kind suggesting it.
	 * The turn goes over all the kinds, empty ones included, so the kind of the week does not change
	 * when another one empties or fills during the week.
	 *
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_ideas(): array {
		$week    = wp_date( 'o-W' );
		$books   = Library_Books::get_books();
		$to_read = self::get_books_to_read( $books );
		$kinds   = array(
			$this->get_series_ideas( $books, $to_read ),
			$this->get_author_ideas( $books, $to_read ),
			$this->get_unread_author_ideas( $books, $to_read ),
			$this->get_translator_ideas( $books, $to_read ),
			$this->get_collection_ideas( $books, $to_read ),
			$this->get_anniversary_ideas( $to_read ),
			$this->get_change_ideas( $books, $to_read ),
			$this->get_short_ideas( $to_read ),
			$this->get_fresh_ideas( $to_read ),
			$this->get_reread_ideas( $books ),
			$this->get_pile_ideas( $to_read ),
			$this->get_to_buy_ideas( $books ),
		);
		$first   = (int) wp_date( 'W' ) % count( $kinds );
		$kinds   = array_merge( array_slice( $kinds, $first ), array_slice( $kinds, 0, $first ) );

		foreach ( $kinds as &$kind ) {
			usort( $kind, fn( array $a, array $b ) => strcmp( md5( $week . $a['id'] ), md5( $week . $b['id'] ) ) );
		}
		unset( $kind );

		$rounds = max( array_map( 'count', $kinds ) );
		$ideas  = array();

		for ( $round = 0; $round < $rounds; $round++ ) {
			foreach ( $kinds as $kind ) {
				$idea = $kind[ $round ] ?? null;

				if ( $idea && ! isset( $ideas[ $idea['id'] ] ) ) {
					$ideas[ $idea['id'] ] = $idea;
				}
			}
		}

		return array_values( $ideas );
	}

	/**
	 * The books not read yet that can be read now: at hand, i.e. not to buy, and the next ones.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array<int,array>
	 */
	private static function get_books_to_read( array $books ): array {
		return array_filter( self::get_next_books( $books ), fn( array $book ) => Library_Posts::TO_BUY !== $book['status'] );
	}

	/**
	 * The books not read yet that come next: a volume of a series only when it is the first one not read yet, to buy or not.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array<int,array>
	 */
	private static function get_next_books( array $books ): array {
		$first_volumes = array();

		foreach ( $books as $book ) {
			if ( ! $book['read'] && null !== $book['volume'] ) {
				$first_volumes[ $book['series'] ] = min( $book['volume'], $first_volumes[ $book['series'] ] ?? PHP_INT_MAX );
			}
		}

		return array_filter(
			$books,
			fn( array $book ) => ! $book['read'] && ( null === $book['volume'] || $first_volumes[ $book['series'] ] === $book['volume'] )
		);
	}

	/**
	 * Year of a date, e.g. "2021" for "2021-05-03".
	 *
	 * @param string $date Date, Y-m-d.
	 * @return string
	 */
	private static function get_year( string $date ): string {
		return substr( $date, 0, 4 );
	}

	/**
	 * Date of the latest reading of a book, or empty when it has no read date.
	 *
	 * @param array $book Book.
	 * @return string
	 */
	private static function get_latest_read_date( array $book ): string {
		return (string) end( $book['read_dates'] );
	}

	/**
	 * The next volume of each series with at least one volume read, and the first one of the part of a cycle that comes next.
	 *
	 * @param array<int,array> $books   All the books.
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_series_ideas( array $books, array $to_read ): array {
		$read_by_series = self::get_read_by_series( $books );
		$next_parts     = self::get_next_parts( $books );
		$ideas          = array();

		foreach ( $to_read as $book ) {
			$read = $read_by_series[ $book['series'] ] ?? $next_parts[ $book['series'] ] ?? null;

			if ( ! $read || null === $book['volume'] ) {
				continue;
			}

			// The next part of a cycle is next in the cycle.
			$series  = $next_parts[ $book['series'] ]['cycle'] ?? $book['series'];
			$volumes = number_format_i18n( $read['volumes'] );
			$reason  = '' === $read['latest_read']
				? sprintf(
					/* translators: 1: series, 2: number of volumes read. */
					_n( 'Next in %1$s: you have read %2$s volume.', 'Next in %1$s: you have read %2$s volumes.', $read['volumes'], 'library' ),
					$series,
					$volumes
				)
				: sprintf(
					/* translators: 1: series, 2: number of volumes read, 3: year of the last one read. */
					_n( 'Next in %1$s: you have read %2$s volume, in %3$s.', 'Next in %1$s: you have read %2$s volumes, the last one in %3$s.', $read['volumes'], 'library' ),
					$series,
					$volumes,
					self::get_year( $read['latest_read'] )
				);

			$ideas[] = array(
				'id'     => $book['id'],
				'reason' => $reason,
			);
		}

		return $ideas;
	}

	/**
	 * The volumes read of each series with at least one, and the date of the last one read.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array<string,array{volumes:int,latest_read:string}>
	 */
	private static function get_read_by_series( array $books ): array {
		$read_by_series = array();

		foreach ( $books as $book ) {
			if ( ! $book['read'] || null === $book['volume'] ) {
				continue;
			}

			$read = $read_by_series[ $book['series'] ] ?? array(
				'volumes'     => 0,
				'latest_read' => '',
			);

			$read_by_series[ $book['series'] ] = array(
				'volumes'     => $read['volumes'] + 1,
				'latest_read' => max( $read['latest_read'], self::get_latest_read_date( $book ) ),
			);
		}

		return $read_by_series;
	}

	/**
	 * The parts of cycles that come next, keyed by their series, with their cycle, the volumes read in it and the date of
	 * the last one read: a part none of whose books is read, after a part read in full, in reading order.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array<string,array{cycle:string,volumes:int,latest_read:string}>
	 */
	private static function get_next_parts( array $books ): array {
		$parts = array();

		foreach ( Library_Books::sort_in_reading_order( $books ) as $book ) {
			$cycle = Library_Books::get_cycle( $book['series'] );

			if ( '' !== $cycle ) {
				$parts[ $cycle ][ $book['series'] ][] = $book;
			}
		}

		$next_parts = array();

		foreach ( $parts as $cycle => $cycle_parts ) {
			// The volumes read in the cycle, counted as those of a series.
			$cycle_books = array_map( fn( array $book ) => array( 'series' => $cycle ) + $book, array_merge( ...array_values( $cycle_parts ) ) );
			$read        = self::get_read_by_series( $cycle_books )[ $cycle ] ?? null;
			$before      = array();

			foreach ( $cycle_parts as $series => $part ) {
				$read_before = $before && ! in_array( false, array_column( $before, 'read' ), true );

				if ( $read && $read_before && ! in_array( true, array_column( $part, 'read' ), true ) ) {
					$next_parts[ $series ] = array( 'cycle' => $cycle ) + $read;
				}

				$before = $part;
			}
		}

		return $next_parts;
	}

	/**
	 * Books to read with a name rated well in a field: the best rated name gives the reason.
	 *
	 * @param array<int,array> $books   All the books.
	 * @param array<int,array> $to_read Books to read.
	 * @param string           $field   Field listing the names: authors, translators or collections.
	 * @param callable         $reason  Reason, from the name and its average, number of books rated and latest read date.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private static function get_rated_well_ideas( array $books, array $to_read, string $field, callable $reason ): array {
		$ratings     = array();
		$latest_read = array();

		foreach ( $books as $book ) {
			foreach ( $book[ $field ] as $name ) {
				if ( null !== $book['rating'] ) {
					$ratings[ $name ][] = $book['rating'];
				}

				$latest_read[ $name ] = max( $latest_read[ $name ] ?? '', self::get_latest_read_date( $book ) );
			}
		}

		$rated_well = array();

		foreach ( $ratings as $name => $values ) {
			$rated   = count( $values );
			$average = array_sum( $values ) / $rated;

			if ( $rated >= self::MIN_RATED && $average >= self::WELL_RATED ) {
				$rated_well[ $name ] = array(
					'average'     => $average,
					'rated'       => $rated,
					'latest_read' => $latest_read[ $name ],
				);
			}
		}

		uasort( $rated_well, fn( array $a, array $b ) => $b['average'] <=> $a['average'] );

		$ideas = array();

		foreach ( $to_read as $book ) {
			$names = array_intersect_key( $rated_well, array_flip( $book[ $field ] ) );
			$name  = array_key_first( $names );

			if ( null !== $name ) {
				$ideas[] = array(
					'id'     => $book['id'],
					'reason' => $reason( (string) $name, $names[ $name ] ),
				);
			}
		}

		return $ideas;
	}

	/**
	 * Books to read by the authors rated well, and when they were last read if it was long ago.
	 *
	 * @param array<int,array> $books   All the books.
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_author_ideas( array $books, array $to_read ): array {
		$dormant_year = (int) wp_date( 'Y' ) - self::DORMANT_YEARS;

		return self::get_rated_well_ideas(
			$books,
			$to_read,
			'authors',
			function ( string $author, array $stats ) use ( $dormant_year ): string {
				$average   = Library_Books::format_rating( $stats['average'] );
				$rated     = number_format_i18n( $stats['rated'] );
				$read_year = self::get_year( $stats['latest_read'] );

				if ( '' !== $read_year && (int) $read_year <= $dormant_year ) {
					return sprintf(
						/* translators: 1: author, 2: average rating, 3: number of books rated, 4: year of the last one read. */
						__( 'You gave %1$s %2$s/10 on average over %3$s books, the last one read in %4$s.', 'library' ),
						$author,
						$average,
						$rated,
						$read_year
					);
				}

				return sprintf(
					/* translators: 1: author, 2: average rating, 3: number of books rated. */
					__( 'You gave %1$s %2$s/10 on average over %3$s books.', 'library' ),
					$author,
					$average,
					$rated
				);
			}
		);
	}

	/**
	 * Books to read by the authors with many books at hand and none read.
	 *
	 * @param array<int,array> $books   All the books.
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_unread_author_ideas( array $books, array $to_read ): array {
		$books_by_author = array();
		$read_authors    = array();

		foreach ( $books as $book ) {
			foreach ( $book['authors'] as $author ) {
				if ( Library_Posts::TO_BUY !== $book['status'] ) {
					$books_by_author[ $author ] = ( $books_by_author[ $author ] ?? 0 ) + 1;
				}

				if ( $book['read'] ) {
					$read_authors[ $author ] = true;
				}
			}
		}

		$ideas = array();

		foreach ( $to_read as $book ) {
			foreach ( $book['authors'] as $author ) {
				if ( isset( $read_authors[ $author ] ) || $books_by_author[ $author ] < self::UNREAD_AUTHOR_BOOKS ) {
					continue;
				}

				$ideas[] = array(
					'id'     => $book['id'],
					'reason' => sprintf(
						/* translators: 1: author, 2: number of books. */
						__( 'You have %2$s books by %1$s, and none read.', 'library' ),
						$author,
						number_format_i18n( $books_by_author[ $author ] )
					),
				);

				break;
			}
		}

		return $ideas;
	}

	/**
	 * Books to read translated by the translators rated well.
	 *
	 * @param array<int,array> $books   All the books.
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_translator_ideas( array $books, array $to_read ): array {
		return self::get_rated_well_ideas(
			$books,
			$to_read,
			'translators',
			fn( string $translator, array $stats ): string => sprintf(
				/* translators: 1: translator, 2: average rating, 3: number of books rated. */
				__( 'Translated by %1$s: %2$s/10 on average over %3$s books rated.', 'library' ),
				$translator,
				Library_Books::format_rating( $stats['average'] ),
				number_format_i18n( $stats['rated'] )
			)
		);
	}

	/**
	 * Books to read in the collections rated well.
	 *
	 * @param array<int,array> $books   All the books.
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_collection_ideas( array $books, array $to_read ): array {
		return self::get_rated_well_ideas(
			$books,
			$to_read,
			'collections',
			fn( string $collection, array $stats ): string => sprintf(
				/* translators: 1: collection, e.g. "Folio", 2: average rating, 3: number of books rated. */
				__( 'In %1$s: %2$s/10 on average over %3$s books rated.', 'library' ),
				$collection,
				Library_Books::format_rating( $stats['average'] ),
				number_format_i18n( $stats['rated'] )
			)
		);
	}

	/**
	 * Books to read first published, or else whose edition came out, this month a round number of years ago.
	 *
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_anniversary_ideas( array $to_read ): array {
		$ideas = array();

		foreach ( $to_read as $book ) {
			$work_years    = self::get_anniversary( $book['published_date'] );
			$edition_years = self::get_anniversary( $book['edition_date'] );

			if ( $work_years ) {
				/* translators: %s: number of years. */
				$reason = sprintf( __( 'First published %s years ago this month.', 'library' ), number_format_i18n( $work_years ) );
			} elseif ( $edition_years ) {
				/* translators: %s: number of years. */
				$reason = sprintf( __( 'This edition came out %s years ago this month.', 'library' ), number_format_i18n( $edition_years ) );
			} else {
				continue;
			}

			$ideas[] = array(
				'id'     => $book['id'],
				'reason' => $reason,
			);
		}

		return $ideas;
	}

	/**
	 * Years since a date that falls this month a round number of years ago.
	 *
	 * A first of January often stands for a date known by its year only, so it is left out.
	 *
	 * @param string $date Date, Y-m-d, or Y-m or Y for a first published date, or empty.
	 * @return int|null Null when the date is not an anniversary this month, or has no month.
	 */
	private static function get_anniversary( string $date ): ?int {
		$month_day = substr( $date, 5 );
		$years     = (int) wp_date( 'Y' ) - (int) self::get_year( $date );

		if ( '01-01' === $month_day || ! str_starts_with( $month_day, wp_date( 'm' ) ) || $years < self::ANNIVERSARY_YEARS || 0 !== $years % self::ANNIVERSARY_YEARS ) {
			return null;
		}

		return $years;
	}

	/**
	 * Books to read unlike the latest reads: translated or not, volume of a series or not,
	 * and first published in a decade with nothing read.
	 *
	 * @param array<int,array> $books   All the books.
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_change_ideas( array $books, array $to_read ): array {
		// A book read again counts once per reading.
		$readings = array();

		foreach ( $books as $book ) {
			foreach ( $book['read_dates'] as $read_date ) {
				$readings[] = array( 'read_date' => $read_date ) + $book;
			}
		}

		usort( $readings, fn( array $a, array $b ) => strcmp( $b['read_date'], $a['read_date'] ) );

		$latest_reads = array_slice( $readings, 0, self::LATEST_READS );
		$ideas        = $this->get_decade_ideas( $books, $to_read );

		if ( count( $latest_reads ) < self::LATEST_READS ) {
			return $ideas;
		}

		$reads = number_format_i18n( self::LATEST_READS );

		return array_merge(
			$ideas,
			self::get_opposite_ideas(
				$latest_reads,
				$to_read,
				fn( array $book ) => (bool) $book['translators'],
				/* translators: %s: number of reads. */
				sprintf( __( 'Your last %s reads were all translated.', 'library' ), $reads ),
				/* translators: %s: number of reads. */
				sprintf( __( 'None of your last %s reads was translated.', 'library' ), $reads )
			),
			self::get_opposite_ideas(
				$latest_reads,
				$to_read,
				fn( array $book ) => null !== $book['volume'],
				/* translators: %s: number of reads. */
				sprintf( __( 'Your last %s reads were all volumes of a series.', 'library' ), $reads ),
				/* translators: %s: number of reads. */
				sprintf( __( 'None of your last %s reads was a volume of a series.', 'library' ), $reads )
			)
		);
	}

	/**
	 * Books to read without a trait when the latest reads all have it, and with it when none of them has it.
	 *
	 * @param array<int,array> $latest_reads Latest reads.
	 * @param array<int,array> $to_read      Books to read.
	 * @param callable         $has_trait    Whether a book has the trait.
	 * @param string           $all_reason   Reason when the latest reads all have the trait.
	 * @param string           $none_reason  Reason when none of them has it.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private static function get_opposite_ideas( array $latest_reads, array $to_read, callable $has_trait, string $all_reason, string $none_reason ): array {
		$with_trait = count( array_filter( $latest_reads, $has_trait ) );

		if ( count( $latest_reads ) === $with_trait ) {
			$wanted = false;
			$reason = $all_reason;
		} elseif ( 0 === $with_trait ) {
			$wanted = true;
			$reason = $none_reason;
		} else {
			return array();
		}

		$ideas = array();

		foreach ( $to_read as $book ) {
			if ( $has_trait( $book ) === $wanted ) {
				$ideas[] = array(
					'id'     => $book['id'],
					'reason' => $reason,
				);
			}
		}

		return $ideas;
	}

	/**
	 * Books to read first published in a decade with no book read.
	 *
	 * @param array<int,array> $books   All the books.
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_decade_ideas( array $books, array $to_read ): array {
		$decade       = fn( array $book ) => intdiv( (int) self::get_year( $book['published_date'] ), 10 ) * 10;
		$read_decades = array();

		foreach ( $books as $book ) {
			if ( $book['read'] && '' !== $book['published_date'] ) {
				$read_decades[ $decade( $book ) ] = true;
			}
		}

		$ideas = array();

		foreach ( $to_read as $book ) {
			if ( '' === $book['published_date'] || isset( $read_decades[ $decade( $book ) ] ) ) {
				continue;
			}

			$ideas[] = array(
				'id'     => $book['id'],
				'reason' => sprintf(
					/* translators: %d: decade, e.g. 1960. */
					__( 'You have not read any book first published in the %ds yet.', 'library' ),
					$decade( $book )
				),
			);
		}

		return $ideas;
	}

	/**
	 * Short books to read, when fewer books are read this year than by the same date last year.
	 *
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_short_ideas( array $to_read ): array {
		$pace = Library_Stats::get_reading_year_to_date();

		if ( $pace['this_year'] >= $pace['last_year'] ) {
			return array();
		}

		$ideas = array();

		foreach ( $to_read as $book ) {
			if ( null === $book['pages'] || $book['pages'] > self::SHORT_BOOK ) {
				continue;
			}

			$ideas[] = array(
				'id'     => $book['id'],
				'reason' => sprintf(
					/* translators: 1: number of pages, 2: number of books read this year, 3: number of books read by the same date last year. */
					_n( '%1$s pages, to catch up: %2$s book read this year against %3$s by this date last year.', '%1$s pages, to catch up: %2$s books read this year against %3$s by this date last year.', $pace['this_year'], 'library' ),
					number_format_i18n( $book['pages'] ),
					number_format_i18n( $pace['this_year'] ),
					number_format_i18n( $pace['last_year'] )
				),
			);
		}

		return $ideas;
	}

	/**
	 * Books to read added lately, while the wish to read them is still fresh.
	 *
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_fresh_ideas( array $to_read ): array {
		$since = time() - self::FRESH_DAYS * DAY_IN_SECONDS;
		$ideas = array();

		foreach ( $to_read as $book ) {
			$added = get_post_timestamp( $book['id'] );

			if ( $added < $since ) {
				continue;
			}

			$ideas[] = array(
				'id'     => $book['id'],
				'reason' => sprintf(
					/* translators: %s: time since the book was added, e.g. "12 days". */
					__( 'Added %s ago.', 'library' ),
					human_time_diff( $added )
				),
			);
		}

		return $ideas;
	}

	/**
	 * Books last read long ago and rated well, to read again.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_reread_ideas( array $books ): array {
		$latest_year = (int) wp_date( 'Y' ) - self::REREAD_YEARS;
		$ideas       = array();

		foreach ( $books as $book ) {
			$read_year = self::get_year( self::get_latest_read_date( $book ) );

			if ( '' === $read_year || (int) $read_year > $latest_year || null === $book['rating'] || $book['rating'] < self::REREAD_RATING ) {
				continue;
			}

			$ideas[] = array(
				'id'     => $book['id'],
				'reason' => sprintf(
					/* translators: 1: rating, 2: year read. */
					__( 'Rated %1$s/10, read in %2$s: read it again?', 'library' ),
					Library_Books::format_rating( $book['rating'] ),
					$read_year
				),
			);
		}

		return $ideas;
	}

	/**
	 * All the other books to read, with the year they were added.
	 *
	 * @param array<int,array> $to_read Books to read.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_pile_ideas( array $to_read ): array {
		$ideas = array();

		foreach ( $to_read as $book ) {
			if ( Library_Posts::NOT_KEPT === $book['status'] ) {
				$reason = __( 'Not kept, still to read: borrowed?', 'library' );
			} else {
				/* translators: %s: year the book was added. */
				$reason = sprintf( __( 'Waiting on the reading pile since %s.', 'library' ), self::get_year( $book['added'] ) );
			}

			$ideas[] = array(
				'id'     => $book['id'],
				'reason' => $reason,
			);
		}

		return $ideas;
	}

	/**
	 * Books to buy that come next: the next volume of a series with a volume read, else a book by an author rated well.
	 *
	 * @param array<int,array> $books All the books.
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_to_buy_ideas( array $books ): array {
		$to_buy         = array_filter( self::get_next_books( $books ), fn( array $book ) => Library_Posts::TO_BUY === $book['status'] );
		$read_by_series = self::get_read_by_series( $books );
		$ideas          = array();

		foreach ( $to_buy as $book ) {
			$read = $read_by_series[ $book['series'] ] ?? null;

			if ( ! $read || null === $book['volume'] ) {
				continue;
			}

			$ideas[ $book['id'] ] = array(
				'id'     => $book['id'],
				'reason' => sprintf(
					/* translators: 1: series, 2: number of volumes read. */
					_n( 'To buy, next in %1$s: you have read %2$s volume.', 'To buy, next in %1$s: you have read %2$s volumes.', $read['volumes'], 'library' ),
					$book['series'],
					number_format_i18n( $read['volumes'] )
				),
			);
		}

		$author_ideas = self::get_rated_well_ideas(
			$books,
			$to_buy,
			'authors',
			fn( string $author, array $stats ): string => sprintf(
				/* translators: 1: author, 2: average rating, 3: number of books rated. */
				__( 'To buy, by %1$s: you gave them %2$s/10 on average over %3$s books.', 'library' ),
				$author,
				Library_Books::format_rating( $stats['average'] ),
				number_format_i18n( $stats['rated'] )
			)
		);

		return array_values( $ideas + array_column( $author_ideas, null, 'id' ) );
	}
}
