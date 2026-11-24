<?php
/**
 * What the catalogue tells of a book to add, under its ISBN: the book of the catalogue that has the ISBN typed, or else the
 * title typed, which opens, added already, a book to buy bought from there, or one on none of the shelves of the reader
 * put in their library; else what the catalogue of the BnF answered for the ISBN. A status, read once one of them shows.
 * Its root is its custom element, catalogue-status, which says them as its place sets them.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 */

?>
<catalogue-status role="status">
	<span class="library-catalogue-status__duplicate" data-row="duplicate" hidden>
		<span data-field="found"></span>
		<a data-field="duplicate" href=""></a>
		<button class="app-pill" type="button" data-action="keep" hidden></button>
	</span>
	<span data-field="catalog" hidden></span>
</catalogue-status>
