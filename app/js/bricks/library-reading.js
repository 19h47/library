import { notKept, toBuy } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { today } from 'library-app/format';
import { fill } from 'library-app/render';
import { inputList, showBusy, showErrors } from 'library-app/behaviors';

// A book in another status, such as a draft, shows as in the library.
const statusOf = ( book ) =>
	[ notKept, toBuy ].includes( book.status ) ? book.status : 'publish';
// Read dates by their days, in order, to compare: a reading of the day keeps its time once saved.
const daysOf = ( dates ) =>
	JSON.stringify( dates.map( ( date ) => date.slice( 0, 10 ) ).sort() );

/**
 * The reading of the book open, as a form: whether it is read, whether it is being read, its read dates, its rating and whether it is in
 * the library, not kept or to buy. A reading added ends the one in progress, as the server does. Each change is saved at once, as the
 * button Finished of the home saves, and the notice that says so offers Undo; a change made while the one before saves waits for it,
 * the form showing what was chosen until then rather than what the book was.
 */
class LibraryReadingElement extends Brick {
	connect( signal ) {
		const $form = this.querySelector( 'form' );
		const {
			library_read: $read,
			library_in_progress: $inProgress,
			library_rating: $rating,
			status: $status,
		} = $form.elements;
		const $dates = this.querySelector( '.library-reading__dates' );
		const $list = $dates.querySelector( 'ul' );
		const $add = $dates.querySelector( '[data-action="add"]' );
		const $addUnknown = $dates.querySelector(
			'[data-action="add-unknown"]'
		);
		// Whether a change waits for the saving of the one before.
		let isPending = false;
		// The date fields check theirs first, such as a date later than today: an invalid one is not sent.
		const save = () => {
			if ( store.get().saving.loading ) {
				isPending = true;
				return;
			}

			$form.requestSubmit();
		};

		// A reading is today, unless changed, and never later; a reading whose date is unknown has no date input.
		const addDate = inputList( {
			$list,
			$add,
			id: ( date ) =>
				date ? 'library-read-date' : 'library-read-date-unknown',
			signal,
			value: today,
			setup: ( $input ) => {
				$input.max = today();
			},
		} );

		const clearErrors = showErrors( $form, signal );

		// Its row has no input to focus: its button does. A book may have been read several times at dates unknown.
		$addUnknown.addEventListener(
			'click',
			() =>
				addDate( '' )
					.closest( 'li' )
					.querySelector( '[data-action="remove"]' )
					.focus(),
			{ signal }
		);

		[ $add, $addUnknown ].forEach( ( $button ) =>
			$button.addEventListener(
				'click',
				() => {
					$inProgress.checked = false;
					save();
				},
				{ signal }
			)
		);

		// A reading removed, after inputList() removed its row.
		$list.addEventListener(
			'click',
			( event ) =>
				event.target.closest( '[data-action="remove"]' ) && save(),
			{ signal }
		);
		$form.addEventListener( 'change', save, { signal } );

		// Whether the form shows the reading of `book` already, such as the one it just saved: it stays as it is, its focus too.
		const isShown = ( book ) => {
			const data = new FormData( $form );

			return (
				data.has( 'library_read' ) === book.read &&
				data.has( 'library_in_progress' ) === book.in_progress &&
				daysOf( data.getAll( 'library_read_date' ) ) ===
					daysOf( book.read_date ) &&
				data.get( 'library_rating' ) ===
					( book.rating || '' ).toString() &&
				data.get( 'status' ) === statusOf( book )
			);
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
					addDate();
					$inProgress.checked = false;
				}

				showDates();
			},
			{ signal }
		);

		$form.addEventListener(
			'submit',
			( event ) => {
				const data = new FormData( $form );
				const rating = data.get( 'library_rating' );

				event.preventDefault();
				store.saveReading( {
					read: data.has( 'library_read' ),
					inProgress: data.has( 'library_in_progress' ),
					readDates: data.getAll( 'library_read_date' ),
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
					// A change waiting to be saved keeps what was chosen, which it sends.
					if ( ! book || isPending || isShown( book ) ) {
						return;
					}

					clearErrors();
					$read.checked = book.read;
					$inProgress.checked = book.in_progress;
					$list.replaceChildren();
					// By its day, as a date field takes it: a reading of the day keeps its time once saved.
					book.read_date.forEach( ( date ) =>
						addDate( date.slice( 0, 10 ) )
					);
					showDates();
					$rating.value = ( book.rating || '' ).toString();
					$status.value = statusOf( book );
				}
			),
			store.select(
				( state ) => state.saving,
				( saving ) => {
					showBusy( $form, saving.loading );
					fill( this, { error: saving.error } );

					if ( ! saving.loading && isPending ) {
						isPending = false;
						save();
					}
				}
			),
		];
	}
}
customElements.define( 'library-reading', LibraryReadingElement );
