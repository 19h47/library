import { Component } from 'library-app/component';

/**
 * A field of several choices, the root of the component checkboxes-field: a pill for each choice made, which removes
 * it, and a button that opens all of them in a sheet, a checkbox each. The checkboxes are the field; the pills follow
 * them, as the user checks them, as a reset of the form checks its defaults again, and as its place sets its value.
 *
 * Its API: `name`, the one of its checkboxes, which its form sends; `value`, the values checked, set without an event,
 * as a checkbox checked by a script; `defaultValue`, the ones a reset of the form checks again.
 */
export class CheckboxesFieldElement extends Component {
	static properties = [ 'value' ];

	/** @return {string[]} The values checked. */
	get value() {
		return this.#checkboxes
			.filter( ( $checkbox ) => $checkbox.checked )
			.map( ( $checkbox ) => $checkbox.value );
	}

	/** @param {Array<string|number>} values The values to check, and only them, such as term IDs. */
	set value( values ) {
		const checked = values.map( ( value ) => value.toString() );

		this.#checkboxes.forEach( ( $checkbox ) => {
			$checkbox.checked = checked.includes( $checkbox.value );
		} );
		this.#showChosen();
	}

	/** @return {string[]} The values checked by default. */
	get defaultValue() {
		return this.#checkboxes
			.filter( ( $checkbox ) => $checkbox.defaultChecked )
			.map( ( $checkbox ) => $checkbox.value );
	}

	/** @return {HTMLInputElement[]} Its checkboxes, a choice each. */
	get #checkboxes() {
		return /** @type {HTMLInputElement[]} */ ( [
			...this.querySelectorAll( 'input[type="checkbox"]' ),
		] );
	}

	// Each pill shows while its choice is checked.
	#showChosen() {
		const { value } = this;

		this.querySelectorAll( '[data-value]' ).forEach(
			( /** @type {HTMLElement} */ $pill ) => {
				$pill.hidden = ! value.includes( $pill.dataset.value );
			}
		);
	}

	connect( signal ) {
		/** @type {HTMLButtonElement} */
		const $add = this.querySelector( '[command="show-modal"]' );

		this.addEventListener( 'change', () => this.#showChosen(), { signal } );
		// The reset event comes before the form resets: the pills follow once it has.
		this.closest( 'form' )?.addEventListener(
			'reset',
			() => queueMicrotask( () => this.#showChosen() ),
			{ signal }
		);
		// A pill unchecks its choice, as a click on its checkbox would.
		this.addEventListener(
			'click',
			( event ) => {
				/** @type {HTMLElement} */
				const $pill = /** @type {Element} */ ( event.target ).closest(
					'[data-value]'
				);

				if ( ! $pill ) {
					return;
				}

				const $checkbox = this.#checkboxes.find(
					( $choice ) => $choice.value === $pill.dataset.value
				);

				$checkbox.checked = false;
				$checkbox.dispatchEvent(
					new Event( 'input', { bubbles: true } )
				);
				$checkbox.dispatchEvent(
					new Event( 'change', { bubbles: true } )
				);
				// The pill leaves: the focus goes to the button that adds one.
				$add.focus();
			},
			{ signal }
		);

		this.#showChosen();
	}
}
customElements.define( 'checkboxes-field', CheckboxesFieldElement );
