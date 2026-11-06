import { __, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatNumber } from 'library-app/format';

/** The percentage of books read. */
class LibraryProgressElement extends Brick {
	connect() {
		return [
			store.select(
				( state ) => state.stats?.percent,
				( percent ) => {
					this.textContent =
						undefined === percent
							? ''
							: sprintf(
									/* translators: %s: reading percentage. */
									__( '%s%% read', 'library' ),
									formatNumber( percent )
							  );
				}
			),
		];
	}
}
customElements.define( 'library-progress', LibraryProgressElement );
