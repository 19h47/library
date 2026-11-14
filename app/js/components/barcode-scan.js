import { __ } from 'library-app/app';
import { Component } from 'library-app/component';
import { fill } from 'library-app/dom';

/**
 * The scan of the barcode of a book, the root of the component barcode-scan: it shows where the browser reads EAN-13
 * barcodes; a photo without one says so, until the field is typed in, another photo is taken or the form is reset.
 *
 * Its API: `for`, the ID of the field the barcode read types, as if typed, with a change.
 */
export class BarcodeScanElement extends Component {
	connect( signal ) {
		/** @type {HTMLInputElement} */
		const $photo = this.querySelector( 'input[type="file"]' );
		const $field = /** @type {HTMLInputElement} */ (
			document.getElementById( this.getAttribute( 'for' ) )
		);
		const say = ( /** @type {string} */ message ) =>
			fill( this, { message } );

		window.BarcodeDetector?.getSupportedFormats().then( ( formats ) => {
			this.hidden = ! formats.includes( 'ean_13' );
		} );

		$photo.addEventListener(
			'change',
			async () => {
				const photo = $photo.files[ 0 ];

				if ( ! photo ) {
					return;
				}

				const detector = new BarcodeDetector( {
					formats: [ 'ean_13' ],
				} );
				const [ barcode ] = await createImageBitmap( photo )
					.then( ( bitmap ) => detector.detect( bitmap ) )
					.catch( () => [] );

				// The same photo can be taken again.
				$photo.value = '';

				if ( ! barcode ) {
					say(
						__(
							'No barcode found in the photo: take it again, closer.',
							'library'
						)
					);
					return;
				}

				say( '' );
				$field.value = barcode.rawValue;
				$field.dispatchEvent(
					new Event( 'change', { bubbles: true } )
				);
			},
			{ signal }
		);
		$field.addEventListener( 'input', () => say( '' ), { signal } );
		$photo.form?.addEventListener( 'reset', () => say( '' ), { signal } );
	}
}
customElements.define( 'barcode-scan', BarcodeScanElement );
