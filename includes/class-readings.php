<?php
/**
 * The readings of a reader: their order, their rating, whether they are read and being read, and what a form gives of
 * them.
 *
 * @link       https://github.com/19h47/library
 * @since      10.0.1
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * Works out what a list of readings of a book says, and makes the readings a form gives the ones to keep: the rules of
 * the readings, which Shelf, where each reader keeps theirs, applies.
 *
 * @since      10.0.1
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
final class Readings {

	/**
	 * Format of the start or the end of a reading with its time, which sorts after the day alone: 2026-11-01T21:40. A
	 * reading started or ended today takes its time, so that the readings of a day follow one another.
	 *
	 * @var string
	 */
	public const string READING_TIME_FORMAT = 'Y-m-d\TH:i';

	/**
	 * Pattern of the start or the end of a reading, its day alone or with its time: 2026-11-02, 2026-11-08T09:15.
	 *
	 * @var string
	 */
	public const string READING_DATE_PATTERN = '^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$';

	/**
	 * Limits of a rating, which its schema states and sanitize_rating() keeps: from 1 to 10, by half points.
	 *
	 * @var array{minimum:int,maximum:int,multipleOf:float}
	 */
	public const array RATING_LIMITS = array(
		'minimum'    => 1,
		'maximum'    => 10,
		'multipleOf' => 0.5,
	);

	/**
	 * The rating of a book, the one of its latest reading rated, null without: a reading read again without rating leaves
	 * the one before.
	 *
	 * @param Reading[] $readings Readings, from the first to the latest.
	 * @return float|null
	 */
	public static function get_rating( array $readings ): ?float {
		$rated = array_filter( self::get_ended( $readings ), fn( Reading $reading ) => null !== $reading->rating );

		return $rated ? end( $rated )->rating : null;
	}

	/**
	 * The readings ended, the one in progress left out.
	 *
	 * @param Reading[] $readings Readings.
	 * @return Reading[]
	 */
	public static function get_ended( array $readings ): array {
		return array_values( array_filter( $readings, fn( Reading $reading ) => $reading->is_ended() ) );
	}

	/**
	 * The reading in progress, null without.
	 *
	 * @param Reading[] $readings Readings.
	 * @return Reading|null
	 */
	public static function get_in_progress( array $readings ): ?Reading {
		return array_find( $readings, fn( Reading $reading ) => ! $reading->is_ended() );
	}

	/**
	 * Readings from the first to the latest: the ones ended by their end, then by their start, each one of an unknown end a
	 * reading of its own, first, and one of a known end once, as one saved twice by an old import; then the one in progress,
	 * the latest started alone.
	 *
	 * @param Reading[] $readings Readings.
	 * @return Reading[]
	 */
	public static function sort( array $readings ): array {
		$ended       = self::get_ended( $readings );
		$unknown     = array_filter( $ended, fn( Reading $reading ) => '' === $reading->end );
		$known       = array_diff_key( $ended, $unknown );
		$known       = array_intersect_key( $known, array_unique( array_map( fn( Reading $reading ) => $reading->to_json(), $known ) ) );
		$ended       = array( ...array_values( $unknown ), ...array_values( $known ) );
		$in_progress = array_values( array_filter( $readings, fn( Reading $reading ) => ! $reading->is_ended() ) );

		usort( $ended, fn( Reading $a, Reading $b ) => array( $a->end, $a->start ) <=> array( $b->end, $b->start ) );
		usort( $in_progress, fn( Reading $a, Reading $b ) => $a->start <=> $b->start );

		return $in_progress ? array( ...$ended, end( $in_progress ) ) : $ended;
	}

	/**
	 * Readings with the rating of the latest one ended replaced, none for an empty one: the rating of a book is the one of
	 * its latest reading. A book without reading ended has none to rate.
	 *
	 * @param Reading[]    $readings Readings, from the first to the latest.
	 * @param float|string $rating   Rating, sanitized, empty for none.
	 * @return Reading[]
	 */
	public static function rate_latest( array $readings, float|string $rating ): array {
		$latest = array_key_last( array_filter( $readings, fn( Reading $reading ) => $reading->is_ended() ) );

		if ( null === $latest ) {
			return $readings;
		}

		$readings[ $latest ] = '' === $rating ? $readings[ $latest ]->without_rating() : $readings[ $latest ]->with( rating: (float) $rating );

		return $readings;
	}

	/**
	 * Readings given, sanitized, with the times of the ones saved: a form gives the day of a start or an end, and one of a
	 * day saved with its time keeps it, once, while one of today takes the time now, which orders the readings of a day.
	 * An invalid start or end is an unknown one.
	 *
	 * @param Reading[]    $saved Readings saved.
	 * @param array<mixed> $given Readings given, as objects of the REST API, their starts and ends by their days, or with their times.
	 * @return Reading[]
	 */
	public static function keep_times( array $saved, array $given ): array {
		$given = array_map( fn( $reading ): Reading => self::clean( Reading::from( $reading ) ), $given );
		$parts = fn( Reading $reading ): array => array( $reading->start, $reading->end );
		// The starts and the ends saved that a day given alone may stand for: not the ones given whole, with their time.
		$halves = array_map(
			fn( int $index ): array => array_diff( array_column( array_map( $parts, $saved ), $index ), array_column( array_map( $parts, $given ), $index ) ),
			array( 0, 1 )
		);
		$today  = current_time( 'Y-m-d' );
		$now    = current_time( self::READING_TIME_FORMAT );

		return array_map(
			function ( Reading $reading ) use ( &$halves, $parts, $today, $now ): Reading {
				$dates = $parts( $reading );

				foreach ( $dates as $index => $date ) {
					if ( 10 !== strlen( $date ) ) {
						continue;
					}

					$found = array_find_key( $halves[ $index ], fn( string $saved ) => str_starts_with( $saved, $date ) );

					if ( null !== $found ) {
						$dates[ $index ] = $halves[ $index ][ $found ];
						unset( $halves[ $index ][ $found ] );
					} elseif ( $today === $date ) {
						$dates[ $index ] = $now;
					}
				}

				return self::clean( new Reading( $dates[0], $dates[1], $reading->rating ) );
			},
			$given
		);
	}

	/**
	 * Readings as whether the book is read and being read say, when given: marked as unread, a book loses its readings
	 * ended; marked as read without one, it is read now, from the start of the one in progress; read already and its
	 * readings all removed, it has one of an unknown date, unless it is marked as unread. Marked as being read, it starts a
	 * reading now, unless one is in progress; marked as not, the one in progress goes. A reading added that ends after the
	 * one in progress started, such as one ended today, ends it, which gives it its start when it has none: an older one,
	 * added to the ones before, leaves it.
	 *
	 * @param Reading[]           $before   Readings saved.
	 * @param Reading[]           $readings Readings given, or saved.
	 * @param array<string,mixed> $values   Values given, library_read and library_in_progress among them.
	 * @return Reading[]
	 */
	public static function apply_state( array $before, array $readings, array $values ): array {
		$now         = current_time( self::READING_TIME_FORMAT );
		$ended       = self::get_ended( $readings );
		$in_progress = self::get_in_progress( $readings );
		$ends        = fn( array $readings ): array => array_map( fn( Reading $reading ) => $reading->end, self::get_ended( $readings ) );
		$started     = null !== $in_progress ? $in_progress->start : '';
		$ending      = array_filter( self::get_added( $ends( $before ), $ends( $readings ) ), fn( string $end ) => '' !== $end && substr( $end, 0, 10 ) >= substr( $started, 0, 10 ) );

		if ( null !== $in_progress && $ending ) {
			$end   = max( $ending );
			$index = array_find_key( $ended, fn( Reading $reading ) => '' === $reading->start && $end === $reading->end );

			if ( null !== $index ) {
				$ended[ $index ] = $ended[ $index ]->with( start: $started );
			}

			$in_progress = null;
		}

		$was_read = (bool) self::get_ended( $before );
		$is_read  = array_key_exists( 'library_read', $values ) ? rest_sanitize_boolean( $values['library_read'] ) : $was_read || $ended;

		if ( ! $is_read ) {
			$ended = array();
		} elseif ( ! $ended && $was_read ) {
			$ended = array( new Reading() );
		} elseif ( ! $ended ) {
			$ended       = array( new Reading( $started, $now ) );
			$in_progress = null;
		}

		if ( array_key_exists( 'library_in_progress', $values ) ) {
			$is_in_progress = rest_sanitize_boolean( $values['library_in_progress'] );

			if ( ! $is_in_progress ) {
				$in_progress = null;
			} elseif ( null === $in_progress ) {
				$in_progress = new Reading( $now, Reading::IN_PROGRESS );
			}
		}

		return null !== $in_progress ? array( ...$ended, $in_progress ) : $ended;
	}

	/**
	 * Read dates one list has and the other has not: a date more, known or not, such as a second unknown one.
	 *
	 * @param string[] $before Read dates before.
	 * @param string[] $after  Read dates after.
	 * @return string[]
	 */
	private static function get_added( array $before, array $after ): array {
		$counts = array_count_values( $before );
		$added  = array();

		foreach ( $after as $read_date ) {
			if ( ( $counts[ $read_date ] ?? 0 ) > 0 ) {
				--$counts[ $read_date ];
				continue;
			}

			$added[] = $read_date;
		}

		return $added;
	}

	/**
	 * Keep a valid reading, as the row of the meta its JSON is: see clean_reading().
	 *
	 * @param mixed $value Raw value: its JSON, or the object of the REST API.
	 * @return string
	 */
	public static function sanitize( $value ): string {
		return self::clean( Reading::from( $value ) )->to_json();
	}

	/**
	 * A valid reading: a start or an end invalid is an unknown one, a start after the end, mistyped, too; a rating off
	 * RATING_LIMITS is none, and a reading in progress has none yet.
	 *
	 * @param Reading $reading Reading.
	 * @return Reading
	 */
	private static function clean( Reading $reading ): Reading {
		$start  = self::sanitize_date( $reading->start );
		$end    = $reading->is_ended() ? self::sanitize_date( $reading->end ) : Reading::IN_PROGRESS;
		$rating = $reading->is_ended() ? self::sanitize_rating( $reading->rating ?? '' ) : '';

		if ( '' !== $start && '' !== $end && Reading::IN_PROGRESS !== $end && substr( $start, 0, 10 ) > substr( $end, 0, 10 ) ) {
			$start = '';
		}

		return new Reading( $start, $end, '' === $rating ? null : (float) $rating );
	}

	/**
	 * Keep a valid start or end of a reading, its day alone or with its time, as READING_TIME_FORMAT; anything else becomes
	 * empty, an unknown one.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function sanitize_date( string $value ): string {
		$format = 10 === strlen( $value ) ? 'Y-m-d' : self::READING_TIME_FORMAT;

		return Meta::is_date( $value, $format ) ? $value : '';
	}

	/**
	 * Keep a rating within RATING_LIMITS, from 1 to 10, rounded to its step, half points; anything else becomes empty.
	 *
	 * @param mixed $value Raw value.
	 * @return float|string
	 */
	public static function sanitize_rating( $value ): float|string {
		$limits = self::RATING_LIMITS;

		if ( ! is_numeric( $value ) || $value < $limits['minimum'] || $value > $limits['maximum'] ) {
			return '';
		}

		return round( (float) $value / $limits['multipleOf'] ) * $limits['multipleOf'];
	}
}
