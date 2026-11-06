import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';

/** @import { Notice } from 'library-app/store' */

// How long a notice shows, and a notice with an action, such as Undo, for it to be reached.
const NOTICE_MS = 4000;
const ACTION_MS = 8000;

/**
 * What an action done says, such as a reading saved, and the action that undoes it if any: it shows a moment, read out
 * as it comes, as its text stays in the page empty. It stays while the pointer or the focus is on it; its action taken,
 * the focus stays on it, which says what was undone.
 */
class LibraryNoticeElement extends Brick {
	#timeout = 0;

	connect( signal ) {
		const $text = this.querySelector( '[data-field="text"]' );
		/** @type {HTMLButtonElement} */
		const $action = this.querySelector( '.library-link' );
		/** @type {Notice|null} */
		let notice = null;
		// Whether the pointer or the focus is on it: a notice that comes then waits for them to leave.
		const held = { pointer: false, focus: false };
		const hide = () => {
			$text.textContent = '';
			$action.hidden = true;
		};
		const hideLater = () => {
			clearTimeout( this.#timeout );

			if ( ! held.pointer && ! held.focus ) {
				this.#timeout = setTimeout(
					hide,
					notice?.action ? ACTION_MS : NOTICE_MS
				);
			}
		};
		const hold = ( by, isHeld ) => () => {
			held[ by ] = isHeld;
			hideLater();
		};

		signal.addEventListener( 'abort', () => clearTimeout( this.#timeout ) );
		this.addEventListener( 'pointerenter', hold( 'pointer', true ), {
			signal,
		} );
		this.addEventListener( 'pointerleave', hold( 'pointer', false ), {
			signal,
		} );
		this.addEventListener( 'focusin', hold( 'focus', true ), { signal } );
		this.addEventListener( 'focusout', hold( 'focus', false ), { signal } );
		$action.addEventListener(
			'click',
			() => {
				// Hidden with the focus on it, the action would leave the focus nowhere.
				this.focus();
				hide();
				notice.action.run();
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => state.notice,
				( value ) => {
					notice = value;
					$text.textContent = notice?.text ?? '';
					$action.textContent = notice?.action?.label ?? '';
					$action.hidden = ! notice?.action;
					hideLater();
				}
			),
		];
	}
}
customElements.define( 'library-notice', LibraryNoticeElement );
