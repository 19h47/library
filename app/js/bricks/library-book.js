import { roles, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatDate, formatNumber, formatTranslation, listFormat } from 'library-app/format';
import { fill, fillCover, linkPeople } from 'library-app/render';
import { openLinks } from 'library-app/behaviors';

/**
 * The book open, in a modal dialog; closing it, with its button or Escape, closes the book.
 * Its button Edit book opens the form of the book over it.
 */
class LibraryBookElement extends Brick {
	connect( signal ) {
		const $dialog = this.querySelector( 'dialog' );
		const $edit = $dialog.querySelector( '[data-action="edit"]' );

		$dialog.addEventListener( 'close', () => store.closeBook(), { signal } );
		$edit.addEventListener( 'click', () => store.openEdit( store.get().book ), { signal } );
		openLinks( $dialog, signal );

		return [
			store.select(
				( state ) => state.books[ state.book ] ?? null,
				( book ) => {
					if ( ! book ) {
						$dialog.close();
						return;
					}

					this.#render( $dialog, book );
					$dialog.showModal();
					$dialog.scrollTop = 0;
				}
			),
		];
	}

	#render( $dialog, book ) {
		const $content = $dialog.querySelector( '[data-html="content"]' );

		fillCover( $dialog.querySelector( '[data-field="cover"]' ), book.cover );
		fill( $dialog, {
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
		linkPeople( $dialog.querySelector( '[data-field="authors"]' ), book.authors );
		// The other names of the book, by role, such as its colorists; the translators, in the translation line, have no field.
		fill( $dialog, Object.fromEntries( roles.map( ( role ) => [ role, listFormat.format( book[ role ].map( ( name ) => name.name ) ) ] ) ) );
		roles.forEach( ( role ) => {
			const $field = $dialog.querySelector( `[data-field="${ role }"]` );

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
