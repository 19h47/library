import { roles, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatDate, formatNumber, formatTranslation, listFormat } from 'library-app/format';
import { fill, fillCover, linkPeople } from 'library-app/render';
import { openLinks } from 'library-app/behaviors';

/**
 * The page of a book: its cover, credits and title, its notes, its reading, its series and its fields. Another book
 * opened from it, such as a volume of its series, takes the focus to its title, as the view does when it opens. Its
 * button Edit book opens the form of the book over it.
 */
class LibraryBookElement extends Brick {
	connect( signal ) {
		const $title = this.querySelector( '.library-book__title' );
		const $edit = this.querySelector( '[data-action="edit"]' );
		let shown = 0;

		$edit.addEventListener( 'click', () => store.openEdit( store.get().book ), { signal } );
		openLinks( this, signal );

		return [
			store.select(
				( state ) => state.books[ state.book ] ?? null,
				( book ) => {
					if ( ! book ) {
						shown = 0;
						return;
					}

					this.#render( book );

					// The same book saved keeps the focus where it is; a book gone back to, the view gives it.
					if ( book.id !== shown && 'forward' === store.get().moved ) {
						requestAnimationFrame( () => $title.focus( { preventScroll: true } ) );
					}

					shown = book.id;
				}
			),
		];
	}

	#render( book ) {
		const $content = this.querySelector( '[data-html="content"]' );

		fillCover( this.querySelector( '[data-field="cover"]' ), book.cover );
		fill( this, {
			credits: book.credits,
			title: book.title,
			translation: formatTranslation( book ),
			excerpt: book.excerpt,
			authors: listFormat.format( book.authors.map( ( author ) => author.name ) ),
			isbn: book.isbn,
			pages: book.pages ? formatNumber( book.pages ) : '',
			edition_date: book.edition_date ? formatDate( book.edition_date ) : '',
			published_date: book.published_date ? formatDate( book.published_date ) : '',
		} );
		linkPeople( this.querySelector( '[data-field="authors"]' ), book.authors );
		// The other names of the book, by role, such as its colorists; the translators, in the translation line, have no field.
		fill( this, Object.fromEntries( roles.map( ( role ) => [ role, listFormat.format( book[ role ].map( ( name ) => name.name ) ) ] ) ) );
		roles.forEach( ( role ) => {
			const $field = this.querySelector( `[data-field="${ role }"]` );

			if ( $field ) {
				linkPeople( $field, book[ role ] );
			}
		} );
		// HTML as the API renders it, from the notes of the book.
		$content.innerHTML = book.content;
		$content.hidden = ! book.content;
	}
}
customElements.define( 'library-book', LibraryBookElement );
