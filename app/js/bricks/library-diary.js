import { store, strings } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { dayFormat, formatDate, formatDetails, join, plural } from 'library-app/format';
import { clone, fill, renderBook } from 'library-app/render';
import { followBooks, observeEnd, openBooks } from 'library-app/behaviors';

/** The readings by month, latest first: a book read again comes once per reading. */
class LibraryDiaryElement extends Brick {
	connect( signal ) {
		const $months = this.querySelector( '.library-diary__months' );
		const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMoreReadings() );

		openBooks( $months, signal );

		return [
			followBooks( $months, ( book, $item ) => this.#state( book, $item.dataset.date ) ),
			store.select(
				( { diary } ) => diary.error || ( diary.loaded && ! diary.total ? strings.noBooks : '' ),
				( message ) => fill( this, { status: message } )
			),
			store.select(
				( state ) => state.diary.items,
				( readings ) => {
					const $cards = [ ...$months.querySelectorAll( '.library-item' ) ];
					const $focused = $cards.find( ( $item ) => $item.contains( document.activeElement ) );

					$months.replaceChildren( ...this.#renderMonths( readings ) );

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

	/** A section per month; the count of the last one shows once its readings are all loaded. */
	#renderMonths( readings ) {
		const { books, diary } = store.get();
		// A reading shows with its book only.
		const months = Map.groupBy(
			readings.filter( ( reading ) => reading.id in books ),
			( reading ) => reading.date.slice( 0, 7 )
		);
		const complete = diary.page >= diary.pages;

		return [ ...months ].map( ( [ month, entries ], index ) => {
			const $month = clone( 'library-month' );
			const isLast = index === months.size - 1;

			fill( $month, {
				month: formatDate( month ),
				count: ! isLast || complete ? plural( entries.length, strings.books ) : '',
			} );
			$month.querySelector( 'ul' ).append(
				...entries.map( ( { date, id } ) => {
					const $item = renderBook( books[ id ], this.#state( books[ id ], date ) );

					$item.dataset.date = date;

					return $item;
				} )
			);

			return $month;
		} );
	}

	/** The line of a reading: its day, whether the book was read again then, its rating and whether it is kept. */
	#state( book, date ) {
		const isFirstReading = date === book.read_date[ 0 ];

		return join( [ formatDate( date, dayFormat ), ! isFirstReading && strings.readAgain, ...formatDetails( book ) ] );
	}
}
customElements.define( 'library-diary', LibraryDiaryElement );
