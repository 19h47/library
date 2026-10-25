import { sprintf, store, strings } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatNumber } from 'library-app/format';

/** The percentage of books read. */
class LibraryProgressElement extends Brick {
	connect() {
		return [
			store.select(
				( state ) => state.stats?.percent,
				( percent ) => {
					this.textContent = undefined === percent ? '' : sprintf( strings.readPercent, formatNumber( percent ) );
				}
			),
		];
	}
}
customElements.define( 'library-progress', LibraryProgressElement );
