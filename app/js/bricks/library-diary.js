import { __ } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { renderBook } from 'library-app/render';
import { clone, fill } from 'library-app/dom';
import {
	followCards,
	formatListStatus,
	observeEnd,
} from 'library-app/behaviors';
import { urlOf } from 'library-app/router';
import {
	formatBooks,
	formatDate,
	formatRating,
	formatRecap,
	join,
} from 'library-app/format';

/**
 * @import { Book, Month } from 'library-app/api'
 */

/**
 * The readings by month, latest first: a book read again comes once per reading. The months of a year gone by follow
 * the link to its recap.
 */
class LibraryDiaryElement extends Brick {
	connect( signal ) {
		const $months = this.querySelector( '[data-list="months"]' );
		const observe = observeEnd(
			this.querySelector( '[data-part="more"]' ),
			signal,
			() => store.loadMoreDiary()
		);

		return [
			followCards( $months, ( book, $item ) =>
				this.#state( book, $item.dataset.date )
			),
			store.select(
				( { diary } ) =>
					formatListStatus(
						diary,
						__( 'No readings yet.', 'library' )
					),
				( message ) => fill( this, { status: message } )
			),
			store.select(
				( state ) => state.diary.items,
				( months ) => {
					const $cards = /** @type {HTMLElement[]} */ ( [
						...$months.querySelectorAll( '[data-id]' ),
					] );
					const $focused = $cards.find( ( $item ) =>
						$item.contains( this.ownerDocument.activeElement )
					);

					$months.replaceChildren( ...this.#renderMonths( months ) );

					if ( $focused ) {
						this.#refocus( $focused, $cards.indexOf( $focused ) );
					}

					observe();
				}
			),
		];
	}

	/**
	 * Gives the focus back to the reading of the card `$focused` once the months are drawn again, as the next page comes
	 * or a book saved changes its readings; a reading removed leaves it to the one at its place.
	 *
	 * @param {HTMLElement} $focused The card that had the focus.
	 * @param {number}      index    Its place among the cards.
	 */
	#refocus( $focused, index ) {
		const { id, date } = $focused.dataset;
		const $cards = /** @type {HTMLElement[]} */ (
			Array.from( this.querySelectorAll( '[data-id]' ) )
		);
		const $card =
			$cards.find(
				( $item ) =>
					id === $item.dataset.id && date === $item.dataset.date
			) ?? $cards[ Math.min( index, $cards.length - 1 ) ];

		/** @type {HTMLElement} */ (
			$card?.querySelector( '[data-part="link"]' )
		)?.focus();
	}

	/**
	 * A section per month, with the number of its readings: a page of the diary holds whole months. Before the latest
	 * month of a year gone by, the link to its recap.
	 *
	 * @param {Month[]} months The months.
	 * @return {HTMLElement[]} Their sections, and the links to the recaps.
	 */
	#renderMonths( months ) {
		const { books } = store.get();
		const thisYear = new Date().getFullYear();

		return months.flatMap( ( { month, readings }, index ) => {
			const year = Number( month.slice( 0, 4 ) );
			const isLatestOfYear =
				! index ||
				! months[ index - 1 ].month.startsWith( `${ year }-` );
			const $month = clone( 'library-month' );
			// A reading shows with its book only.
			const shown = readings.filter( ( reading ) => reading.id in books );

			fill( $month, {
				month: formatDate( month ),
				count: formatBooks( readings.length ),
			} );
			$month
				.querySelector( 'ul' )
				.append(
					...shown.map( ( { date, id } ) =>
						this.#renderReading( books[ id ], date )
					)
				);

			return isLatestOfYear && year < thisYear
				? [ this.#renderRecap( year ), $month ]
				: [ $month ];
		} );
	}

	/**
	 * The link to the recap of `year`.
	 *
	 * @param {number} year The year.
	 * @return {HTMLElement} Its paragraph.
	 */
	#renderRecap( year ) {
		const $recap = clone( 'library-diary-recap' );

		/** @type {HTMLAnchorElement} */ ( $recap.querySelector( 'a' ) ).href =
			urlOf( { view: 'recap', id: year } );
		fill( $recap, {
			recap: formatRecap( year ),
		} );

		return $recap;
	}

	/**
	 * The card of a reading: its book, and the date of the reading, which the card keeps for its line and its focus.
	 *
	 * @param {Book}   book The book.
	 * @param {string} date The date of the reading, empty when unknown.
	 * @return {HTMLElement} The card.
	 */
	#renderReading( book, date ) {
		const $item = renderBook( book, this.#state( book, date ) );

		$item.dataset.date = date;

		return $item;
	}

	/**
	 * The line of a reading: its day, whether the book was read again then, and the rating it ended with.
	 *
	 * @param {Book}   book The book.
	 * @param {string} date The date of the reading, empty when unknown.
	 * @return {string} The line.
	 */
	#state( book, date ) {
		const isFirstReading = date === book.readings[ 0 ]?.end;
		const reading = book.readings.find( ( { end } ) => end === date );

		return join( [
			formatDate( date, 'day' ),
			! isFirstReading && __( 'Read again', 'library' ),
			formatRating( reading?.rating ?? 0 ),
		] );
	}
}
customElements.define( 'library-diary', LibraryDiaryElement );
