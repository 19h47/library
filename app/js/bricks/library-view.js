import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** A view, shown when it is the current one. */
class LibraryViewElement extends Brick {
	connect() {
		return [
			store.select(
				( state ) => state.view,
				( view ) => {
					this.hidden = view !== this.getAttribute( 'name' );
				}
			),
		];
	}
}
customElements.define( 'library-view', LibraryViewElement );
