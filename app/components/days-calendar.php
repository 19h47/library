<?php
/**
 * The days of a year, as a calendar: every day from January 1st, in weeks from Monday, each as dark as its level, a day
 * to come its outline, a memorable one pulsing. An image only: what it shows, the place says it in words. Its root is its
 * custom element, days-calendar, whose `value` its place sets, the year, the level of each day gone by and the memorable
 * ones.
 *
 * @link       https://github.com/19h47/library
 * @since      10.6.2
 *
 * @package    Library
 * @subpackage Library/app/components
 */

?>
<days-calendar aria-hidden="true">
	<ol class="library-days-calendar" data-part="days"></ol>
	<template>
		<li class="library-day"></li>
	</template>
</days-calendar>
