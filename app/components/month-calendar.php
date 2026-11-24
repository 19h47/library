<?php
/**
 * The days of a month, as a calendar: in weeks from Monday, the first one in the column of its weekday, each a
 * .library-day as dark as its level, a day to come its outline, today marked. `covers`, larger, as the diary shows a month:
 * each day its number, a button that chooses it, and the cover of the book whose reading ended that day, a link to it, the
 * others counted. Its root is its custom element, month-calendar, whose `value` its place sets: the year, the month, and
 * each day of it; a day chosen sends `dayselect`.
 *
 * @link       https://github.com/19h47/library
 * @since      10.7.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{covers?: bool} $args
 */

$args = wp_parse_args( $args, array( 'covers' => false ) );
?>
<month-calendar<?php echo $args['covers'] ? '' : ' aria-hidden="true"'; ?>>
	<ol class="<?php echo esc_attr( $args['covers'] ? 'library-month-calendar library-month-calendar--covers' : 'library-month-calendar' ); ?>" data-part="days"></ol>
	<template>
		<?php if ( $args['covers'] ) : ?>
			<li class="library-day library-month-calendar__day">
				<button class="app-text-s app-font-ui library-month-calendar__number" type="button" data-part="number"></button>
				<a class="library-month-calendar__book" data-part="book" href="" hidden>
					<img class="library-month-calendar__cover" data-field="cover" alt="" loading="lazy" hidden>
					<span class="library-month-calendar__title" data-part="title"></span>
				</a>
				<span class="app-text-s app-font-ui library-month-calendar__more" data-part="more" hidden></span>
			</li>
		<?php else : ?>
			<li class="library-day"></li>
		<?php endif; ?>
	</template>
</month-calendar>
