import { __, _n, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { formatBooks, formatNumber, formatState, join, listFormat } from 'library-app/format';
import { fill, fillFolded, renderSeries } from 'library-app/render';
import { TermPage } from 'library-app/term-page';

/**
 * The page of a person: their names, their description, their reading, the pages of their books and the completion
 * their books count for, their series, then their books, in the order they first came out.
 */
class LibraryPersonElement extends TermPage {
	static page = 'person';

	summary( person ) {
		/* translators: %s: average rating of the books of a person, e.g. 7.5. */
		const rating = person.rating && sprintf( __( '%s/10 on average', 'library' ), formatNumber( person.rating ) );
		/* translators: %s: number of pages. */
		const bookPages = person.bookPages && sprintf( _n( '%s page', '%s pages', person.bookPages, 'library' ), formatNumber( person.bookPages ) );

		return [ rating, bookPages ];
	}

	fillPage( person ) {
		const otherNames = sprintf(
			/* translators: %s: list of the other names of a person, such as a pseudonym or their birth name. */
			_n( 'Other name: %s', 'Other names: %s', person.otherNames.length, 'library' ),
			listFormat.format( person.otherNames )
		);

		fill( this, { otherNames: person.otherNames.length ? otherNames : '' } );
	}

	subscriptions() {
		const $series = this.querySelector( '[data-row="series"]' );

		return [
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
		];
	}

	/** The line of a book: the year it first came out, as the list follows it, then its state. */
	line( book ) {
		/* translators: %s: year a book first came out. */
		const published = book.published_date && sprintf( __( 'First published in %s', 'library' ), book.published_date.slice( 0, 4 ) );

		return join( [ published, formatState( book ) ] );
	}
}
customElements.define( 'library-person', LibraryPersonElement );
