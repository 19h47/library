import { Component } from 'library-app/component';

/**
 * The status of the app offline, the root of the component offline-status: it shows while the browser says it is
 * offline, as it goes offline and back online.
 *
 * Its API: none, but its message, which its markup holds.
 */
export class OfflineStatusElement extends Component {
	connect( signal ) {
		const show = () => {
			this.hidden = navigator.onLine;
		};

		addEventListener( 'online', show, { signal } );
		addEventListener( 'offline', show, { signal } );
		show();
	}
}
customElements.define( 'offline-status', OfflineStatusElement );
