import { __ } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { fillBookPage } from 'library-app/render';
import { fill } from 'library-app/dom';
import { focusTitle, showBusy } from 'library-app/behaviors';

/**
 * The page of a book, which fillBookPage() fills: its cover, its title above its subtitle, its credits and its
 * translation as on its title page, its notes, its reading, its series and its fields, those of its work, of its
 * edition and of its copy; a book not loaded yet, such as one of an achievement, says it is loading, the page open
 * already. Another book opened from it, such as a volume of its series, takes the focus to its title, as the view does
 * when it opens. Its button Edit book opens the form of the book over it.
 */
class LibraryBookElement extends Brick {
	connect( signal ) {
		/** @type {HTMLElement} */
		const $title = this.querySelector( '[data-part="title"]' );
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
				fillBookPage( this, book );
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
}
customElements.define( 'library-book', LibraryBookElement );
