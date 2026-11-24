import { __, sprintf } from 'library-app/app';
import { Component } from 'library-app/component';
import { fill } from 'library-app/dom';
import { formatAnd, formatNumber, join } from 'library-app/format';

/** @import { CheckboxesFieldElement } from './checkboxes-field.js' */

// The most values left unchecked a pill names, as all but them, such as All but Novel.
const MOST_LEFT = 2;

/**
 * The filters of a list, the root of the component filters-sheet: a button that says how many are chosen opens their
 * fields in a dialog, each named after its filter, a select or a checkboxes-field; each filter chosen shows as a pill,
 * which says its choice, or its label and how many values are checked, and removes it. The fields are the filters; the
 * pills and the button follow them, as the user changes them and as its place sets its value. It knows its fields by
 * their API alone: their value, their options and their counts, and the change they send.
 *
 * Its API: `value`, the choice of each filter by its name, the value of a select, the values checked of checkboxes,
 * set without an event; `counts`, how many each value of a filter has, by filter then value, such as the books of each
 * form; `found`, what the list finds as the filters stand, such as 19 books, which the button that closes the dialog
 * shows, empty while it loads. A filter changed sends a change from its field, a pill that removes a filter as well,
 * its name the one of the filter.
 */
export class FiltersSheetElement extends Component {
	static properties = [ 'value', 'counts', 'found' ];

	/** @type {Object<string, Object<string, number>>} */
	#counts = {};
	/**
	 * The label of the button that opens the filters, as the page gives it, before it counts the filters chosen.
	 *
	 * @type {string|undefined}
	 */
	#label;
	#found = '';

	/** @return {Object<string, string|string[]>} The choice of each filter, by its name. */
	get value() {
		return Object.fromEntries(
			this.#fields.map( ( $field ) => [ $field.name, $field.value ] )
		);
	}

	/** @param {Object<string, string|string[]>} value The choice of each filter, by its name; a filter left out stays. */
	set value( value ) {
		this.#setFields( value );
		this.#show();
	}

	/** @return {Object<string, Object<string, number>>} How many each value of a filter has. */
	get counts() {
		return this.#counts;
	}

	/** @param {Object<string, Object<string, number>>} counts How many each value of a filter has, by filter then value. */
	set counts( counts ) {
		this.#counts = counts;
		Object.entries( counts ).forEach( ( [ filter, filterCounts ] ) => {
			const $field = this.#fieldOf( filter );

			if ( $field && ! ( $field instanceof HTMLSelectElement ) ) {
				$field.counts = filterCounts;
			}
		} );
	}

	/** @return {string} What the list finds, empty while it loads. */
	get found() {
		return this.#found;
	}

	/** @param {string} found What the list finds, such as 19 books, empty while it loads. */
	set found( found ) {
		this.#found = found;
		fill( this, {
			show: found
				? sprintf(
						/* translators: %s: number of books or of readings found, e.g. 19 books. */
						__( 'Show %s', 'library' ),
						found
				  )
				: __( 'Loading…', 'library' ),
		} );
	}

	/** @return {Array<HTMLSelectElement|CheckboxesFieldElement>} The fields of the filters, in its dialog. */
	get #fields() {
		return /** @type {Array<HTMLSelectElement|CheckboxesFieldElement>} */ ( [
			...this.querySelectorAll( 'dialog :is(select, checkboxes-field)' ),
		] );
	}

	/**
	 * The field of the filter `filter`: its select, or its checkboxes-field.
	 *
	 * @param {string} filter The filter.
	 * @return {HTMLSelectElement|CheckboxesFieldElement|undefined} Its field.
	 */
	#fieldOf( filter ) {
		return this.#fields.find( ( $field ) => $field.name === filter );
	}

	/**
	 * Sets the fields of the filters given, the others left as they are.
	 *
	 * @param {Object<string, string|string[]>} value The choice of each filter, by its name.
	 */
	#setFields( value ) {
		this.#fields
			.filter( ( $field ) => $field.name in value )
			.forEach( ( $field ) => {
				$field.value = /** @type {string & string[]} */ (
					value[ $field.name ]
				);
			} );
	}

	/**
	 * What a field chose, as the pill of its filter says it: the label of the option chosen, or of the one value checked;
	 * all but the values left unchecked, a few and fewer than those checked, such as All but Novel; else the label of its
	 * filter and how many values are checked, such as Forms · 3; empty while none is.
	 *
	 * @param {HTMLSelectElement|CheckboxesFieldElement} $field The field.
	 * @param {string}                                   label  The label of its filter.
	 * @return {string} What it chose.
	 */
	#chosen( $field, label ) {
		if ( $field instanceof HTMLSelectElement ) {
			return $field.value ? $field.selectedOptions[ 0 ].textContent : '';
		}

		const { options, value } = $field;
		const checked = options.filter( ( option ) =>
			value.includes( option.value )
		);

		if ( checked.length < 2 ) {
			return checked.map( ( option ) => option.label ).join();
		}

		const left = options.filter(
			( option ) => ! value.includes( option.value )
		);

		if (
			left.length &&
			left.length <= MOST_LEFT &&
			left.length < checked.length
		) {
			return sprintf(
				/* translators: %s: values left out of a filter, e.g. Novel and Essay. */
				__( 'All but %s', 'library' ),
				formatAnd( left.map( ( option ) => option.label ) )
			);
		}

		return join( [ label, formatNumber( checked.length ) ] );
	}

	// The pills of the filters chosen and the button that opens them, as the fields stand.
	#show() {
		/** @type {HTMLElement} */
		const $chosen = this.querySelector( '[data-list="chosen"]' );
		/** @type {HTMLElement} */
		const $open = this.querySelector( '[data-part="open"]' );
		const chosen = [
			...$chosen.querySelectorAll( '[data-filter]' ),
		].filter( ( /** @type {HTMLElement} */ $pill ) => {
			const { filter, label } = $pill.dataset;
			const text = this.#chosen( this.#fieldOf( filter ), label );

			fill( $pill, { chosen: text } );
			$pill.hidden = ! text;

			return text;
		} );

		this.#label ??= $open.textContent.trim();
		$chosen.hidden = ! chosen.length;
		fill( $open, {
			label: join( [
				this.#label,
				chosen.length && formatNumber( chosen.length ),
			] ),
		} );
	}

	connect( signal ) {
		/** @type {HTMLButtonElement} */
		const $open = this.querySelector( '[data-part="open"]' );

		// The button that closes the dialog says what the list finds, that it loads until its place says it.
		this.found = this.#found;
		this.addEventListener( 'change', () => this.#show(), { signal } );
		// A pill removes its filter, its field sending the events of a change; it leaves, the focus goes to the button of
		// the filters.
		this.addEventListener(
			'click',
			( event ) => {
				/** @type {HTMLElement} */
				const $pill = /** @type {Element} */ ( event.target ).closest(
					'[data-filter]'
				);

				if ( ! $pill ) {
					return;
				}

				const $field = this.#fieldOf( $pill.dataset.filter );

				$field.value = /** @type {string & string[]} */ (
					$field instanceof HTMLSelectElement ? '' : []
				);
				$field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				$field.dispatchEvent(
					new Event( 'change', { bubbles: true } )
				);
				$open.focus();
			},
			{ signal }
		);

		this.#show();
	}
}
customElements.define( 'filters-sheet', FiltersSheetElement );
