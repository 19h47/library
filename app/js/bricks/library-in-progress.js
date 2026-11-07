import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatBooks } from 'library-app/format';
import { fill, renderCurrent } from 'library-app/render';
import { followBooks, renderKeyed, showBusy } from 'library-app/behaviors';

/**
 * The books being read, as their covers in a row that scrolls sideways, which open them, each with its button
 * Finished, which reads it today; how many there are, some out of sight; hidden when none is. A cover stays while its
 * book is being read, its focus too: a book finished leaves its focus to the next one.
 */
class LibraryInProgressElement extends Brick {
	connect( signal ) {
		const $list = this.querySelector( 'ul' );

		$list.addEventListener(
			'click',
			async ( event ) => {
				const $finish = /** @type {Element} */ ( event.target ).closest(
					'[data-action="finish"]'
				);

				// Busy rather than disabled while it saves: a button disabled loses its focus, which the next cover takes.
				if (
					! $finish ||
					'true' === $finish.getAttribute( 'aria-busy' )
				) {
					return;
				}

				showBusy( $finish, true );
				await store.finishReading(
					Number(
						/** @type {HTMLElement} */ (
							$finish.closest( '[data-id]' )
						).dataset.id
					)
				);
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
					renderKeyed( $list, ids, ( id ) =>
						renderCurrent( books[ id ] )
					);
					this.hidden = ! ids.length;
				}
			),
			// A book being read that changed, such as its cover, is drawn again in its place. A book finished leaves as the
			// books being read load again: its cover stays until then, its focus with it.
			followBooks( $list, '.library-current', ( $item, book ) => {
				if ( book?.in_progress ) {
					$item.replaceWith( renderCurrent( book ) );
				}
			} ),
		];
	}
}
customElements.define( 'library-in-progress', LibraryInProgressElement );
