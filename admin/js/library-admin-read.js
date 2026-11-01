/**
 * Read checkbox in the books list, then refresh the read date and the reading percentage.
 *
 * Uses wp.apiFetch, which handles the REST root and nonce, and wp.i18n.
 * Checking a book without a read date reads it now and ends its reading in progress; unchecking it clears its read dates.
 *
 * @package    Library
 * @subpackage Library/admin
 * @global     wp
 */
( () => {
	'use strict';

	const { apiFetch }     = window.wp;
	const { __, sprintf }  = window.wp.i18n;
	const checkboxes       = document.querySelectorAll( '.js-library-checkbox' );
	const readPercentageEl = document.querySelector( '.js-library-read-percentage' );

	if ( ! readPercentageEl ) {
		return;
	}

	const updatePercent = ( percent ) => {
		/* translators: %s: reading percentage. */
		readPercentageEl.textContent = sprintf( __( '%s%% read', 'library' ), percent.toLocaleString() );
	};

	// Quick edit reads the inline data, so it must follow the date the server kept.
	// The checkbox adds or clears a single date: the cell of a book read several times stays as it is.
	const updateReadDates = ( id, readDates ) => {
		const cell       = document.querySelector( `#post-${ id } .column-library_read_date` );
		const inlineDate = document.querySelector( `#inline_${ id } .library_read_date` );

		if ( ! cell || ! inlineDate || readDates.length > 1 ) {
			return;
		}

		// Its day: a reading of today has its time too.
		const [ readDate = '' ] = readDates.map( ( date ) => date.slice( 0, 10 ) );
		const label             = readDate
			? new Date( `${ readDate }T00:00` ).toLocaleDateString( document.documentElement.lang, { dateStyle: 'long' } )
			: '—';

		cell.textContent       = label;
		inlineDate.textContent = readDate;
	};

	const handleChange = async ( event ) => {
		const checkbox = event.target;
		const id       = checkbox.dataset.postId;

		if ( ! id ) {
			return;
		}

		checkbox.disabled = true;
		readPercentageEl.textContent = readPercentageEl.dataset.loadingText;

		// Through the route of the book form, as the reading app saves: marked as read without a read date, a book is read
		// now, its time with it, and its reading in progress ends.
		const meta = { library_read: checkbox.checked };

		try {
			await apiFetch( { path: `/library/v1/books/${ id }`, method: 'POST', data: { meta } } );

			const book = await apiFetch( { path: `/wp/v2/books/${ id }?_fields=meta` } );

			updateReadDates( id, book.meta.library_read_date );

			const stats = await apiFetch( { path: '/library/v1/books/reading-stats' } );

			updatePercent( stats.percent );
		} catch {
			readPercentageEl.textContent = '—';
		} finally {
			checkbox.disabled = false;
		}
	};

	checkboxes.forEach( ( checkbox ) => checkbox.addEventListener( 'change', handleChange ) );
} )();
