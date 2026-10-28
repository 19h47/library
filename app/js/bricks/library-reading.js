import { notKept, store, toBuy } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { today } from 'library-app/format';
import { fill } from 'library-app/render';
import { inputList, showErrors } from 'library-app/behaviors';

/**
 * The reading of the book open, as a form: whether it is read, its read dates, its rating and whether it is in the library, not kept or to buy.
 * Saving closes the book; closing it drops what was not saved.
 */
class LibraryReadingElement extends Brick {
	connect( signal ) {
		const $form = this.querySelector( 'form' );
		const { read: $read, rating: $rating, status: $status } = $form.elements;
		const $dates = this.querySelector( '.library-reading__dates' );
		const $list = $dates.querySelector( 'ul' );
		const $add = $dates.querySelector( '[data-action="add"]' );
		const $submit = $form.querySelector( '[type="submit"]' );

		// A reading is today, unless changed, and never later.
		const addDate = inputList( {
			$list,
			$add,
			id: 'library-read-date',
			signal,
			value: today,
			setup: ( $input ) => {
				$input.max = today();
			},
		} );

		const clearErrors = showErrors( $form, signal );

		// Unread, the book has no read dates: they are left out of the form.
		const showDates = () => {
			$dates.disabled = ! $read.checked;
			$dates.hidden = ! $read.checked;
		};

		$read.addEventListener(
			'change',
			() => {
				// Read today, unless a date is there already.
				if ( $read.checked && ! $list.children.length ) {
					addDate();
				}

				showDates();
			},
			{ signal }
		);

		$form.addEventListener(
			'submit',
			( event ) => {
				const data = new FormData( $form );
				const rating = data.get( 'rating' );

				event.preventDefault();
				store.saveReading( {
					read: data.has( 'read' ),
					readDates: data.getAll( 'read_date' ),
					rating: rating ? Number( rating ) : null,
					status: data.get( 'status' ),
				} );
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => state.books[ state.book ] ?? null,
				( book ) => {
					if ( ! book ) {
						return;
					}

					clearErrors();
					$read.checked = book.read;
					$list.replaceChildren();
					book.read_date.forEach( ( date ) => addDate( date ) );
					showDates();
					$rating.value = ( book.rating || '' ).toString();
					// A book in another status, such as a draft, shows as in the library.
					$status.value = [ notKept, toBuy ].includes( book.status ) ? book.status : 'publish';
				}
			),
			store.select(
				( state ) => state.saving,
				( saving ) => {
					$submit.disabled = saving.loading;
					fill( this, { error: saving.error } );
				}
			),
		];
	}
}
customElements.define( 'library-reading', LibraryReadingElement );
