import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';

/** The button that shows the lists of books as a grid of their covers, or as cards again. */
class LibraryCoversToggleElement extends Brick {
	connect( signal ) {
		const $button = this.querySelector( 'button' );

		$button.addEventListener( 'click', () => store.toggleCovers(), {
			signal,
		} );

		return [
			store.select(
				( state ) => state.covers,
				( covers ) => {
					$button.setAttribute( 'aria-pressed', covers.toString() );
				}
			),
		];
	}
}
customElements.define( 'library-covers-toggle', LibraryCoversToggleElement );
