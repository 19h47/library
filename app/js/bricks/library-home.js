import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { openViews } from 'library-app/behaviors';

/** The home: the books view without a query, above the books. */
class LibraryHomeElement extends Brick {
	connect( signal ) {
		openViews( this, signal );

		return [
			store.select(
				( state ) => ! state.query.trim(),
				( isHome ) => {
					this.hidden = ! isHome;
				}
			),
		];
	}
}
customElements.define( 'library-home', LibraryHomeElement );
