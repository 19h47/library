import { Component } from 'library-app/component';
import { addRow, controlOf, listenRows } from 'library-app/rows';

/**
 * A list of inputs, the root of the component inputs-list, such as the readings of a book: a row each, a clone of its
 * template, its control first, which a button removes; its buttons add one, each a row of its template, a variant one
 * of a fixed value, such as a reading whose date is unknown, which its control shows as it shows it. A row added by a
 * button takes the focus and tells it with a rowadd event, which its place listens to, to fill it; a row removed tells
 * it with a change of the list.
 *
 * Its API: `template`, the ID of the template of its rows; its buttons, `data-action="add"`, a variant one with its
 * `data-value`; `value`, the values of the controls of its rows; `add( value )`, which adds a row; `disabled`, which
 * leaves its fields out of its form; the events rowadd, on the row added, and change, on a row removed.
 */
export class InputsListElement extends Component {
	static properties = [ 'value', 'disabled' ];

	/** @return {string[]} The values of its rows. */
	get value() {
		return [ ...this.#list.children ].map(
			( $row ) => controlOf( $row ).value
		);
	}

	/** @param {string[]} values The values of its rows, each a row. */
	set value( values ) {
		this.#list.replaceChildren();
		values.forEach( ( value ) => this.add( value ) );
	}

	/** @return {boolean} Whether its fields are left out of its form. */
	get disabled() {
		return this.#fieldset.disabled;
	}

	/** @param {boolean} disabled Whether to leave its fields out of its form. */
	set disabled( disabled ) {
		this.#fieldset.disabled = disabled;
	}

	/**
	 * Adds a row of its template, its control taking `value`.
	 *
	 * @param {string} [value] The value of the row.
	 * @return {HTMLElement} The row.
	 */
	add( value = '' ) {
		return addRow( this.#list, this.getAttribute( 'template' ), value );
	}

	/** @return {HTMLFieldSetElement} The fieldset of its fields. */
	get #fieldset() {
		return this.querySelector( 'fieldset' );
	}

	/** @return {HTMLUListElement} The list of its rows. */
	get #list() {
		return this.querySelector( 'ul' );
	}

	connect( signal ) {
		listenRows(
			this,
			( $button ) => this.add( $button.dataset.value ),
			signal
		);
	}
}
customElements.define( 'inputs-list', InputsListElement );
