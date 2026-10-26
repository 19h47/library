/**
 * Read checkbox in the books list, then refresh the read date and the reading percentage.
 *
 * Uses wp.apiFetch, which handles the REST root and nonce, and libraryAdminRead (readPercentFormat, today).
 * Checking a book without a read date sets it to today; unchecking it clears its read dates.
 *
 * @package    Library
 * @subpackage Library/admin
 * @global     wp, libraryAdminRead
 */
( () => {
	'use strict';

	const { apiFetch }     = window.wp;
	const config           = window.libraryAdminRead;
	const checkboxes       = document.querySelectorAll( '.js-library-checkbox' );
	const readPercentageEl = document.querySelector( '.js-library-read-percentage' );

	if ( ! readPercentageEl ) {
		return;
	}

	const updatePercent = ( percent ) => {
		readPercentageEl.textContent = config.readPercentFormat.replace( '%s', percent.toLocaleString() );
	};

	// Quick edit reads the inline data, so it must follow the date the server kept.
	// The checkbox adds or clears a single date: the cell of a book read several times stays as it is.
	const updateReadDates = ( id, readDates ) => {
		const cell       = document.querySelector( `#post-${ id } .column-read_date` );
		const inlineDate = document.querySelector( `#inline_${ id } .library_read_date` );

		if ( ! cell || ! inlineDate || readDates.length > 1 ) {
			return;
		}

		const [ readDate = '' ] = readDates;
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

		const inlineDate = document.querySelector( `#inline_${ id } .library_read_date` );
		const meta       = { read: checkbox.checked };

		if ( checkbox.checked && ! inlineDate?.textContent ) {
			meta.read_date = [ config.today ];
		}

		try {
			const book = await apiFetch( {
				path: `/wp/v2/books/${ id }`,
				method: 'POST',
				data: { meta },
			} );

			// A date stored empty comes back as null.
			updateReadDates( id, book.meta.read_date.filter( Boolean ) );

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
