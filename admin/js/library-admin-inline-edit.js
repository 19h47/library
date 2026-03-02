/**
 * Quick edit: prefill ISBN and book editions in the inline edit row.
 *
 * Extends inlineEditPost.edit (WordPress core) then fills custom fields.
 * Bulk edit uses admin-ajax.php with fetch() (same pattern as REST in admin).
 *
 * @package    Library
 * @subpackage Library/admin
 * @global     inlineEditPost
 * @global     ajaxurl
 * @global     pagenow
 * @global     typenow
 * @global     adminpage
 */
( function ( $ ) {
	'use strict';

	if ( 'book' !== typenow || 'edit-php' !== adminpage || 'edit-book' !== pagenow ) {
		return;
	}

	// Wrap WordPress quick edit like core does for custom columns.
	var originalEdit = inlineEditPost.edit;

	inlineEditPost.edit = function ( id ) {
		originalEdit.apply( this, arguments );

		var postId = 0;
		if ( typeof id === 'object' ) {
			postId = parseInt( this.getId( id ), 10 );
		}
		if ( postId <= 0 ) {
			return;
		}

		var editRow = document.getElementById( 'edit-' + postId );
		if ( ! editRow ) {
			return;
		}

		var isbnEl = document.getElementById( 'library-isbn-' + postId );
		var bookEditionsEl = document.getElementById( 'library-book_editions-' + postId );
		if ( isbnEl && bookEditionsEl ) {
			editRow.querySelector( 'input[name="isbn"]' ).value = isbnEl.innerHTML.trim();
			editRow.querySelector( 'input[name="book_editions"]' ).value = bookEditionsEl.value.trim();
		}
	};

	// Bulk edit: send via admin-ajax (fetch + FormData, no synchronous XHR).
	$( '#bulk_edit' ).on( 'click', function () {
		var $bulkRow = $( '#bulk-edit' );
		var postIds  = [];

		$bulkRow.find( '#bulk-titles' ).children().each( function () {
			postIds.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
		} );

		var formData = new FormData();
		formData.append( 'action', 'manage_wp_posts_using_bulk_quick_save_bulk_edit' );
		formData.append( 'isbn', $bulkRow.find( 'input[name="isbn"]' ).val() );
		postIds.forEach( function ( id ) {
			formData.append( 'post_ids[]', id );
		} );

		window.fetch( ajaxurl, {
			method:      'POST',
			body:        formData,
			credentials: 'same-origin',
		} );
	} );
} )( jQuery );
