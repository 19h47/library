import { __, sprintf } from 'library-app/app';
import { PAGE_VIEWS } from 'library-app/api';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatRecap } from 'library-app/format';
import { routeOf, urlOf } from 'library-app/router';

/**
 * The links to the views, which mark the current one; the one to the books leads to their search. Their row at the top
 * of the page scrolls sideways where it is too narrow: it scrolls as little as it takes for the current one to show
 * whole, the page staying where it is. On a phone, they are the tabs of a bar at the bottom of the screen, all in sight.
 * The document is named after what shows, before the name of the app, as WordPress names a page: the book, the person,
 * the series or the collection open, the recap of a year, the search, or the view; the home by the name of the app alone.
 */
class LibraryNavElement extends Brick {
	connect() {
		const $row = this.querySelector( '.library-nav' );
		const $links = [ ...this.querySelectorAll( 'a' ) ];
		const $books = $links.find(
			( $link ) => 'books' === routeOf( $link.href ).view
		);
		const appName = document.title;
		const labels = Object.fromEntries(
			$links.map( ( $link ) => [
				routeOf( $link.href ).view,
				$link.textContent.trim(),
			] )
		);
		const nameOf = ( state ) => {
			if ( 'book' === state.view ) {
				return state.books[ state.book ]?.title ?? '';
			}

			// The page of a person, a series or a collection, once it is loaded.
			if ( PAGE_VIEWS.includes( state.view ) ) {
				return state[ state.view ].name;
			}

			if ( 'recap' === state.view ) {
				return formatRecap( state.recap.year );
			}

			if ( 'books' === state.view ) {
				return state.query.trim();
			}

			return labels[ state.view ] ?? '';
		};

		return [
			store.select( nameOf, ( name ) => {
				document.title = name
					? sprintf(
							/* translators: 1: what the page shows, such as a book or a view, 2: name of the app. */
							__( '%1$s – %2$s', 'library' ),
							name,
							appName
					  )
					: appName;
			} ),
			store.select(
				( state ) => state.query.trim(),
				( query ) => {
					$books.href = urlOf( { query } );
				}
			),
			store.select(
				( state ) => state.view,
				( view ) =>
					$links.forEach( ( $link ) => {
						if ( view !== routeOf( $link.href ).view ) {
							$link.removeAttribute( 'aria-current' );
							return;
						}

						// The brick is as wide as the content, where the row starts and ends.
						const content = this.getBoundingClientRect();
						const link = $link.getBoundingClientRect();
						const left =
							Math.max( 0, link.right - content.right ) +
							Math.min( 0, link.left - content.left );

						$link.setAttribute( 'aria-current', 'page' );
						$row.scrollBy( { left } );
					} )
			),
		];
	}
}
customElements.define( 'library-nav', LibraryNavElement );
