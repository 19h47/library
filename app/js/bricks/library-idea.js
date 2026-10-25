import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatState } from 'library-app/format';
import { fill, renderBook } from 'library-app/render';
import { followBooks, openBooks } from 'library-app/behaviors';

/** The reading idea of the week, and the next one on demand; hidden until it is loaded. */
class LibraryIdeaElement extends Brick {
	connect( signal ) {
		const $book = this.querySelector( '[data-field="book"]' );
		const $next = this.querySelector( '.library-idea__actions button' );

		openBooks( $book, signal );
		$next.addEventListener( 'click', () => store.nextIdea(), { signal } );

		return [
			followBooks( $book, formatState ),
			store.select(
				( state ) => state.idea,
				( idea ) => {
					const book = store.get().books[ idea.id ];

					this.hidden = ! idea.loaded;
					$next.disabled = idea.loading;
					$next.hidden = idea.total < 2;
					fill( this, { status: idea.error, reason: book ? idea.reason : '' } );
					$book.replaceChildren(
						...( book ? [ renderBook( book, formatState( book ) ) ] : [] )
					);
				}
			),
		];
	}
}
customElements.define( 'library-idea', LibraryIdeaElement );
