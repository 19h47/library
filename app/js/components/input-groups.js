import { Component } from 'library-app/component';
import { addRow, controlOf, listenRows } from 'library-app/rows';

/**
 * A list of inputs in groups, the root of the component input-groups, such as the people of a book by role: a row
 * each, a clone of its template, in the group of its own, which a button removes; its button adds one, in the group
 * its select chooses. A row added by the button takes the focus and tells it with a rowadd event; a row removed tells
 * it with a change of the list.
 *
 * Its API: `template`, the ID of the template of its rows; `value`, its rows, the value of each and its group;
 * `add( value, group )`, which adds a row; the events rowadd, on the row added, and change, on a row removed.
 */
export class InputGroupsElement extends Component {
	static properties = [ 'value' ];

	/** @return {Array<{value: string, group: string}>} Its rows, the value of each and its group. */
	get value() {
		return this.#groups.flatMap( ( $group ) =>
			[ ...$group.querySelector( 'ul' ).children ].map( ( $row ) => ( {
				value: controlOf( $row ).value,
				group: $group.dataset.group,
			} ) )
		);
	}

	/** @param {Array<{value: string, group: string}>} rows Its rows, each with its group. */
	set value( rows ) {
		this.#groups.forEach( ( $group ) =>
			$group.querySelector( 'ul' ).replaceChildren()
		);
		rows.forEach( ( { value, group } ) => this.add( value, group ) );
	}

	/**
	 * Adds a row in a group, its control taking `value`.
	 *
	 * @param {string} [value] The value of the row.
	 * @param {string} [group] Its group, the one its select chooses unless given.
	 * @return {HTMLElement} The row.
	 */
	add( value = '', group = this.querySelector( 'select' ).value ) {
		return addRow(
			this.querySelector( `[data-group="${ group }"] > ul` ),
			this.getAttribute( 'template' ),
			value
		);
	}

	/** @return {HTMLElement[]} Its groups, each a fieldset. */
	get #groups() {
		return /** @type {HTMLElement[]} */ ( [
			...this.querySelectorAll( '[data-group]' ),
		] );
	}

	connect( signal ) {
		listenRows( this, () => this.add(), signal );
	}
}
customElements.define( 'input-groups', InputGroupsElement );
