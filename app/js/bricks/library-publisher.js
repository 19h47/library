import { __, _n, sprintf, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatBooks, formatNumber, formatState, join } from 'library-app/format';
import { fill, fillAuthors, fillCompletion, fillPills, pageLink, renderBook } from 'library-app/render';
import { followBooks, observeEnd, openBooks, renderKeyed } from 'library-app/behaviors';
import { urlOf } from 'library-app/router';

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
							$link.href = urlOf( { view: 'publisher', id: publisher[ key ].id } );
						}
					} );

					// HTML as the API renders it, among the few tags core keeps when it saves a description.
					$description.innerHTML = publisher.description;
					$description.hidden = ! publisher.description;

					fillPills(
						$collections,
						publisher.collections.map( ( collection ) => pageLink( 'publisher', collection.id, collection.name ) )
					);
				}
			),
			store.select(
				( state ) => state.publisher.authors,
				// Loaded with its authors.
				( authors ) => fillAuthors( $authors, authors, store.get().publisher.total )
			),
			store.select(
				( state ) => state.publisher.completion,
				( completion ) => fillCompletion( $completion, completion, 'library-publisher-completion' )
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
