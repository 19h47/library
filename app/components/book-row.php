<?php
/**
 * The row of a book, an .app-item: its cover, small, its title, a link to its page, then its authors and its status, as
 * the reading idea on the home shows it. renderRow() fills its fields; the place that holds it, such as the item of a
 * list, sets where it goes.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{level?: int} $args
 */

$args    = wp_parse_args( $args, array( 'level' => 4 ) );
$heading = 'h' . (int) $args['level'];
?>
<article class="app-item library-row" data-part="card">
	<img class="library-cover" data-field="cover" alt="" loading="lazy" hidden>
	<div class="library-row__body">
		<<?php echo esc_html( $heading ); ?> class="library-row__title">
			<a class="library-item__link" data-part="link">
				<span class="library-row__name" data-field="title"></span>
			</a>
		</<?php echo esc_html( $heading ); ?>>
		<p class="app-item__meta">
			<span data-field="authors"></span>
			<span class="library-tag library-tag--status" data-field="status" hidden></span>
		</p>
	</div>
</article>
