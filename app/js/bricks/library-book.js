import { __, roles, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import {
	formatDate,
	formatLanguage,
	formatNumber,
	formatTranslation,
	listFormat,
} from 'library-app/format';
import {
	fill,
	fillCover,
	fillHtml,
	linkPeople,
	pageLink,
} from 'library-app/render';
import { focusTitle, showBusy } from 'library-app/behaviors';
import { getPeople } from 'library-app/api';

/** @import { Book } from 'library-app/api' */

/**
 * The page of a book: its cover, its title above its subtitle, its credits and its translation as on its title page, its notes, its reading, its series and its fields, those of its work, of its edition and of its copy; a
 * book not loaded yet, such as one of an achievement, says it is loading, the page open already. Another book opened
 * from it, such as a volume of its series, takes the focus to its title, as the view does when it opens. Its button Edit
 * book opens the form of the book over it.
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
		const authors = getPeople( book, 'author' );
		const $publishers = this.querySelector( '[data-field="publishers"]' );
		const date = ( /** @type {string} */ value ) =>
			value ? formatDate( value ) : '';
		const languages = ( /** @type {string[]} */ codes ) =>
			listFormat.format( codes.map( formatLanguage ) );

		fillCover( this.querySelector( '[data-field="cover"]' ), book.cover );
		fill( this, {
			credits: book.credits,
			main_title: book.main_title,
			subtitle: book.subtitle,
			translation: formatTranslation( book ),
			excerpt: book.excerpt,
			authors: listFormat.format(
				authors.map( ( author ) => author.name )
			),
			published_date: date( book.published_date ),
			languages: languages( book.languages ),
			form: book.form,
			publishers: book.publishers
				.map( ( publisher ) => publisher.name )
				.join( ', ' ),
			edition_date: date( book.edition_date ),
			isbn: book.isbn,
			pages: book.pages ? formatNumber( book.pages ) : '',
			edition_languages: languages( book.edition_languages ),
			printed_date: date( book.printed_date ),
			added_date: date( book.added_date ),
		} );
		linkPeople( this.querySelector( '[data-field="authors"]' ), authors );
		// Its publisher, then its collections, each to its page, as in its credits, its number after its collection.
		$publishers.replaceChildren(
			...book.publishers.flatMap( ( publisher, index ) => [
				...( index ? [ ', ' ] : [] ),
				pageLink( 'publisher', publisher.id, publisher.name ),
			] )
		);

		if ( book.collection_number ) {
			$publishers.append(
				' ',
				sprintf(
					/* translators: %s: number of the book in its collection. */
					__( 'no. %s', 'library' ),
					book.collection_number
				)
			);
		}
		// The other names of the book, by role, such as its colorists; the translators, in the translation line, have no field.
		fill(
			this,
			Object.fromEntries(
				roles.map( ( role ) => [
					role,
					listFormat.format(
						getPeople( book, role ).map( ( person ) => person.name )
					),
				] )
			)
		);
		roles.forEach( ( role ) => {
			const $field = this.querySelector( `[data-field="${ role }"]` );

			if ( $field ) {
				linkPeople( $field, getPeople( book, role ) );
			}
		} );
		fillHtml( this.querySelector( '[data-html="content"]' ), book.content );
	}
}
customElements.define( 'library-book', LibraryBookElement );
