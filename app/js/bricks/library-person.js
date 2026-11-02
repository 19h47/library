import { __, _n, sprintf, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatBooks, formatNumber, formatState, join, listFormat } from 'library-app/format';
import { fill, fillCompletion, fillFolded, renderBook, renderSeries } from 'library-app/render';
import { followBooks, observeEnd, openBooks, renderKeyed } from 'library-app/behaviors';

/**
 * The page of a person: their names, their description, their reading, the pages of their books and the completion
 * their books count for, their series, then their books, in the order they first came out, loading as the end of the
 * list comes. Opened, their name takes the focus once it is loaded.
 */
class LibraryPersonElement extends Brick {
	connect( signal ) {
		const $description = this.querySelector( '[data-html="description"]' );
		const $name = this.querySelector( '[data-field="name"]' );
		const $completion = this.querySelector( '[data-list="completion"]' );
		const $series = this.querySelector( '[data-row="series"]' );
		const $items = this.querySelector( '[data-list="books"]' );
		const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMorePageBooks( 'person' ) );

		openBooks( $items, signal );

		return [
			followBooks( $items, ( book ) => this.#state( book ) ),
			store.select(
				( state ) => state.person,
				( person ) => {
					const otherNames = sprintf(
						/* translators: %s: list of the other names of a person, such as a pseudonym or their birth name. */
						_n( 'Other name: %s', 'Other names: %s', person.otherNames.length, 'library' ),
						listFormat.format( person.otherNames )
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
						otherNames: person.otherNames.length ? otherNames : '',
						summary: person.name ? join( [ formatBooks( person.total ), read, rating, bookPages ] ) : '',
					} );

					// HTML as the API renders it, among the few tags core keeps when it saves a description.
					$description.innerHTML = person.description;
					$description.hidden = ! person.description;
				}
			),
			store.select(
				( state ) => state.person.name,
				( name ) => {
					if ( name && 'forward' === store.get().moved ) {
						$name.focus( { preventScroll: true } );
					}
				}
			),
			store.select(
				( state ) => state.person.completion,
				( completion ) => fillCompletion( $completion, completion, 'library-person-completion' )
			),
			store.select(
				( state ) => state.person.series,
				( series ) =>
					fillFolded(
						$series,
						series.map( ( item ) => {
							/* translators: %s: number of books read, after the number of books of a person in a series. */
							const read = sprintf( _n( '%s read', '%s read', item.read, 'library' ), formatNumber( item.read ) );

							return renderSeries( item, join( [ formatBooks( item.books ), read ] ) );
						} ),
						/* translators: %s: number of series of a person, after the first ones. */
						( folded ) => sprintf( _n( '%s other series', '%s other series', folded, 'library' ), formatNumber( folded ) )
					)
			),
			store.select(
				( state ) => state.person.items,
				( ids ) => {
					const { books } = store.get();

					// A card per book of the person, kept from a page to the next one, as from a person to another one who shares it.
					renderKeyed(
						$items,
						ids.filter( ( id ) => id in books ),
						( id ) => renderBook( books[ id ], this.#state( books[ id ] ) )
					);
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
