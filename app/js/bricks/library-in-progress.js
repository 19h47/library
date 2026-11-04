import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatBooks } from 'library-app/format';
import { fill, renderCurrent } from 'library-app/render';
import { openBooks, showBusy } from 'library-app/behaviors';

/**
 * The books being read, as their covers in a row that scrolls sideways, which open them, each with its button
 * Finished, which reads it today; how many there are, some out of sight; hidden when none is.
 */
class LibraryInProgressElement extends Brick {
	connect( signal ) {
		const $list = this.querySelector( 'ul' );

		openBooks( $list, signal );

		$list.addEventListener(
			'click',
			async ( event ) => {
				const $finish = event.target.closest( '[data-action="finish"]' );

				if ( ! $finish ) {
					return;
				}

				$finish.disabled = true;
				showBusy( $finish, true );
				await store.finishReading( Number( $finish.closest( '[data-id]' ).dataset.id ) );
				$finish.disabled = false;
				showBusy( $finish, false );
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => state.inProgress.items,
				( ids ) => {
					const { books } = store.get();

					fill( this, { count: formatBooks( ids.length ) } );
					$list.replaceChildren( ...ids.map( ( id ) => renderCurrent( books[ id ] ) ) );
					this.hidden = ! ids.length;
				}
			),
		];
	}
}
customElements.define( 'library-in-progress', LibraryInProgressElement );
