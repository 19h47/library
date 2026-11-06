import { isHome, store } from 'library-app/store';
import { Brick } from 'library-app/brick';

/** The name of the app, which leads to its home: the books without their search, at their top. Marked current there. */
class LibraryAppNameElement extends Brick {
	connect() {
		const $link = this.querySelector( 'a' );

		return [
			store.select(
				isHome,
				( isShown ) => {
					if ( isShown ) {
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
