import { __, roles } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import {
	formatDate,
	formatNumber,
	formatReadingTime,
	formatTranslation,
	listFormat,
} from 'library-app/format';
import { fill, fillCover, fillHtml, linkPeople } from 'library-app/render';
import { focusTitle, showBusy } from 'library-app/behaviors';

/** @import { Book } from 'library-app/api' */

/**
 * The page of a book: its cover, credits and title, its notes, its reading, its series and its fields; a book not
 * loaded yet, such as one of an achievement, says it is loading, the page open already. Another book opened from it,
 * such as a volume of its series, takes the focus to its title, as the view does when it opens. Its button Edit book
 * opens the form of the book over it.
 */
class LibraryBookElement extends Brick {
	connect( signal ) {
		/** @type {HTMLElement} */
		const $title = this.querySelector( '.library-heading' );
		const $edit = this.querySelector( '[data-action="edit"]' );
		let shown = 0;
		// The book drawn, drawn again only once changed: gone back to, its page keeps its links, and the focus one had.
		let drawn = null;

		$edit.addEventListener(
			'click',
			() => store.openEdit( store.get().book ),
			{ signal }
		);

		// The book open, or the one to open while it loads.
		const show = () => {
			const { book: id, books, moved } = store.get();
			const book = books[ id ];
			const isLoading = Boolean( id ) && ! book;

			// The book before it, if any, stays out of sight.
			showBusy( this, isLoading );
			fill( this, {
				status: isLoading ? __( 'Loading…', 'library' ) : '',
			} );

			if ( ! book ) {
				shown = 0;
				return;
			}

			if ( book !== drawn ) {
				this.#render( book );
				drawn = book;
			}

			// The same book saved keeps the focus where it is; a book gone back to, the view gives it.
			if ( book.id !== shown && 'forward' === moved ) {
				requestAnimationFrame( () => focusTitle( $title ) );
			}

			shown = book.id;
		};

		// The book open once loaded, else its ID while it loads: each changes once.
		return [
			store.select(
				( state ) => state.books[ state.book ] ?? state.book,
				show
			),
		];
	}

	/**
	 * Draws the book.
	 *
	 * @param {Book} book The book.
	 */
	#render( book ) {
		fillCover( this.querySelector( '[data-field="cover"]' ), book.cover );
		fill( this, {
			credits: book.credits,
			title: book.title,
			translation: formatTranslation( book ),
			excerpt: book.excerpt,
			authors: listFormat.format(
				book.authors.map( ( author ) => author.name )
			),
			isbn: book.isbn,
			pages: book.pages ? formatNumber( book.pages ) : '',
			edition_date: book.edition_date
				? formatDate( book.edition_date )
				: '',
			published_date: book.published_date
				? formatDate( book.published_date )
				: '',
			reading_time: formatReadingTime( book ),
		} );
		linkPeople(
			this.querySelector( '[data-field="authors"]' ),
			book.authors
		);
		// The other names of the book, by role, such as its colorists; the translators, in the translation line, have no field.
		fill(
			this,
			Object.fromEntries(
				roles.map( ( role ) => [
					role,
					listFormat.format(
						book[ role ].map( ( name ) => name.name )
					),
				] )
			)
		);
		roles.forEach( ( role ) => {
			const $field = this.querySelector( `[data-field="${ role }"]` );

			if ( $field ) {
				linkPeople( $field, book[ role ] );
			}
		} );
		fillHtml( this.querySelector( '[data-html="content"]' ), book.content );
	}
}
customElements.define( 'library-book', LibraryBookElement );
