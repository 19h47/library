import { statusOf, store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { today } from 'library-app/format';
import { toDays, toInterval } from 'library-app/api';
import { fill } from 'library-app/render';
import { inputList, showBusy, showErrors } from 'library-app/behaviors';

/**
 * @import { ReadingPeriod } from 'library-app/api'
 */

/**
 * The fields of the reading of a book, by their names.
 *
 * @typedef {Object} ReadingFields
 * @property {HTMLInputElement}  read        Whether it is read.
 * @property {HTMLInputElement}  in_progress Whether it is being read.
 * @property {HTMLInputElement}  started     The day its reading in progress started.
 * @property {HTMLSelectElement} rating      Its rating.
 * @property {HTMLSelectElement} status      Whether it is in the library, not kept or to buy.
 */

/**
 * The readings ended the form gives, a start and an end per row, by their days, either empty when it is unknown.
 *
 * @param {FormData} data The values of the form.
 * @return {ReadingPeriod[]} The readings.
 */
function readingsOf( data ) {
	// The values of date inputs are strings.
	const starts = /** @type {string[]} */ ( data.getAll( 'reading_start' ) );
	const ends = /** @type {string[]} */ ( data.getAll( 'reading_end' ) );

	return starts.map( ( start, index ) => ( { start, end: ends[ index ] } ) );
}

// Readings by their days, as intervals in order, to compare: a reading of the day keeps its times once saved.
const intervalsOf = ( /** @type {ReadingPeriod[]} */ readings ) =>
	JSON.stringify(
		readings.map( ( reading ) => toInterval( toDays( reading ) ) ).sort()
	);

/**
 * The reading of the book open, as a form: whether it is read, whether it is being read and since when, its readings ended,
 * from their starts to their ends, its rating and whether it is in the library, not kept or to buy. A reading added ends
 * the one in progress, from its start, as the server does. Each change is saved at once, a date typed once left, a field
 * in error showing it without taking the focus, and the notice that says so offers Undo; a change made while the one
 * before saves waits for it, the form showing what was chosen until then rather than what the book was.
 */
class LibraryReadingElement extends Brick {
	connect( signal ) {
		const $form = this.querySelector( 'form' );
		const {
			read: $read,
			in_progress: $inProgress,
			started: $started,
			rating: $rating,
			status: $status,
		} = /** @type {HTMLFormControlsCollection & ReadingFields} */ (
			$form.elements
		);
		/** @type {HTMLElement} */
		const $startedField = this.querySelector( '.library-reading__started' );
		/** @type {HTMLFieldSetElement} */
		const $readings = this.querySelector( '.library-reading__readings' );
		const $list = $readings.querySelector( 'ul' );
		/** @type {HTMLButtonElement} */
		const $add = $readings.querySelector( '[data-action="add"]' );
		/** @type {HTMLButtonElement} */
		const $addUnknown = $readings.querySelector(
			'[data-action="add-unknown"]'
		);
		// Whether a change waits for the saving of the one before.
		let isPending = false;
		/**
		 * The date field typed in, which changes at each key, through the dates on the way, such as the year 0002 of 2025:
		 * it is saved once left.
		 *
		 * @type {HTMLInputElement|null}
		 */
		let $typed = null;
		// The date fields check theirs first, such as a date later than today: an invalid one is not sent.
		const save = () => {
			if ( store.get().saving.loading ) {
				isPending = true;
				return;
			}

			$form.requestSubmit();
		};

		/**
		 * A start never after its end, an end never before its start, and neither later than today. The field typed in
		 * keeps its bounds: Chrome starts the typing of a date field again as its bounds are set, which empties it at the
		 * next key, such as the year 0002 of 2025; a bound is set only when it changes.
		 *
		 * @param {Element}               $row     The row of a reading.
		 * @param {HTMLInputElement|null} [$field] The field typed in, if any.
		 */
		const limit = ( $row, $field = null ) => {
			const [ $start, $end ] = /** @type {HTMLInputElement[]} */ ( [
				...$row.querySelectorAll( 'input[type="date"]' ),
			] );
			const bound = (
				/** @type {HTMLInputElement} */ $input,
				/** @type {'min'|'max'} */ name,
				/** @type {string} */ value
			) => {
				if ( $input !== $field && $input[ name ] !== value ) {
					$input[ name ] = value;
				}
			};

			if ( $start && $end ) {
				bound( $start, 'max', $end.value || today() );
				bound( $end, 'min', $start.value );
				bound( $end, 'max', today() );
			}
		};

		// A reading ends today, unless changed, from the start of the one in progress, which it ends; a reading whose dates
		// are unknown has no date input.
		const addReading = inputList( {
			$list,
			$add,
			id: ( interval ) =>
				'/' === interval
					? 'library-reading-unknown'
					: 'library-reading-ended',
			signal,
			value: () =>
				`${ $inProgress.checked ? $started.value : '' }/${ today() }`,
			setup: ( $start, interval ) => {
				const [ start, end ] = interval.split( '/' );
				const $row = $start.closest( 'li' );
				/** @type {HTMLInputElement} */
				const $end = $row.querySelector( '[name="reading_end"]' );

				$start.value = start;
				$end.value = end;
				limit( $row );
			},
		} );

		const clearErrors = showErrors( $form, signal, { focus: false } );

		// The day the reading in progress started, while there is one: today, unless changed, as it starts.
		const showStarted = () => {
			$started.max = today();
			$started.disabled = ! $inProgress.checked;
			$startedField.hidden = ! $inProgress.checked;

			if ( $inProgress.checked && ! $started.value ) {
				$started.value = today();
			}
		};

		// Unread, the book has no readings ended: they are left out of the form.
		const showReadings = () => {
			$readings.disabled = ! $read.checked;
			$readings.hidden = ! $read.checked;
		};

		// Its row has no input to focus: its button does. A book may have been read several times at dates unknown.
		$addUnknown.addEventListener(
			'click',
			() =>
				/** @type {HTMLButtonElement} */ (
					addReading( '/' )
						.closest( 'li' )
						.querySelector( '[data-action="remove"]' )
				).focus(),
			{ signal }
		);

		// A reading added ends the one in progress, which its row took the start of.
		[ $add, $addUnknown ].forEach( ( $button ) =>
			$button.addEventListener(
				'click',
				() => {
					$inProgress.checked = false;
					showStarted();
					save();
				},
				{ signal }
			)
		);

		// A reading removed, after inputList() removed its row.
		$list.addEventListener(
			'click',
			( event ) =>
				/** @type {Element} */ ( event.target ).closest(
					'[data-action="remove"]'
				) && save(),
			{ signal }
		);
		$list.addEventListener(
			'input',
			( event ) => {
				const $field = /** @type {HTMLInputElement} */ ( event.target );

				limit( $field.closest( 'li' ), $field );
			},
			{ signal }
		);
		$inProgress.addEventListener( 'change', showStarted, { signal } );
		$form.addEventListener(
			'change',
			( event ) => {
				const $field = /** @type {HTMLInputElement} */ ( event.target );

				// Still typed in: Chrome moves the focus into the next part of the date, out of :focus but not of
				// :focus-within, as it changes at the end of a part, such as its month.
				if (
					'date' === $field.type &&
					$field.matches( ':focus-within' )
				) {
					$typed = $field;
					return;
				}

				save();
			},
			{ signal }
		);
		$form.addEventListener(
			'focusout',
			( event ) => {
				if ( event.target !== $typed ) {
					return;
				}

				$typed = null;
				save();
			},
			{ signal }
		);

		// Whether the form shows the reading of `book` already, such as the one it just saved: it stays as it is, its focus too.
		const isShown = ( book ) => {
			const data = new FormData( $form );

			return (
				data.has( 'read' ) === book.read &&
				data.has( 'in_progress' ) === book.in_progress &&
				( ! book.in_progress ||
					data.get( 'started' ) === book.started.slice( 0, 10 ) ) &&
				intervalsOf( readingsOf( data ) ) ===
					intervalsOf( book.readings ) &&
				data.get( 'rating' ) === ( book.rating || '' ).toString() &&
				data.get( 'status' ) === statusOf( book )
			);
		};

		$read.addEventListener(
			'change',
			() => {
				// Read today, from the start of the reading in progress, which it ends, unless a reading is there already.
				if ( $read.checked && ! $list.children.length ) {
					addReading();
					$inProgress.checked = false;
					showStarted();
				}

				showReadings();
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
					inProgress: data.has( 'in_progress' ),
					// The values of date inputs and selects are strings.
					started: /** @type {string} */ (
						data.get( 'started' ) ?? ''
					),
					readings: readingsOf( data ),
					rating: rating ? Number( rating ) : null,
					status: /** @type {string} */ ( data.get( 'status' ) ),
				} );
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => state.books[ state.book ] ?? null,
				( book ) => {
					// A change waiting to be saved, or a date being typed, keeps what was chosen, which it sends.
					if ( ! book || isPending || $typed || isShown( book ) ) {
						return;
					}

					clearErrors();
					$read.checked = book.read;
					$inProgress.checked = book.in_progress;
					// By its day, as a date field takes it: a reading started today keeps its time once saved.
					$started.value = book.started.slice( 0, 10 );
					showStarted();
					$list.replaceChildren();
					book.readings.forEach( ( reading ) =>
						addReading( toInterval( toDays( reading ) ) )
					);
					showReadings();
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
