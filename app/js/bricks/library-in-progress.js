import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { renderCover } from 'library-app/render';
import { openBooks } from 'library-app/behaviors';

/** The covers of the books being read, which open them; hidden when none is. */
class LibraryInProgressElement extends Brick {
	connect( signal ) {
		const $list = this.querySelector( 'ul' );

		openBooks( $list, signal );

		return [
			store.select(
				( state ) => state.inProgress.items,
				( ids ) => {
					const { books } = store.get();

					$list.replaceChildren( ...ids.map( ( id ) => renderCover( books[ id ] ) ) );
					this.hidden = ! ids.length;
				}
			),
		];
	}
}
customElements.define( 'library-in-progress', LibraryInProgressElement );
