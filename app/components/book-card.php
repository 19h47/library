<?php
/**
 * The card of a book, an .app-item: its cover, its credits, its title, a link to its page, its excerpt, then its state
 * and its tags, its form, its original language and its status. fillBook() fills its fields; the place that holds it,
 * such as the item of a list, sets where it goes.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{level?: int} $args
 */

$args    = wp_parse_args( $args, array( 'level' => 3 ) );
$heading = 'h' . (int) $args['level'];
?>
<article class="app-item library-item" data-part="card">
	<img class="library-cover" data-field="cover" alt="" loading="lazy" hidden>
	<div class="library-item__body">
		<p class="app-item__meta" data-field="credits"></p>
		<<?php echo esc_html( $heading ); ?> class="library-item__title">
			<a class="library-item__link" data-part="link" data-field="title"></a>
		</<?php echo esc_html( $heading ); ?>>
		<p class="library-item__excerpt" data-field="excerpt" hidden></p>
		<p class="app-item__meta">
			<span data-field="state"></span>
			<span class="library-tag" data-field="form" hidden></span>
			<span class="library-tag" data-field="original" hidden></span>
			<span class="library-tag library-tag--status" data-field="status" hidden></span>
		</p>
	</div>
</article>
