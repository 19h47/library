import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { renderCover } from 'library-app/render';
import { openBooks } from 'library-app/behaviors';

// How many covers show.
const LATEST_BOOKS = 12;

/** The covers of the latest books read, which open them; hidden until the diary has readings. */
class LibraryLatestElement extends Brick {
	connect( signal ) {
		const $list = this.querySelector( 'ul' );

		openBooks( $list, signal );

		return [
			store.select(
				( state ) => state.diary.items,
				( months ) => {
					const { books } = store.get();
					const readings = months.flatMap( ( month ) => month.readings );
					// A book read again shows once.
					const ids = [ ...new Set( readings.map( ( reading ) => reading.id ) ) ].filter( ( id ) => id in books ).slice( 0, LATEST_BOOKS );

					$list.replaceChildren( ...ids.map( ( id ) => renderCover( books[ id ] ) ) );
					this.hidden = ! ids.length;
				}
			),
		];
	}
}
customElements.define( 'library-latest', LibraryLatestElement );
