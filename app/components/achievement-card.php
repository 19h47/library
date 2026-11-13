<?php
/**
 * The card of an achievement, an .app-item: earned, its title, its subject and when it was earned; close, its name, what
 * it asks, and how far it is, a progress bar and its count, read by the bar itself. renderAchievement() and
 * renderCloseAchievement() fill it; the place that holds it, such as the item of a list, sets where it goes.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{close?: bool} $args
 */

$args = wp_parse_args( $args, array( 'close' => false ) );
?>
<article class="app-item library-achievement-card" data-part="card">
	<?php if ( $args['close'] ) : ?>
		<p data-field="name"></p>
		<p class="app-item__meta" data-field="meta"></p>
		<p class="library-achievement-card__progress">
			<progress></progress>
			<span class="app-item__meta" data-field="count" aria-hidden="true"></span>
		</p>
	<?php else : ?>
		<p data-field="title"></p>
		<p data-field="subject"></p>
		<p class="app-item__meta" data-field="meta"></p>
	<?php endif; ?>
</article>
