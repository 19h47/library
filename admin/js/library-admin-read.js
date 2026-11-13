/**
 * Read checkbox in the books list, then refresh the read date and the reading percentage.
 *
 * Uses wp.apiFetch, which handles the REST root and nonce, wp.i18n, and wp.a11y, which says the new percentage, or that
 * the reading could not be saved, the checkbox back as it was.
 * Checking a book without a read date reads it now and ends its reading in progress; unchecking it clears its read dates.
 */
( () => {
	'use strict';

	const { apiFetch } = window.wp;
	const { __ } = window.wp.i18n;
	const { speak } = window.wp.a11y;
	const percentSelector = '.js-library-read-percentage';
	/** @type {HTMLElement} */
	const readPercentageEl = document.querySelector( percentSelector );

	if ( ! readPercentageEl ) {
		return;
	}

	const checkboxes = document.querySelectorAll( '[data-part="read"]' );

	// The read date of the book and the percentage as the server renders them, from the list again: the date in the
	// format of the site, the readings counted. Quick edit reads the inline data, so it follows the dates kept too.
	const refresh = async ( id ) => {
		const response = await fetch( location.href, {
			credentials: 'same-origin',
		} );
		const list = new DOMParser().parseFromString(
			await response.text(),
			'text/html'
		);

		[
			`#post-${ id } .column-library_read_date`,
			`#inline_${ id } .library_read_date`,
		].forEach( ( selector ) => {
			const rendered = list.querySelector( selector );
			const shown = document.querySelector( selector );

			if ( rendered && shown ) {
				shown.textContent = rendered.textContent;
			}
		} );

		const label = list.querySelector( percentSelector )?.textContent ?? '—';

		readPercentageEl.textContent = label;
		speak( label );
	};

	const handleChange = async ( event ) => {
		const checkbox = event.target;
		const id = checkbox.dataset.postId;

		if ( ! id ) {
			return;
		}

		const percentText = readPercentageEl.textContent;

		checkbox.disabled = true;
		readPercentageEl.textContent = readPercentageEl.dataset.loadingText;

		// Through the route of the book form, as the reading app saves: marked as read without a read date, a book is read
		// now, its time with it, and its reading in progress ends.
		const meta = { library_read: checkbox.checked };

		try {
			await apiFetch( {
				path: `/library/v1/books/${ id }?_fields=id`,
				method: 'POST',
				data: { meta },
			} );
		} catch ( error ) {
			const message =
				error.message ??
				__( 'The reading could not be saved.', 'library' );

			checkbox.checked = ! checkbox.checked;
			checkbox.disabled = false;
			readPercentageEl.textContent = percentText;
			speak( message, 'assertive' );
			return;
		}

		// Saved: what follows only shows it.
		try {
			await refresh( id );
		} catch {
			readPercentageEl.textContent = '—';
		} finally {
			checkbox.disabled = false;
		}
	};

	checkboxes.forEach( ( checkbox ) =>
		checkbox.addEventListener( 'change', handleChange )
	);
} )();
