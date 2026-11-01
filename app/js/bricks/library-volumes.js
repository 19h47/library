import { __, seriesUrl, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatBooks } from 'library-app/format';
import { fill, renderCover } from 'library-app/render';
import { openBooks } from 'library-app/behaviors';

/**
 * The volumes of the series of the book open, on its page: their covers in reading order, each with its reading state,
 * the book open among them and in view, which open the others; the name of the series links to its page. Hidden for a
 * book alone.
 */
class LibraryVolumesElement extends Brick {
	connect( signal ) {
		const $list = this.querySelector( 'ul' );
		const $name = this.querySelector( '[data-field="name"]' );
		const render = () => {
			const { books, book: id, volumes: series } = store.get();
			const isShown = id && books[ id ]?.series === series.name && series.items.length > 1;

			this.hidden = ! isShown;

			if ( ! isShown ) {
				$list.replaceChildren();
				return;
			}

			const { series_id: seriesId } = books[ id ];

			fill( this, { name: series.name, count: formatBooks( series.total ) } );
			$name.href = seriesUrl + seriesId;
			$name.dataset.series = seriesId;
			$list.replaceChildren( ...series.items.map( ( item ) => renderCover( this.#volume( books[ item ], series.name ), this.#state( books[ item ] ) ) ) );

			const $current = $list.querySelector( `[data-id="${ id }"]` );

			$current.querySelector( 'button' ).setAttribute( 'aria-current', 'true' );
			// Once the page shows it: the book open where the first one starts.
			requestAnimationFrame( () => {
				$list.scrollLeft = $current.offsetLeft - $list.firstElementChild.offsetLeft;
			} );
		};

		openBooks( $list, signal );

		return [ store.select( ( state ) => state.book, render ), store.select( ( state ) => state.volumes, render ) ];
	}

	/**
	 * A volume as its cover shows it among the others: its title without the series it starts with, "vol. 21: The
	 * Castafiore Emerald", which a book without cover shows.
	 */
	#volume( book, name ) {
		const title = book.title.startsWith( name ) ? book.title.slice( name.length ).replace( /^[,:\s]+/u, '' ) : book.title;

		return { ...book, title: title || book.title };
	}

	/** The reading state of a volume: being read, or read. */
	#state( book ) {
		if ( book.in_progress ) {
			return __( 'In progress', 'library' );
		}

		return book.read ? __( 'Read', 'library' ) : '';
	}
}
customElements.define( 'library-volumes', LibraryVolumesElement );
