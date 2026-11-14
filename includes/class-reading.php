<?php
/**
 * A reading of a book: its start and its end, each empty when unknown, its end IN_PROGRESS while it is read, and the
 * rating it ended with, none without. One row of the meta library_reading each, as JSON, and an object of the REST API,
 * {"start":"2026-10-20","end":"2026-11-02","rating":8}, an unknown start or end and no rating null.
 *
 * @link       https://github.com/19h47/library
 * @since      9.46.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * A reading of a book.
 *
 * @phpstan-type Reading_Data array{start: string|null, end: string|null, rating: float|null}
 */
final class Reading {
	/**
	 * The end of a reading in progress, as an interval of ISO 8601 open at its end: 2026-11-08/..
	 */
	public const string IN_PROGRESS = '..';

	/**
	 * A reading.
	 *
	 * @param string     $start  Its start, Y-m-d or with its time, empty when unknown.
	 * @param string     $end    Its end, Y-m-d or with its time, empty when unknown, IN_PROGRESS while it is read.
	 * @param float|null $rating The rating it ended with, out of 10, null without; none while it is read.
	 */
	public function __construct(
		public readonly string $start = '',
		public readonly string $end = '',
		public readonly ?float $rating = null,
	) {}

	/**
	 * A reading from the object the REST API gives, or a row of the meta, its JSON: an unknown start or end is null or
	 * empty, and anything else a reading of unknown dates without rating. It is not sanitized: see Meta::sanitize_reading().
	 *
	 * @param mixed $value The reading, the object, as an array, or its JSON.
	 * @return self
	 */
	public static function from( $value ): self {
		if ( $value instanceof self ) {
			return $value;
		}

		$data = is_string( $value ) ? json_decode( $value, true ) : $value;

		if ( ! is_array( $data ) ) {
			return new self();
		}

		$rating = $data['rating'] ?? null;

		return new self(
			is_string( $data['start'] ?? null ) ? $data['start'] : '',
			is_string( $data['end'] ?? null ) ? $data['end'] : '',
			is_numeric( $rating ) ? (float) $rating : null
		);
	}

	/**
	 * A copy of the reading with other values, such as its end: `$reading->with( end: '2026-11-02' )`.
	 *
	 * @param string|null $start  Its start, or the same.
	 * @param string|null $end    Its end, or the same.
	 * @param float|null  $rating Its rating, or the same; see without_rating() to take it away.
	 * @return self
	 */
	public function with( ?string $start = null, ?string $end = null, ?float $rating = null ): self {
		return new self( $start ?? $this->start, $end ?? $this->end, $rating ?? $this->rating );
	}

	/**
	 * A copy of the reading without its rating.
	 *
	 * @return self
	 */
	public function without_rating(): self {
		return new self( $this->start, $this->end );
	}

	/**
	 * Whether it is ended, of a known end or not: not in progress.
	 *
	 * @return bool
	 */
	public function is_ended(): bool {
		return self::IN_PROGRESS !== $this->end;
	}

	/**
	 * The object of the REST API: an unknown start or end and no rating null.
	 *
	 * @return Reading_Data
	 */
	public function to_array(): array {
		return array(
			'start'  => '' !== $this->start ? $this->start : null,
			'end'    => '' !== $this->end ? $this->end : null,
			'rating' => $this->rating,
		);
	}

	/**
	 * Its row of the meta library_reading: the object of the REST API as JSON.
	 *
	 * @return string
	 */
	public function to_json(): string {
		return (string) wp_json_encode( $this->to_array() );
	}
}
