import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** The filter of the books by form, such as the comics. */
class LibraryFormFilterElement extends Brick {
	connect( signal ) {
		const $select = this.querySelector( 'select' );

		$select.addEventListener( 'change', () => store.setForm( $select.value ), { signal } );

		return [
			store.select(
				( state ) => state.form,
				( form ) => {
					$select.value = form;
				}
			),
		];
	}
}
customElements.define( 'library-form-filter', LibraryFormFilterElement );
