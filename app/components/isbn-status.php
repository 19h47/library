<?php
/**
 * What the ISBN typed for a book to add tells, under it: the book that has it already, which opens, and a book to buy
 * bought from there; else what the catalogue of the BnF answered; or that the photo of a barcode has none. A status,
 * read once one of them shows; library-edit fills it.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 */

?>
<p class="library-isbn-status" role="status">
	<span class="library-isbn-status__duplicate" data-row="duplicate" hidden>
		<?php esc_html_e( 'Already added:', 'library' ); ?>
		<a data-field="duplicate" href=""></a>
		<button class="app-pill" type="button" data-action="buy" hidden><?php esc_html_e( 'I bought it', 'library' ); ?></button>
	</span>
	<span data-field="catalog" hidden></span>
	<span data-field="scan" hidden></span>
</p>
