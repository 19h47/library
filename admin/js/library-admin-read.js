/**
 * Read checkbox in the books list, then refresh the reading percentage.
 *
 * Uses libraryAdminRead (restUrl, nonce) and fetch() with X-WP-Nonce.
 *
 * @package    Library
 * @subpackage Library/admin
 * @global     libraryAdminRead
 */
( () => {
	'use strict';

	const config           = window.libraryAdminRead ?? {};
	const checkboxes       = document.querySelectorAll( '.js-library-checkbox' );
	const readPercentageEl = document.querySelector( '.js-library-read-percentage' );

	if ( ! readPercentageEl ) {
		return;
	}

	const request = async ( path, body ) => {
		const options = {
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': config.nonce,
				'Content-Type': 'application/json',
			},
		};

		if ( body ) {
			options.method = 'POST';
			options.body   = JSON.stringify( body );
		}

		const response = await window.fetch( config.restUrl + path, options );

		if ( ! response.ok ) {
			throw new Error( response.statusText );
		}

		return response.json();
	};

	const updateUI = ( percent ) => {
		const format = config.readPercentFormat ?? '%s% read';

		readPercentageEl.textContent = format.replace( '%s', percent.toLocaleString() );
	};

	const handleChange = async ( event ) => {
		const checkbox = event.target;
		const id       = checkbox.dataset.postId;

		if ( ! id ) {
			return;
		}

		checkbox.disabled = true;
		readPercentageEl.textContent = readPercentageEl.dataset.loadingText ?? '…';

		try {
			await request( `wp/v2/books/${ id }`, { read: checkbox.checked } );

			const stats = await request( 'library/v1/books/reading-stats' );

			updateUI( stats.percent );
		} catch {
			readPercentageEl.textContent = '—';
		} finally {
			checkbox.disabled = false;
		}
	};

	checkboxes.forEach( ( checkbox ) => checkbox.addEventListener( 'change', handleChange ) );
} )();
