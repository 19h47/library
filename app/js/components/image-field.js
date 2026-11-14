import { Component } from 'library-app/component';
import { fillCover } from 'library-app/render';

/**
 * @import { Cover } from 'library-app/api'
 */

/**
 * The field of a cover, the root of the component image-field: the photo picked shows in its preview at once; Remove
 * empties the field and its preview, and checks its checkbox, the focus going to the pill that picks a photo. The reset
 * of the form unchecks it, as a field of its own, and empties the preview. Its styles show Remove while its preview
 * shows an image.
 *
 * Its API: `cover`, the cover it has, such as the one of the book open, shown until a photo is picked or it is removed;
 * its fields, the photo picked and `<name>_removed`, checked once the cover is removed, which its form sends.
 */
export class ImageFieldElement extends Component {
	static properties = [ 'cover' ];

	/** @type {Cover|null} */
	#cover = null;

	/** @return {Cover|null} The cover it has. */
	get cover() {
		return this.#cover;
	}

	/** @param {Cover|null} cover The cover it has, none without one. */
	set cover( cover ) {
		this.#cover = cover;
		fillCover( this.#preview, cover );
	}

	/** @return {HTMLImageElement} The image of its preview. */
	get #preview() {
		return this.querySelector(
			'[data-part="preview"] [data-field="cover"]'
		);
	}

	connect( signal ) {
		/** @type {HTMLInputElement} */
		const $file = this.querySelector( 'input[type="file"]' );
		/** @type {HTMLInputElement} */
		const $removed = this.querySelector( 'input[type="checkbox"]' );
		// The address of the photo picked, shown in the preview until another replaces it.
		let url = '';
		const show = ( /** @type {File|null} */ photo ) => {
			URL.revokeObjectURL( url );
			url = photo ? URL.createObjectURL( photo ) : '';
			fillCover( this.#preview, photo ? { url } : null );
		};

		// A picker closed without a photo fires cancel instead, the photo picked before kept.
		$file.addEventListener( 'change', () => show( $file.files[ 0 ] ), {
			signal,
		} );
		this.querySelector( '[data-action="remove"]' ).addEventListener(
			'click',
			() => {
				$file.value = '';
				$removed.checked = true;
				show( null );
				// The button hides: the focus goes to the one that picks a photo.
				$file.focus();
			},
			{ signal }
		);
		$file.form?.addEventListener( 'reset', () => show( null ), { signal } );
		signal.addEventListener( 'abort', () => URL.revokeObjectURL( url ) );
	}
}
customElements.define( 'image-field', ImageFieldElement );
