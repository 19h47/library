<?php
/**
 * The days of a month, as a calendar: in weeks from Monday, the first one in the column of its weekday, each a
 * .library-day as dark as its level, a day to come its outline, today marked; a day that leads somewhere, a link to it for
 * a pointer, out of the order of the focus, as the calendar is an image the place says in words. `covers`, larger, as the
 * diary shows a month:
 * each day a link to it, its number and the cover of the book finished that day, or its title, the others
 * counted. Its root is its custom element, month-calendar, whose `value` its place sets: the year, the month, and each day
 * of it, with where it leads.
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
				<?php // The whole day, a link to it once gone by: its date, which its number shows, then its book. ?>
				<a class="library-month-calendar__link" data-part="link">
					<span class="sr-only" data-part="date"></span>
					<span class="app-text-s app-font-ui library-month-calendar__number" data-part="number" aria-hidden="true"></span>
					<span class="library-month-calendar__book" data-part="book" hidden>
						<img class="library-month-calendar__cover" data-field="cover" alt="" loading="lazy" hidden>
						<span class="library-month-calendar__title" data-part="title"></span>
					</span>
					<span class="app-text-s app-font-ui library-month-calendar__more" data-part="more" aria-hidden="true" hidden></span>
				</a>
			</li>
		<?php else : ?>
			<li class="library-day">
				<?php // The whole day, a link for a pointer where it leads: the place leads there in words. ?>
				<a class="library-month-calendar__link" data-part="link" tabindex="-1"></a>
			</li>
		<?php endif; ?>
	</template>
</month-calendar>
