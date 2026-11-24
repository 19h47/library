<?php
/**
 * The shares of a chart, such as the forms read in a year: a line each, the most read first, its name, its books, then a
 * bar as long as its share of the first one, which only shows what its books say. `ranked`, such as the people read the
 * most: an ordered list, a name on one line, cut short when too long. Its root is its custom element, shares-chart, whose
 * `shares` its place sets.
 *
 * @link       https://github.com/19h47/library
 * @since      10.6.2
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{ranked?: bool} $args
 */

$args = wp_parse_args( $args, array( 'ranked' => false ) );
$list = $args['ranked'] ? 'ol' : 'ul';
?>
<shares-chart>
	<<?php echo esc_html( $list ); ?> class="<?php echo esc_attr( $args['ranked'] ? 'library-shares-chart library-shares-chart--ranked' : 'library-shares-chart' ); ?>" data-part="lines"></<?php echo esc_html( $list ); ?>>
	<template>
		<li class="library-shares-chart__line">
			<span class="library-shares-chart__name" data-part="name"></span>
			<span class="app-text-s app-text-muted app-font-ui app-uppercase" data-part="count"></span>
			<span class="library-bar" aria-hidden="true"></span>
		</li>
	</template>
</shares-chart>
