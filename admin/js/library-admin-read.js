/**
 * Reading percentage (at a glance) and checkbox handling.
 *
 * Uses libraryAdminRead (restUrl, nonce) and fetch() with X-WP-Nonce like the ISBN script.
 *
 * @package    Library
 * @subpackage Library/admin
 * @global     pagenow
 * @global     typenow
 * @global     adminpage
 * @global     libraryAdminRead
 */
( function () {
	'use strict';

	var config = window.libraryAdminRead || {};

	jQuery( async function () {
		if ( 'book' !== typenow || 'edit-php' !== adminpage || 'edit-book' !== pagenow ) {
			return;
		}

		var checkboxes       = document.querySelectorAll( '.js-library-checkbox' );
		var readPercentageEl = document.querySelector( '.js-library-read-percentage' );

		if ( ! readPercentageEl ) {
			return;
		}

		var restUrl = config.restUrl || '';
		var nonce   = config.nonce || '';

		function fetchLibrary( path, options ) {
			options = options || {};
			var url = restUrl + 'library/v1/' + path;
			var opts = {
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': nonce,
					'Content-Type': 'application/json',
				},
				method: options.method || 'GET',
			};
			if ( options.body !== undefined ) {
				opts.body = options.body;
			}
			return window.fetch( url, opts );
		}

		function fetchWpApi( route, body, method ) {
			method = method || 'POST';
			body   = body || {};
			var opts = {
				method: method,
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': nonce,
					'Content-Type': 'application/json',
				},
			};
			if ( 'POST' === method && Object.keys( body ).length > 0 ) {
				opts.body = JSON.stringify( body );
			}
			return window.fetch( restUrl + 'wp/v2/' + route, opts );
		}

		function getReadingStats() {
			return fetchLibrary( 'books/reading-stats' ).then( function ( res ) {
				if ( ! res.ok ) {
					throw new Error( 'Failed to load reading stats' );
				}
				return res.json();
			} );
		}

		function saveReadingPercentage( value ) {
			fetchLibrary( 'settings/reading_percentage', {
				method: 'POST',
				body: JSON.stringify( { reading_percentage: value } ),
			} );
		}

		function updateUI( read, total ) {
			var pct    = total > 0 ? Math.trunc( ( read / total ) * 100 ) : 0;
			var format = config.readPercentFormat || '%s%% read';
			readPercentageEl.textContent = format.replace( '%s', String( pct ) );
			saveReadingPercentage( pct );
		}

		async function handleChange( evt ) {
			var target = evt.target;
			var id     = target.dataset.postId;
			if ( ! id ) {
				return;
			}

			target.disabled = true;
			readPercentageEl.textContent = readPercentageEl.getAttribute( 'data-loading-text' ) || '…';

			try {
				var res = await fetchWpApi( 'books/' + id, { read: target.checked }, 'POST' );
				if ( ! res.ok ) {
					throw new Error( 'Failed to update' );
				}
				var stats = await getReadingStats();
				updateUI( stats.read, stats.total );
			} catch ( err ) {
				readPercentageEl.textContent = '—';
			} finally {
				target.disabled = false;
			}
		}

		checkboxes.forEach( function ( cb ) {
			cb.addEventListener( 'change', handleChange );
		} );

		if ( readPercentageEl.classList.contains( 'js-library-read-percentage-init' ) ) {
			try {
				var stats = await getReadingStats();
				readPercentageEl.classList.remove( 'js-library-read-percentage-init' );
				updateUI( stats.read, stats.total );
			} catch ( err ) {
				readPercentageEl.textContent = '—';
			}
		}
	} );
} )();
