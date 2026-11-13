<?php
/**
 * The cover of a book: its image, or without one the frame that stands for it, its title in a frame of the shape of a
 * cover. It lays out as the one it shows, its place setting its size, --library-cover-inline-size or
 * --library-cover-block-size; a place that names the book already hides the frame, --library-cover-frame: none, such as
 * a card in a list. Large, it is the one of the page of a book, standing out of the page and kept inside it.
 * fillBookCover() fills it.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{large?: bool, attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;

$args = wp_parse_args(
	$args,
	array(
		'large'      => false,
		'attributes' => array(),
	)
);

Markup::render_tag(
	'<span>',
	$args['attributes'] + array(
		'class'     => 'library-book-cover' . ( $args['large'] ? ' library-book-cover--large' : '' ),
		'data-part' => 'cover',
	)
);
?>
	<img class="library-book-cover__image" data-field="cover" alt="" loading="lazy" hidden>
	<span class="library-book-cover__frame" data-row="cover_title" hidden>
		<span class="library-book-cover__title" data-field="cover_title"></span>
	</span>
</span>
