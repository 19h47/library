import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { openViews } from 'library-app/behaviors';

/** The links to the views: they switch views without reloading, and mark the current one. */
class LibraryNavElement extends Brick {
	connect( signal ) {
		const $links = [ ...this.querySelectorAll( 'a[data-view]' ) ];

		openViews( this, signal );

		return [
			store.select(
				( state ) => state.view,
				( view ) =>
					$links.forEach( ( $link ) => {
						if ( view === $link.dataset.view ) {
							$link.setAttribute( 'aria-current', 'page' );
						} else {
							$link.removeAttribute( 'aria-current' );
						}
					} )
			),
		];
	}
}
customElements.define( 'library-nav', LibraryNavElement );
