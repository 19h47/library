import { Component } from 'library-app/component';
import { fill } from 'library-app/dom';
import { join } from 'library-app/format';

/**
 * Fields folded under a summary, the root of the component fields-disclosure: its summary says its label and how many
 * of its fields are filled in, as the filters count theirs: the checkboxes of a field once, a date once, its parts
 * sending nothing. It counts them as they change, as its form resets, and as its place asks once it set them itself.
 *
 * Its API: `open`, whether it shows its fields; `recount()`, which counts its fields again once a script set them, as
 * that sends no event.
 */
export class FieldsDisclosureElement extends Component {
	/**
	 * The label of its summary, as the page gives it, before it counts.
	 *
	 * @type {string|undefined}
	 */
	#label;

	/** @return {boolean} Whether it shows its fields. */
	get open() {
		return this.#details.open;
	}

	/** @param {boolean} open Whether it shows its fields. */
	set open( open ) {
		this.#details.open = open;
	}

	/** @return {HTMLDetailsElement} Its details. */
	get #details() {
		return this.querySelector( 'details' );
	}

	/** Counts its fields filled in again, into its summary. */
	recount() {
		// A date by its parts has a name and a value of its own, as a field.
		const $fields =
			/** @type {Array<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement>} */ ( [
				...this.querySelectorAll(
					'input, select, textarea, partial-date'
				),
			] );
		const filled = new Set(
			$fields
				.filter(
					( $field ) =>
						$field.name &&
						( $field instanceof HTMLInputElement &&
						'checkbox' === $field.type
							? $field.checked
							: $field.value )
				)
				.map( ( $field ) => $field.name )
		).size;
		/** @type {HTMLElement} */
		const $summary = this.querySelector( 'summary' );

		this.#label ??= $summary.textContent.trim();
		fill( $summary, {
			label: join( [ this.#label, filled && filled.toString() ] ),
		} );
	}

	connect( signal ) {
		this.addEventListener( 'input', () => this.recount(), { signal } );
		this.addEventListener( 'change', () => this.recount(), { signal } );
		// The reset event comes before the form resets: it counts once it has.
		this.closest( 'form' )?.addEventListener(
			'reset',
			() => queueMicrotask( () => this.recount() ),
			{ signal }
		);
		this.recount();
	}
}
customElements.define( 'fields-disclosure', FieldsDisclosureElement );
