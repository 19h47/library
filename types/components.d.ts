/**
 * The components of the reading app by their tags, for querySelector() and closest() to give their API: the properties
 * a place sets on them, such as the cover of an image-field or the value of a reading-period.
 */

import type { BarcodeScanElement } from '../app/js/components/barcode-scan.js';
import type { CatalogueStatusElement } from '../app/js/components/catalogue-status.js';
import type { CheckboxesFieldElement } from '../app/js/components/checkboxes-field.js';
import type { FieldsDisclosureElement } from '../app/js/components/fields-disclosure.js';
import type { FiltersSheetElement } from '../app/js/components/filters-sheet.js';
import type { ImageFieldElement } from '../app/js/components/image-field.js';
import type { InputGroupsElement } from '../app/js/components/input-groups.js';
import type { InputsListElement } from '../app/js/components/inputs-list.js';
import type { OfflineStatusElement } from '../app/js/components/offline-status.js';
import type { PartialDateElement } from '../app/js/components/partial-date.js';
import type { ReadingPeriodElement } from '../app/js/components/reading-period.js';
import type { ReadingRowElement } from '../app/js/components/reading-row.js';
import type { SearchFormElement } from '../app/js/components/search-form.js';

declare global {
	interface HTMLElementTagNameMap {
		'barcode-scan': BarcodeScanElement;
		'catalogue-status': CatalogueStatusElement;
		'checkboxes-field': CheckboxesFieldElement;
		'fields-disclosure': FieldsDisclosureElement;
		'filters-sheet': FiltersSheetElement;
		'image-field': ImageFieldElement;
		'input-groups': InputGroupsElement;
		'inputs-list': InputsListElement;
		'offline-status': OfflineStatusElement;
		'partial-date': PartialDateElement;
		'reading-period': ReadingPeriodElement;
		'reading-row': ReadingRowElement;
		'search-form': SearchFormElement;
	}
}

export {};
