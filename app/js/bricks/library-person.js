import { __, _n, sprintf, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatBooks, formatNumber, formatState, join, listFormat } from 'library-app/format';
import { fill, renderAchievement, renderBook, renderCloseAchievement } from 'library-app/render';
import { followBooks, observeEnd, openBooks } from 'library-app/behaviors';

/**
 * The page of a person: their names, their description, their reading, the pages of their books and the completion
 * their books count for, then their books, in the order they first came out, loading as the end of the list comes.
 */
class LibraryPersonElement extends Brick {
	#id = 0;
	#ids = [];

	connect( signal ) {
		const $description = this.querySelector( '[data-html="description"]' );
		const $completion = this.querySelector( '[data-list="completion"]' );
		const $items = this.querySelector( '[data-list="books"]' );
		const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMorePersonBooks() );

		openBooks( $items, signal );

		return [
			followBooks( $items, ( book ) => this.#state( book ) ),
			store.select(
				( state ) => state.person,
				( person ) => {
					const pseudonyms = sprintf(
						/* translators: %s: list of the pseudonyms of a person. */
						_n( 'Pseudonym: %s', 'Pseudonyms: %s', person.pseudonyms.length, 'library' ),
						listFormat.format( person.pseudonyms )
					);
					/* translators: %s: number of books read, after the number of books of a person. */
					const read = sprintf( _n( '%s read', '%s read', person.read, 'library' ), formatNumber( person.read ) );
					/* translators: %s: average rating of the books of a person, e.g. 7.5. */
					const rating = person.rating && sprintf( __( '%s/10 on average', 'library' ), formatNumber( person.rating ) );
					/* translators: %s: number of pages. */
					const bookPages = person.bookPages && sprintf( _n( '%s page', '%s pages', person.bookPages, 'library' ), formatNumber( person.bookPages ) );

					fill( this, {
						status: person.error || ( person.loaded && ! person.total ? __( 'No book found.', 'library' ) : '' ),
						name: person.name,
						pseudonyms: person.pseudonyms.length ? pseudonyms : '',
						summary: person.name ? join( [ formatBooks( person.total ), read, rating, bookPages ] ) : '',
					} );

					// HTML as the API renders it, among the few tags core keeps when it saves a description.
					$description.innerHTML = person.description;
					$description.hidden = ! person.description;
				}
			),
			store.select(
				( state ) => state.person.completion,
				( completion ) => {
					$completion.hidden = ! completion;

					if ( ! completion ) {
						$completion.replaceChildren();
						return;
					}

					// The page is the person's: their completion links nowhere.
					const achievement = { ...completion, target: null };

					$completion.replaceChildren(
						'total' in achievement ? renderCloseAchievement( achievement, 'library-person-completion' ) : renderAchievement( achievement )
					);
				}
			),
			store.select(
				( state ) => state.person.items,
				( ids ) => {
					const { books, person } = store.get();
					// A next page adds to the list; another person, or a book that left their books, starts it again.
					const isNextPage = person.id === this.#id && this.#ids.every( ( id, index ) => id === ids[ index ] );
					const start = isNextPage ? this.#ids.length : 0;

					if ( ! start ) {
						$items.replaceChildren();
					}

					$items.append(
						...ids
							.slice( start )
							.filter( ( id ) => id in books )
							.map( ( id ) => renderBook( books[ id ], this.#state( books[ id ] ) ) )
					);
					this.#id = person.id;
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
customElements.define( 'library-person', LibraryPersonElement );
