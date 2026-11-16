<?php
/**
 * What the catalogue tells of a book to add, under its ISBN: the book of the catalogue that has the ISBN typed, or else the
 * title typed, which opens, added already, a book to buy bought from there, or one on none of the shelves of the reader
 * put in their library; else what the catalogue of the BnF answered for the ISBN. A status, read once one of them shows;
 * library-edit fills it.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 */

?>
<p class="library-catalogue-status" role="status">
	<span class="library-catalogue-status__duplicate" data-row="duplicate" hidden>
		<span data-field="found" data-added="<?php esc_attr_e( 'Already added:', 'library' ); ?>" data-catalogue="<?php esc_attr_e( 'This book is in the catalogue:', 'library' ); ?>"></span>
		<a data-field="duplicate" href=""></a>
		<button class="app-pill" type="button" data-action="keep" data-to-buy="<?php esc_attr_e( 'I bought it', 'library' ); ?>" data-catalogue="<?php esc_attr_e( 'Put it in my library', 'library' ); ?>" hidden><?php esc_html_e( 'I bought it', 'library' ); ?></button>
	</span>
	<span data-field="catalog" hidden></span>
</p>
