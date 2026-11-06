import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatState } from 'library-app/format';
import { fill, renderBook, renderRow } from 'library-app/render';
import { followBooks } from 'library-app/behaviors';

/**
 * The reading idea of the week, and the next one on demand; hidden until it is loaded. Its book as a card, or as a row
 * when it is `compact`, as on the home.
 */
class LibraryIdeaElement extends Brick {
	connect( signal ) {
		const $book = this.querySelector( '[data-field="book"]' );
		/** @type {HTMLButtonElement} */
		const $next = this.querySelector( '[data-action="next-idea"]' );
		const render = this.hasAttribute( 'compact' )
			? renderRow
			: ( book ) => renderBook( book, formatState( book ) );

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
					fill( this, {
						status: idea.error,
						reason: book ? idea.reason : '',
					} );
					$book.replaceChildren(
						...( book ? [ render( book ) ] : [] )
					);
				}
			),
		];
	}
}
customElements.define( 'library-idea', LibraryIdeaElement );
