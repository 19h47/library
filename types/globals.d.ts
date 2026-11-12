/**
 * What the pages of the plugin give their scripts: the packages of WordPress as wp, and the data of the reading app,
 * which get_config() of the app prints; the quick edit of WordPress; and what the browsers have that TypeScript does not
 * type yet: the Navigation API, which the router of the app goes through, moveBefore(), the focusVisible option of
 * focus() and the Barcode Detection API, which reads the ISBN of a book in the photo of its barcode.
 */

import type apiFetch from '@wordpress/api-fetch';
import type * as i18n from '@wordpress/i18n';
import type * as url from '@wordpress/url';
import type * as a11y from '@wordpress/a11y';

declare global {
	interface Window {
		wp: {
			apiFetch: typeof apiFetch;
			i18n: typeof i18n;
			url: typeof url;
			a11y: typeof a11y;
		};
		libraryApp: {
			statuses: string;
			bookFields: string;
			perPage: number;
			maxPerPage: number;
			forms: string;
			inLibrary: string;
			notKept: string;
			toBuy: string;
			roles: string[];
			editUrl: string;
			appUrl: string;
			serviceWorkerUrl: string;
			storageKey: string;
			icons: { light: string; dark: string };
		};
		libraryInlineEdit: {
			statusChoices: Record< string, string >;
		};
	}

	/** The quick edit of the books list, wp-admin/js/inline-edit-post.js. */
	var inlineEditPost: {
		/** Opens the quick edit of the post `id`, or of the row of an element; what it returns is not read. */
		edit: ( id: number | HTMLElement, ...args: unknown[] ) => void;
		/** The ID of the post of the row of an element. */
		getId: ( element: HTMLElement ) => string;
	};

	interface Element {
		/** Moves a child before another one, or last, keeping its state, such as its focus; not in every browser yet. */
		moveBefore?: ( node: Node, child: Node | null ) => void;
	}

	interface FocusOptions {
		/** Whether the element focused shows its outline, rather than as the browser guesses. */
		focusVisible?: boolean;
	}

	interface NavigationHistoryEntry {
		readonly key: string;
		readonly index: number;
		readonly sameDocument: boolean;
	}

	interface NavigationDestination {
		readonly url: string;
		// The key of the entry gone to in the history, empty for a new one.
		readonly key: string;
	}

	interface NavigationInterceptOptions {
		handler?: () => Promise< void >;
		focusReset?: 'after-transition' | 'manual';
		scroll?: 'after-transition' | 'manual';
	}

	interface NavigateEvent extends Event {
		readonly navigationType: 'push' | 'replace' | 'reload' | 'traverse';
		readonly destination: NavigationDestination;
		readonly canIntercept: boolean;
		readonly hashChange: boolean;
		readonly downloadRequest: string | null;
		readonly formData: FormData | null;
		readonly hasUAVisualTransition: boolean;
		readonly sourceElement: Element | null;
		intercept: ( options?: NavigationInterceptOptions ) => void;
	}

	interface NavigationResult {
		committed: Promise< NavigationHistoryEntry >;
		finished: Promise< NavigationHistoryEntry >;
	}

	interface Navigation extends EventTarget {
		readonly currentEntry: NavigationHistoryEntry;
		entries: () => NavigationHistoryEntry[];
		navigate: (
			url: string,
			options?: { history?: 'auto' | 'push' | 'replace' }
		) => NavigationResult;
		back: () => NavigationResult;
		addEventListener: ( (
			type: 'navigate',
			listener: ( event: NavigateEvent ) => void,
			options?: boolean | AddEventListenerOptions
		) => void ) &
			( (
				type: 'navigatesuccess',
				listener: ( event: Event ) => void,
				options?: boolean | AddEventListenerOptions
			) => void );
	}

	var navigation: Navigation;

	interface DetectedBarcode {
		readonly rawValue: string;
		readonly format: string;
	}

	/** Reads the barcodes of an image; not in every browser, Safari behind its Shape Detection API flag. */
	class BarcodeDetector {
		constructor( options?: { formats?: string[] } );
		static getSupportedFormats(): Promise< string[] >;
		detect( image: ImageBitmapSource ): Promise< DetectedBarcode[] >;
	}

	interface Window {
		BarcodeDetector?: typeof BarcodeDetector;
	}
}

export {};
