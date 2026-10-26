import { editUrl, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatDate, formatNumber, formatTranslation, listFormat } from 'library-app/format';
import { fill, fillCover, linkAuthors } from 'library-app/render';
import { openLinks } from 'library-app/behaviors';

/** The book open, in a modal dialog; closing it, with its button or Escape, closes the book. */
class LibraryBookElement extends Brick {
	connect( signal ) {
		const $dialog = this.querySelector( 'dialog' );

		$dialog.addEventListener( 'close', () => store.closeBook(), { signal } );
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
		fillCover( $dialog.querySelector( '[data-field="cover"]' ), book.cover );
		fill( $dialog, {
			credits: book.credits,
			title: book.title,
			translation: formatTranslation( book ),
			authors: listFormat.format( book.authors.map( ( author ) => author.name ) ),
			isbn: book.isbn,
			pages: book.pages ? formatNumber( book.pages ) : '',
			edition_date: book.edition_date ? formatDate( book.edition_date ) : '',
			published_date: book.published_date ? formatDate( book.published_date ) : '',
		} );
		linkAuthors( $dialog.querySelector( '[data-field="authors"]' ), book.authors );
		$dialog.querySelector( '[data-field="edit"]' ).href = editUrl + book.id;
	}
}
customElements.define( 'library-book', LibraryBookElement );
