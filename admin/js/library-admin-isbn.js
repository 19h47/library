/**
 * Fetch book metadata by ISBN (BnF) and fill form fields.
 *
 * Uses the WordPress REST API (fetch + X-WP-Nonce) like the core.
 *
 * @package    Library
 * @subpackage Library/admin
 */
( function ( $ ) {
	'use strict';

	var config = window.libraryAdminIsbn || {};

	function normalizeIsbn( value ) {
		return String( value ).replace( /[\s\-]/g, '' );
	}

	function setInput( $el, value ) {
		if ( ! $el.length ) {
			return;
		}
		if ( value !== undefined && value !== '' ) {
			if ( $el.data( 'library-placeholder' ) === undefined ) {
				$el.data( 'library-placeholder', $el.attr( 'placeholder' ) || '' );
			}
			$el.val( value );
			$el.attr( 'placeholder', '' );
			$el[ 0 ].dispatchEvent( new Event( 'input', { bubbles: true } ) );
		}
	}

	function hideTitlePrompt() {
		var el = document.getElementById( 'title-prompt-text' ) || document.querySelector( 'label[for="title"]' );
		if ( el ) {
			el.classList.add( 'screen-reader-text' );
		}
	}

	function showTitlePrompt() {
		var el = document.getElementById( 'title-prompt-text' ) || document.querySelector( 'label[for="title"]' );
		if ( el ) {
			el.classList.remove( 'screen-reader-text' );
		}
	}

	function onFetchClick() {
		var $btn   = $( '#library-fetch-isbn' );
		var $status = $( '#library-fetch-isbn-status' );
		var isbn   = normalizeIsbn( $( '#library-isbn' ).val() );

		if ( ! isbn ) {
			$status.text( config.enterIsbn ).addClass( 'library-fetch-error' );
			return;
		}

		$btn.prop( 'disabled', true );
		$status.removeClass( 'library-fetch-error library-fetch-ok' ).text( config.loading );

		var url = config.restUrl + 'library/v1/isbn/' + encodeURIComponent( isbn );

		window.fetch( url, {
			method:  'GET',
			headers: {
				'X-WP-Nonce': config.nonce,
				'Content-Type': 'application/json',
			},
			credentials: 'same-origin',
		} )
			.then( function ( response ) {
				return response.json().then( function ( data ) {
					return { ok: response.ok, status: response.status, data: data };
				} );
			} )
			.then( function ( result ) {
				if ( result.ok ) {
					$status.text( config.success ).addClass( 'library-fetch-ok' );
					setInput( $( '#title' ), result.data.title );
					if ( result.data.title ) {
						setTimeout( hideTitlePrompt, 0 );
					}
					setInput( $( '#library-authors' ), result.data.authors );
					setInput( $( '#library-publishers' ), result.data.publishers );
					setInput( $( '#library-date-published' ), result.data.date_published );
					setInput( $( '#library-number-of-pages' ), result.data.number_of_pages != null ? String( result.data.number_of_pages ) : '' );
				} else {
					var msg = result.data && result.data.message ? result.data.message : config.error;
					if ( result.status === 404 ) {
						msg = config.notFound;
					}
					$status.text( msg ).addClass( 'library-fetch-error' );
				}
			} )
			.catch( function () {
				$status.text( config.error ).addClass( 'library-fetch-error' );
			} )
			.finally( function () {
				$btn.prop( 'disabled', false );
			} );
	}

	$( function () {
		$( '#library-fetch-isbn' ).on( 'click', onFetchClick );

		$( '#title, #library-authors, #library-publishers, #library-date-published, #library-number-of-pages' )
			.on( 'input.library-placeholder', function () {
				var $el = $( this );
				if ( $el.val() === '' ) {
					if ( $el.data( 'library-placeholder' ) !== undefined ) {
						$el.attr( 'placeholder', $el.data( 'library-placeholder' ) );
					}
					if ( $el.attr( 'id' ) === 'title' ) {
						showTitlePrompt();
					}
				}
			} );
	} );
} )( jQuery );
