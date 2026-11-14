/**
 * The components of the reading app by their tags, for querySelector() and closest() to give their API: the properties
 * a place sets on them, such as the cover of an image-field or the value of a reading-period.
 */

import type { BarcodeScanElement } from '../app/js/components/barcode-scan.js';
import type { CheckboxesFieldElement } from '../app/js/components/checkboxes-field.js';
import type { ImageFieldElement } from '../app/js/components/image-field.js';
import type { ReadingPeriodElement } from '../app/js/components/reading-period.js';

declare global {
	interface HTMLElementTagNameMap {
		'barcode-scan': BarcodeScanElement;
		'checkboxes-field': CheckboxesFieldElement;
		'image-field': ImageFieldElement;
		'reading-period': ReadingPeriodElement;
	}
}

export {};
