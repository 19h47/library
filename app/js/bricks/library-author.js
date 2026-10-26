import { __, _n, sprintf, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatBooks, formatNumber, formatState, join, listFormat } from 'library-app/format';
import { fill, renderAchievement, renderBook, renderCloseAchievement } from 'library-app/render';
import { followBooks, observeEnd, openBooks } from 'library-app/behaviors';

/**
 * The page of an author: their names, their reading and the completion their books count for,
 * then their books, in the order they first came out, loading as the end of the list comes.
 */
class LibraryAuthorElement extends Brick {
	#id = 0;
	#ids = [];

	connect( signal ) {
		const $completion = this.querySelector( '[data-list="completion"]' );
		const $items = this.querySelector( '[data-list="books"]' );
		const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMoreAuthorBooks() );

		openBooks( $items, signal );

		return [
			followBooks( $items, ( book ) => this.#state( book ) ),
			store.select(
				( state ) => state.author,
				( author ) => {
					const pseudonyms = sprintf(
						/* translators: %s: list of the pseudonyms of an author. */
						_n( 'Pseudonym: %s', 'Pseudonyms: %s', author.pseudonyms.length, 'library' ),
						listFormat.format( author.pseudonyms )
					);
					/* translators: %s: number of books read, after the number of books of an author. */
					const read = sprintf( _n( '%s read', '%s read', author.read, 'library' ), formatNumber( author.read ) );
					/* translators: %s: average rating of the books of an author, e.g. 7.5. */
					const rating = author.rating && sprintf( __( '%s/10 on average', 'library' ), formatNumber( author.rating ) );

					fill( this, {
						status: author.error || ( author.loaded && ! author.total ? __( 'No book found.', 'library' ) : '' ),
						name: author.name,
						pseudonyms: author.pseudonyms.length ? pseudonyms : '',
						summary: author.name ? join( [ formatBooks( author.total ), read, rating ] ) : '',
					} );
				}
			),
			store.select(
				( state ) => state.author.completion,
				( completion ) => {
					$completion.hidden = ! completion;

					if ( ! completion ) {
						$completion.replaceChildren();
						return;
					}

					// The page is the author's: their completion links nowhere.
					const achievement = { ...completion, target: null };

					$completion.replaceChildren(
						'total' in achievement ? renderCloseAchievement( achievement, 'library-author-completion' ) : renderAchievement( achievement )
					);
				}
			),
			store.select(
				( state ) => state.author.items,
				( ids ) => {
					const { books, author } = store.get();
					// A next page adds to the list, another author starts it again.
					const start = author.id === this.#id ? this.#ids.length : 0;

					if ( ! start ) {
						$items.replaceChildren();
					}

					$items.append(
						...ids
							.slice( start )
							.filter( ( id ) => id in books )
							.map( ( id ) => renderBook( books[ id ], this.#state( books[ id ] ) ) )
					);
					this.#id = author.id;
					this.#ids = ids;
					observe();
				}
			),
		];
	}

	/** The line of a book: the year it first came out, as the list follows it, then its state. */
	#state( book ) {
		/* translators: %s: year a book first came out. */
		const published = book.published_date && sprintf( __( 'First published in %s', 'library' ), book.published_date.slice( 0, 4 ) );

		return join( [ published, formatState( book ) ] );
	}
}
customElements.define( 'library-author', LibraryAuthorElement );
