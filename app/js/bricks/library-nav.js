import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { openViews } from 'library-app/behaviors';

/**
 * The links to the views: they switch views without reloading, and mark the current one. Their row scrolls sideways:
 * it scrolls as little as it takes for the current one to show whole, the page staying where it is.
 */
class LibraryNavElement extends Brick {
	connect( signal ) {
		const $row = this.querySelector( '.library-nav' );
		const $links = [ ...this.querySelectorAll( 'a[data-view]' ) ];

		openViews( this, signal );

		return [
			store.select(
				( state ) => state.view,
				( view ) =>
					$links.forEach( ( $link ) => {
						if ( view !== $link.dataset.view ) {
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
