import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { routeOf, urlOf } from 'library-app/router';

/**
 * The links to the views, which mark the current one; the one to the books leads to their search. Their row scrolls
 * sideways: it scrolls as little as it takes for the current one to show whole, the page staying where it is.
 */
class LibraryNavElement extends Brick {
	connect() {
		const $row = this.querySelector( '.library-nav' );
		const $links = [ ...this.querySelectorAll( 'a' ) ];
		const $books = $links.find( ( $link ) => 'books' === routeOf( $link.href ).view );

		return [
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
						const left = Math.max( 0, link.right - content.right ) + Math.min( 0, link.left - content.left );

						$link.setAttribute( 'aria-current', 'page' );
						$row.scrollBy( { left } );
					} )
			),
		];
	}
}
customElements.define( 'library-nav', LibraryNavElement );
