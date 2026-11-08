import { Brick } from 'library-app/brick';

/**
 * A field of several choices: a pill for each choice made, which removes it, and a button that opens all of them in a
 * sheet, a checkbox each. The checkboxes are the field; the pills follow them, as the user checks them, as a reset of
 * the form checks its defaults again, and as the form of a book checks the ones of a book, which tells with a change.
 */
class LibraryChoicesElement extends Brick {
	connect( signal ) {
		/** @type {HTMLButtonElement} */
		const $add = this.querySelector( '[command="show-modal"]' );
		const $pills = /** @type {HTMLButtonElement[]} */ (
			Array.from( this.querySelectorAll( '[data-value]' ) )
		);
		const $checkboxes = [ ...this.querySelectorAll( 'input' ) ];
		/** @type {( $pill: HTMLElement ) => HTMLInputElement} */
		const getCheckbox = ( $pill ) =>
			$checkboxes.find(
				( $checkbox ) => $checkbox.value === $pill.dataset.value
			);
		const showChosen = () => {
			$pills.forEach( ( $pill ) => {
				$pill.hidden = ! getCheckbox( $pill ).checked;
			} );
		};

		this.addEventListener( 'change', showChosen, { signal } );
		// The reset event comes before the form resets: the pills follow once it has.
		this.closest( 'form' )?.addEventListener(
			'reset',
			() => queueMicrotask( showChosen ),
			{ signal }
		);
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

				const $checkbox = getCheckbox( $pill );

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

		showChosen();

		return [];
	}
}
customElements.define( 'library-choices', LibraryChoicesElement );
