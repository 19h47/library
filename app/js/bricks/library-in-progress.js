import { __ } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { renderBook } from 'library-app/render';
import { openBooks, showBusy } from 'library-app/behaviors';

/**
 * The books being read, as cards that open them, each with its button Finished, which reads it today; hidden when none
 * is. The section says they are in progress: their cards do not.
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

					$list.replaceChildren( ...ids.map( ( id ) => this.#renderBook( books[ id ] ) ) );
					this.hidden = ! ids.length;
				}
			),
		];
	}

	/** The card of a book being read, its button Finished described by its title. */
	#renderBook( book ) {
		const $item = renderBook( book, '' );
		const $title = $item.querySelector( '.library-item__button' );
		const $finish = document.createElement( 'button' );

		$title.id = `library-in-progress-${ book.id }`;
		$finish.className = 'app-pill library-item__action';
		$finish.type = 'button';
		$finish.dataset.action = 'finish';
		$finish.textContent = __( 'Finished', 'library' );
		$finish.setAttribute( 'aria-describedby', $title.id );
		$item.querySelector( '.library-item__body' ).append( $finish );

		return $item;
	}
}
customElements.define( 'library-in-progress', LibraryInProgressElement );
