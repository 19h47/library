import { sprintf, store, strings } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatNumber, formatState, join, listFormat, plural, pluralRules } from 'library-app/format';
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
					const pseudonyms = strings.pseudonyms[ pluralRules.select( author.pseudonyms.length ) ] ?? strings.pseudonyms.other;
					const rating = author.rating && sprintf( strings.averageRating, formatNumber( author.rating ) );

					fill( this, {
						status: author.error || ( author.loaded && ! author.total ? strings.noBooks : '' ),
						name: author.name,
						pseudonyms: author.pseudonyms.length ? sprintf( pseudonyms, listFormat.format( author.pseudonyms ) ) : '',
						summary: author.name ? join( [ plural( author.total, strings.books ), plural( author.read, strings.booksRead ), rating ] ) : '',
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
					const achievement = { ...completion, url: '', author: 0 };

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
		const published = book.published_date && sprintf( strings.publishedIn, book.published_date.slice( 0, 4 ) );

		return join( [ published, formatState( book ) ] );
	}
}
customElements.define( 'library-author', LibraryAuthorElement );
