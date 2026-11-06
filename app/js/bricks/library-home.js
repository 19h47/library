import { isHome, store } from 'library-app/store';
import { Brick } from 'library-app/brick';

/** The home: the books view without a query, above the books. */
class LibraryHomeElement extends Brick {
	connect() {
		return [
			store.select( isHome, ( isShown ) => {
				this.hidden = ! isShown;
			} ),
		];
	}
}
customElements.define( 'library-home', LibraryHomeElement );
