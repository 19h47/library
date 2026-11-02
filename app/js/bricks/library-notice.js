import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';

// How long a notice shows.
const NOTICE_MS = 4000;

/** What an action done says, such as a reading saved: it shows a moment, read out as it comes, as it stays in the page empty. */
class LibraryNoticeElement extends Brick {
	#timeout = 0;

	connect( signal ) {
		signal.addEventListener( 'abort', () => clearTimeout( this.#timeout ) );

		return [
			store.select(
				( state ) => state.notice,
				( notice ) => {
					clearTimeout( this.#timeout );
					this.textContent = notice?.text ?? '';
					this.#timeout = setTimeout( () => {
						this.textContent = '';
					}, NOTICE_MS );
				}
			),
		];
	}
}
customElements.define( 'library-notice', LibraryNoticeElement );
