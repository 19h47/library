import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/**
 * A view, shown when it is the current one. Opened, it gives the focus to its title, if it has one to take it, such as
 * the page of a book; gone back to, to what had it when it hid, such as the card of the book opened from it.
 */
class LibraryViewElement extends Brick {
	#focused = null;

	connect() {
		return [
			store.select(
				( state ) => state.view,
				( view ) => {
					const isShown = view === this.getAttribute( 'name' );
					const appears = isShown && this.hidden;

					if ( ! isShown && ! this.hidden && this.contains( document.activeElement ) ) {
						this.#focused = document.activeElement;
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

	#focus() {
		const { moved } = store.get();
		const $title = this.querySelector( 'h2[tabindex="-1"]' );

		if ( 'back' === moved && this.#focused?.isConnected && this.contains( this.#focused ) ) {
			this.#focused.focus( { preventScroll: true } );
		} else if ( 'forward' === moved ) {
			$title?.focus( { preventScroll: true } );
		}
	}
}
customElements.define( 'library-view', LibraryViewElement );
