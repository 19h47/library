import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { renderCurrent } from 'library-app/render';
import { fill } from 'library-app/dom';
import { followBooks, renderKeyed } from 'library-app/behaviors';
import { formatBooks } from 'library-app/format';

/**
 * The books being read, as their covers in a row that scrolls sideways, which open them, their reading in their page;
 * how many there are, some out of sight; hidden when none is. A cover stays while its book is being read.
 */
class LibraryInProgressElement extends Brick {
	connect() {
		const $list = this.querySelector( 'ul' );

		return [
			store.select(
				( state ) => state.inProgress.items,
				( ids ) => {
					const { books } = store.get();

					fill( this, { count: formatBooks( ids.length ) } );
					renderKeyed( $list, ids, ( id ) =>
						renderCurrent( books[ id ] )
					);
					this.hidden = ! ids.length;
				}
			),
			// A book being read that changed, such as its cover, is drawn again in its place. A book finished leaves as the
			// books being read load again: its cover stays until then, its focus with it.
			followBooks( $list, ( $item, book ) => {
				if ( book?.in_progress ) {
					$item.replaceWith( renderCurrent( book ) );
				}
			} ),
		];
	}
}
customElements.define( 'library-in-progress', LibraryInProgressElement );
