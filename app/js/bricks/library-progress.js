import { __, sprintf, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatNumber } from 'library-app/format';

/** The percentage of books read. */
class LibraryProgressElement extends Brick {
	connect() {
		return [
			store.select(
				( state ) => state.stats?.percent,
				( percent ) => {
					/* translators: %s: reading percentage. */
					this.textContent = undefined === percent ? '' : sprintf( __( '%s%% read', 'library' ), formatNumber( percent ) );
				}
			),
		];
	}
}
customElements.define( 'library-progress', LibraryProgressElement );
