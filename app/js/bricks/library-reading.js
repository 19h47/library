import { notKept, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { today } from 'library-app/format';
import { clone, fill } from 'library-app/render';

/**
 * The reading of the book open, as a form: whether it is read, its read dates, its rating and whether it is kept.
 * Saving closes the book; closing it drops what was not saved.
 */
class LibraryReadingElement extends Brick {
	connect( signal ) {
		const $form = this.querySelector( 'form' );
		const { read: $read, rating: $rating, not_kept: $notKept } = $form.elements;
		const $dates = this.querySelector( '.library-reading__dates' );
		const $list = $dates.querySelector( 'ul' );
		const $add = $dates.querySelector( '[data-action="add"]' );
		const $submit = $form.querySelector( '[type="submit"]' );

		const addDate = ( date ) => {
			const $item = clone( 'library-read-date' );
			const $input = $item.querySelector( 'input' );

			$input.value = date;
			$input.max = today();
			$list.append( $item );

			return $input;
		};

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
					addDate( today() );
				}

				showDates();
			},
			{ signal }
		);

		$add.addEventListener( 'click', () => addDate( today() ).focus(), { signal } );

		$list.addEventListener(
			'click',
			( event ) => {
				const $remove = event.target.closest( '[data-action="remove"]' );

				if ( $remove ) {
					$remove.closest( 'li' ).remove();
					$add.focus();
				}
			},
			{ signal }
		);

		$form.addEventListener(
			'submit',
			( event ) => {
				const data = new FormData( $form );
				const rating = data.get( 'rating' );

				event.preventDefault();
				store.saveBook( {
					read: data.has( 'read' ),
					readDates: data.getAll( 'read_date' ),
					rating: rating ? Number( rating ) : null,
					kept: ! data.has( 'not_kept' ),
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

					$read.checked = book.read;
					$list.replaceChildren();
					book.read_date.forEach( ( date ) => addDate( date ) );
					showDates();
					$rating.value = ( book.rating || '' ).toString();
					$notKept.checked = notKept === book.status;
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
