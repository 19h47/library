import { __, _n, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatBooks, formatNumber, join } from 'library-app/format';
import { fill, renderCover } from 'library-app/render';
import { urlOf } from 'library-app/router';

/**
 * The volumes of the series of the book open, on its page: their covers in reading order, as the home shows covers,
 * the book open among them and in view, which open the others; the name of the series links to its page, its count says
 * how many are read, and each cover says its reading state to assistive technologies. Hidden for a book alone.
 */
class LibraryVolumesElement extends Brick {
	connect() {
		const $list = this.querySelector( 'ul' );
		const $name = this.querySelector( '[data-field="name"]' );
		const render = () => {
			const { books, book: id, volumes: series } = store.get();
			const isShown =
				id &&
				books[ id ]?.series === series.name &&
				series.items.length > 1;

			this.hidden = ! isShown;

			if ( ! isShown ) {
				$list.replaceChildren();
				return;
			}

			const { series_id: seriesId } = books[ id ];
			const readCount = series.items.filter(
				( item ) => books[ item ].read
			).length;
			/* translators: %s: number of books read, after the number of books of a series. */
			const read = sprintf(
				_n( '%s read', '%s read', readCount, 'library' ),
				formatNumber( readCount )
			);

			fill( this, {
				name: series.name,
				count: join( [ formatBooks( series.total ), read ] ),
			} );
			$name.href = urlOf( { view: 'series', id: seriesId } );
			$list.replaceChildren(
				...series.items.map( ( item ) =>
					renderCover(
						this.#volume( books[ item ], series.name ),
						this.#state( books[ item ] )
					)
				)
			);

			const $current = $list.querySelector( `[data-id="${ id }"]` );

			$current
				.querySelector( 'a' )
				.setAttribute( 'aria-current', 'page' );
			// Once the page shows it: the book open where the first one starts, unless the page was left meanwhile.
			requestAnimationFrame( () => {
				if ( $current.isConnected ) {
					$list.scrollLeft =
						$current.offsetLeft -
						$list.firstElementChild.offsetLeft;
				}
			} );
		};

		return [
			store.select( ( state ) => state.book, render ),
			store.select( ( state ) => state.volumes, render ),
		];
	}

	/**
	 * A volume as its cover shows it among the others: its title without the series it starts with, "vol. 21: The
	 * Castafiore Emerald", which a book without cover shows.
	 * @param book
	 * @param name
	 */
	#volume( book, name ) {
		const title = book.title.startsWith( name )
			? book.title.slice( name.length ).replace( /^[,:\s]+/u, '' )
			: book.title;

		return { ...book, title: title || book.title };
	}

	/**
	 * The reading state of a volume: being read, or read.
	 * @param book
	 */
	#state( book ) {
		if ( book.in_progress ) {
			return __( 'In progress', 'library' );
		}

		return book.read ? __( 'Read', 'library' ) : '';
	}
}
customElements.define( 'library-volumes', LibraryVolumesElement );
