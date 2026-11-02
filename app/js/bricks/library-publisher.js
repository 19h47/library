import { store } from 'library-app/app';
import { fill, fillAuthors, fillPills, pageLink } from 'library-app/render';
import { urlOf } from 'library-app/router';
import { TermPage } from 'library-app/term-page';

/**
 * The page of a collection, or of a publisher: its name, a sub-collection after the collections it is part of, its
 * publisher and the collection it is part of, its description, how many of its books are read and the completion they
 * count for, its collections, its authors, then its books by collection, in the order of their number.
 */
class LibraryPublisherElement extends TermPage {
	static page = 'publisher';

	fillPage( publisher ) {
		fill( this, {
			publisher: publisher.publisher?.name ?? '',
			collection: publisher.collection?.name ?? '',
		} );

		// The publisher above, and the collection above a sub-collection.
		[ 'publisher', 'collection' ].forEach( ( key ) => {
			if ( publisher[ key ] ) {
				this.querySelector( `[data-field="${ key }"]` ).href = urlOf( { view: 'publisher', id: publisher[ key ].id } );
			}
		} );

		fillPills(
			this.querySelector( '[data-list="collections"]' ),
			publisher.collections.map( ( collection ) => pageLink( 'publisher', collection.id, collection.name ) )
		);
	}

	subscriptions() {
		const $authors = this.querySelector( '[data-row="authors"]' );

		// Loaded with its authors.
		return [ store.select( ( state ) => state.publisher.authors, ( authors ) => fillAuthors( $authors, authors, store.get().publisher.total ) ) ];
	}
}
customElements.define( 'library-publisher', LibraryPublisherElement );
