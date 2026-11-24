import { __ } from 'library-app/app';
import { Component } from 'library-app/component';
import { formatNumber } from 'library-app/format';

/**
 * A field of several choices, the root of the component checkboxes-field: a pill for each choice made, which removes
 * it, and a button that opens all of them in a sheet, a checkbox each; or, inline, a pill checkbox for each choice. A
 * button may check them all, or uncheck them all once they are. The checkboxes are the field; the pills and that button
 * follow them, as the user checks them, as a reset of the form checks its defaults again, and as its place sets its value.
 *
 * Its API: `name`, the one of its checkboxes, which its form sends; `value`, the values checked, set without an event,
 * as a checkbox checked by a script; `defaultValue`, the ones a reset of the form checks again; `options`, each choice,
 * its value and its label, in order, as those of a select; `counts`, how many each choice has, by value, such as the
 * books of a form, which its checkboxes show if they have room for it. A pill that removes a choice, and the button that
 * checks all, send a change from a checkbox, as a click on one would.
 */
export class CheckboxesFieldElement extends Component {
	static properties = [ 'value', 'counts' ];

	/** @type {Object<string, number>} */
	#counts = {};

	/** @return {string} The name of its checkboxes, which its form sends. */
	get name() {
		return this.getAttribute( 'name' ) ?? '';
	}

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

	/** @return {Array<{value: string, label: string}>} Each choice, its value and its label, in order. */
	get options() {
		return this.#checkboxes.map( ( $checkbox ) => ( {
			value: $checkbox.value,
			label: $checkbox.labels[ 0 ].querySelector( '[data-part="name"]' )
				.textContent,
		} ) );
	}

	/** @return {Object<string, number>} How many each choice has, by value. */
	get counts() {
		return this.#counts;
	}

	/** @param {Object<string, number>} counts How many each choice has, by value, 0 for one left out. */
	set counts( counts ) {
		this.#counts = counts;
		this.#checkboxes.forEach( ( $checkbox ) => {
			const $count = $checkbox.labels[ 0 ].querySelector(
				'[data-part="count"]'
			);

			if ( $count ) {
				$count.textContent = formatNumber(
					counts[ $checkbox.value ] ?? 0
				);
			}
		} );
	}

	/** @return {HTMLInputElement[]} Its checkboxes, a choice each. */
	get #checkboxes() {
		return /** @type {HTMLInputElement[]} */ ( [
			...this.querySelectorAll( 'input[type="checkbox"]' ),
		] );
	}

	// Each pill shows while its choice is checked; the button that checks all unchecks all once they all are.
	#showChosen() {
		const { value } = this;
		const $toggle = this.querySelector( '[data-action="toggle-all"]' );

		this.querySelectorAll( '[data-value]' ).forEach(
			( /** @type {HTMLElement} */ $pill ) => {
				$pill.hidden = ! value.includes( $pill.dataset.value );
			}
		);

		if ( $toggle ) {
			$toggle.textContent = this.#allChecked
				? __( 'Uncheck all', 'library' )
				: __( 'Check all', 'library' );
		}
	}

	/** @return {boolean} Whether every choice is checked. */
	get #allChecked() {
		return this.#checkboxes.every( ( $checkbox ) => $checkbox.checked );
	}

	/**
	 * Sets the values checked as a user would, a checkbox sending the events of a change.
	 *
	 * @param {string[]}         values    The values to check.
	 * @param {HTMLInputElement} $checkbox The checkbox that sends them.
	 */
	#change( values, $checkbox ) {
		this.value = values;
		$checkbox.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		$checkbox.dispatchEvent( new Event( 'change', { bubbles: true } ) );
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
		this.addEventListener(
			'click',
			( event ) => {
				const $target = /** @type {Element} */ ( event.target );
				/** @type {HTMLElement} */
				const $pill = $target.closest( '[data-value]' );

				// A pill unchecks its choice, as a click on its checkbox would.
				if ( $pill ) {
					const $checkbox = this.#checkboxes.find(
						( $choice ) => $choice.value === $pill.dataset.value
					);

					this.#change(
						this.value.filter(
							( value ) => value !== $pill.dataset.value
						),
						$checkbox
					);
					// The pill leaves: the focus goes to the button that adds one.
					$add.focus();
				}

				// Checks all the choices, or unchecks them once they all are.
				if ( $target.closest( '[data-action="toggle-all"]' ) ) {
					this.#change(
						this.#allChecked
							? []
							: this.#checkboxes.map(
									( $checkbox ) => $checkbox.value
							  ),
						this.#checkboxes[ 0 ]
					);
				}
			},
			{ signal }
		);

		this.#showChosen();
	}
}
customElements.define( 'checkboxes-field', CheckboxesFieldElement );
