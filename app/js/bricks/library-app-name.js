import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** The name of the app, which leads to its home: the books without their search, at their top. Marked current there. */
class LibraryAppNameElement extends Brick {
	connect( signal ) {
		const $link = this.querySelector( 'a' );

		$link.addEventListener(
			'click',
			( event ) => {
				// A new tab or window stays a link.
				if ( event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
					return;
				}

				event.preventDefault();
				store.goHome();
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => 'books' === state.view && ! state.query.trim(),
				( isHome ) => {
					if ( isHome ) {
						$link.setAttribute( 'aria-current', 'page' );
					} else {
						$link.removeAttribute( 'aria-current' );
					}
				}
			),
		];
	}
}
customElements.define( 'library-app-name', LibraryAppNameElement );
