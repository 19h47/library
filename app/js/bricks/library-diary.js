import { __, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { dayFormat, formatBooks, formatDate, formatRating, join } from 'library-app/format';
import { clone, fill, renderBook } from 'library-app/render';
import { followBooks, observeEnd, openBooks } from 'library-app/behaviors';

/** The readings by month, latest first: a book read again comes once per reading. */
class LibraryDiaryElement extends Brick {
	connect( signal ) {
		const $months = this.querySelector( '.library-diary__months' );
		const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMoreDiary() );

		openBooks( $months, signal );

		return [
			followBooks( $months, ( book, $item ) => this.#state( book, $item.dataset.date ) ),
			store.select(
				( { diary } ) => diary.error || ( diary.loaded && ! diary.total ? __( 'No readings yet.', 'library' ) : '' ),
				( message ) => fill( this, { status: message } )
			),
			store.select(
				( state ) => state.diary.items,
				( months ) => {
					const $cards = [ ...$months.querySelectorAll( '.library-item' ) ];
					const $focused = $cards.find( ( $item ) => $item.contains( document.activeElement ) );

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
	 */
	#refocus( $focused, index ) {
		const { id, date } = $focused.dataset;
		const $cards = [ ...this.querySelectorAll( '.library-item' ) ];
		const $card = $cards.find( ( $item ) => id === $item.dataset.id && date === $item.dataset.date ) ?? $cards[ Math.min( index, $cards.length - 1 ) ];

		$card?.querySelector( '.library-item__button' ).focus();
	}

	/** A section per month, with the number of its readings: a page of the diary holds whole months. */
	#renderMonths( months ) {
		const { books } = store.get();

		return months.map( ( { month, readings } ) => {
			const $month = clone( 'library-month' );
			// A reading shows with its book only.
			const shown = readings.filter( ( reading ) => reading.id in books );

			fill( $month, { month: formatDate( month ), count: formatBooks( readings.length ) } );
			$month.querySelector( 'ul' ).append( ...shown.map( ( { date, id } ) => this.#renderReading( books[ id ], date ) ) );

			return $month;
		} );
	}

	/** The card of a reading: its book, and the date of the reading, which the card keeps for its line and its focus. */
	#renderReading( book, date ) {
		const $item = renderBook( book, this.#state( book, date ) );

		$item.dataset.date = date;

		return $item;
	}

	/** The line of a reading: its day, whether the book was read again then, and its rating. */
	#state( book, date ) {
		const isFirstReading = date === book.read_date[ 0 ];

		return join( [ formatDate( date, dayFormat ), ! isFirstReading && __( 'Read again', 'library' ), formatRating( book.rating ) ] );
	}
}
customElements.define( 'library-diary', LibraryDiaryElement );
