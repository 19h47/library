import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { focusTitle } from 'library-app/behaviors';

/**
 * A view, shown when it is the current one. Opened, it gives the focus to its title, if it has one to take it, such as
 * the page of a book; gone back to, to what had it when it hid, such as the card of the book opened from it.
 */
class LibraryViewElement extends Brick {
	/** @type {HTMLElement|null} */
	#focused = null;

	connect() {
		return [
			store.select(
				( state ) => state.view,
				( view ) => {
					// As the server shows it, until the router reads the URL.
					if ( ! view ) {
						return;
					}

					const isShown = view === this.getAttribute( 'name' );
					const appears = isShown && this.hidden;

					if (
						! isShown &&
						! this.hidden &&
						this.contains( this.ownerDocument.activeElement )
					) {
						this.#focused = /** @type {HTMLElement} */ (
							this.ownerDocument.activeElement
						);
					}

					this.hidden = ! isShown;

					if ( appears ) {
						// Once every brick has filled it.
						requestAnimationFrame( () => this.#focus() );
					}
				}
			),
		];
	}

	/** Gives the focus to what had it gone back to, or to its title opened. */
	#focus() {
		const { moved } = store.get();
		/** @type {HTMLElement} */
		const $title = this.querySelector( 'h2[tabindex="-1"]' );

		if (
			'back' === moved &&
			this.#focused?.isConnected &&
			this.contains( this.#focused )
		) {
			this.#focused.focus( { preventScroll: true } );
		} else if ( 'forward' === moved && $title ) {
			focusTitle( $title );
		}
	}
}
customElements.define( 'library-view', LibraryViewElement );
