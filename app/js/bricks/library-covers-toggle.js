import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';

/** The checkbox that shows the lists of books as a grid of their covers, or as cards. */
class LibraryCoversToggleElement extends Brick {
	connect( signal ) {
		const $checkbox = this.querySelector( 'input' );

		$checkbox.addEventListener(
			'change',
			() => store.setCovers( $checkbox.checked ),
			{ signal }
		);

		return [
			store.select(
				( state ) => state.covers,
				( covers ) => {
					$checkbox.checked = covers;
				}
			),
		];
	}
}
customElements.define( 'library-covers-toggle', LibraryCoversToggleElement );
