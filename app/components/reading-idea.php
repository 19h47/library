<?php
/**
 * The reading idea of the week: its title, its reason and its book as a card, then a pill for another idea. `compact`, as
 * on the home: its book as a row, the action for another idea a link at the end of its title. Its heading level `level`,
 * 2 by default. library-idea, which holds it in the app, fills it, its book drawn as its list says, `row` or `card`.
 *
 * @link       https://github.com/19h47/library
 * @since      10.6.2
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{compact?: bool, level?: int} $args
 */

use Library\Markup;

$args    = wp_parse_args(
	$args,
	array(
		'compact' => false,
		'level'   => 2,
	)
);
$heading = Markup::get_heading( $args['level'] );
?>
<div class="library-reading-idea">
	<?php if ( $args['compact'] ) : ?>
		<div class="library-title">
			<<?php echo esc_html( $heading ); ?> class="library-title__heading"><?php esc_html_e( 'Reading idea of the week', 'library' ); ?></<?php echo esc_html( $heading ); ?>>
			<span class="app-text-s app-text-muted app-font-ui app-uppercase library-title__action" data-row="next"><button class="library-link" type="button" data-action="next-idea"><?php esc_html_e( 'Another idea', 'library' ); ?></button></span>
		</div>
	<?php else : ?>
		<<?php echo esc_html( $heading ); ?> class="library-title"><?php esc_html_e( 'Reading idea of the week', 'library' ); ?></<?php echo esc_html( $heading ); ?>>
	<?php endif; ?>
	<p class="app-status" data-field="status" hidden></p>
	<p data-field="reason"></p>
	<ul class="app-list" data-list="<?php echo $args['compact'] ? 'row' : 'card'; ?>"></ul>
	<?php if ( ! $args['compact'] ) : ?>
		<p data-row="next">
			<button class="app-pill" type="button" data-action="next-idea"><?php esc_html_e( 'Another idea', 'library' ); ?></button>
		</p>
	<?php endif; ?>
</div>
