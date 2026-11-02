import { __, _n, publisherUrl, sprintf, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatBooks, formatNumber, formatState, join } from 'library-app/format';
import { fill, fillFolded, publisherLink, renderAchievement, renderAuthor, renderBook, renderCloseAchievement } from 'library-app/render';
import { followBooks, observeEnd, openBooks, openLinks, renderKeyed } from 'library-app/behaviors';

/**
 * The page of a collection, or of a publisher: its name, a sub-collection after the collections it is part of, its
 * publisher and the collection it is part of, its description, how many of its books are read and the completion they
 * count for, its collections, its authors, then its books by collection, in the order of their number, loading as the
 * end of the list comes. Opened, its name takes the focus once it is loaded.
 */
class LibraryPublisherElement extends Brick {
	connect( signal ) {
		const $name = this.querySelector( '[data-field="name"]' );
		const $links = {
			publisher: this.querySelector( '[data-field="publisher"]' ),
			collection: this.querySelector( '[data-field="collection"]' ),
		};
		const $description = this.querySelector( '[data-html="description"]' );
		const $completion = this.querySelector( '[data-list="completion"]' );
		const $collections = this.querySelector( '[data-list="collections"]' );
		const $authors = this.querySelector( '[data-row="authors"]' );
		const $items = this.querySelector( '[data-list="books"]' );
		const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMorePageBooks( 'publisher' ) );

		openBooks( $items, signal );
		openLinks( this, signal );

		return [
			followBooks( $items, formatState ),
			store.select(
				( state ) => state.publisher,
				( publisher ) => {
					/* translators: %s: number of books read, after the number of books of a series. */
					const read = sprintf( _n( '%s read', '%s read', publisher.read, 'library' ), formatNumber( publisher.read ) );

					fill( this, {
						status: publisher.error || ( publisher.loaded && ! publisher.total ? __( 'No book found.', 'library' ) : '' ),
						name: publisher.name,
						publisher: publisher.publisher?.name ?? '',
						collection: publisher.collection?.name ?? '',
						summary: publisher.name ? join( [ formatBooks( publisher.total ), read ] ) : '',
					} );

					// The publisher above, and the collection above a sub-collection.
					Object.entries( $links ).forEach( ( [ key, $link ] ) => {
						if ( publisher[ key ] ) {
							$link.href = publisherUrl + publisher[ key ].id;
							$link.dataset.publisher = publisher[ key ].id;
						}
					} );

					// HTML as the API renders it, among the few tags core keeps when it saves a description.
					$description.innerHTML = publisher.description;
					$description.hidden = ! publisher.description;

					$collections.replaceChildren(
						...publisher.collections.map( ( collection ) => {
							const $item = document.createElement( 'li' );
							const $link = publisherLink( collection.id, collection.name );

							$link.className = 'app-pill';
							$item.append( $link );

							return $item;
						} )
					);
					$collections.closest( '[data-row]' ).hidden = ! publisher.collections.length;
				}
			),
			store.select(
				( state ) => state.publisher.authors,
				( authors ) => {
					// Loaded with its authors.
					const { total } = store.get().publisher;

					// How many of its books an author wrote, unless it has only one.
					fillFolded(
						$authors,
						authors.map( ( author ) => renderAuthor( author, total > 1 ? formatBooks( author.books ) : '' ) ),
						/* translators: %s: number of authors of a series, after the first ones. */
						( folded ) => sprintf( _n( '%s other author', '%s other authors', folded, 'library' ), formatNumber( folded ) )
					);
				}
			),
			store.select(
				( state ) => state.publisher.completion,
				( completion ) => {
					$completion.hidden = ! completion;

					if ( ! completion ) {
						$completion.replaceChildren();
						return;
					}

					// The page is the collection's: its completion links nowhere.
					const achievement = { ...completion, target: null };

					$completion.replaceChildren(
						'total' in achievement ? renderCloseAchievement( achievement, 'library-publisher-completion' ) : renderAchievement( achievement )
					);
				}
			),
			store.select(
				( state ) => state.publisher.name,
				( name ) => {
					if ( name && 'forward' === store.get().moved ) {
						$name.focus( { preventScroll: true } );
					}
				}
			),
			store.select(
				( state ) => state.publisher.items,
				( ids ) => {
					const { books } = store.get();

					renderKeyed(
						$items,
						ids.filter( ( id ) => id in books ),
						( id ) => renderBook( books[ id ], formatState( books[ id ] ) )
					);
					observe();
				}
			),
		];
	}
}
customElements.define( 'library-publisher', LibraryPublisherElement );
