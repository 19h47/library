import { Brick } from 'library-app/brick';

/** Says the app is offline, as the browser tells: it then shows what it loaded before, and saves nothing. */
class LibraryOfflineElement extends Brick {
	connect( signal ) {
		const show = () => {
			this.hidden = navigator.onLine;
		};

		addEventListener( 'online', show, { signal } );
		addEventListener( 'offline', show, { signal } );
		show();

		return [];
	}
}
customElements.define( 'library-offline', LibraryOfflineElement );
