<?php
/**
 * The readings of each month of a year: a bar each, as high as its share of the month read the most, its number above it
 * and the initial of its month under it, which assistive technologies read as the name of the month and its books. Its
 * root is its custom element, months-chart, whose `year` and `months` its place sets.
 *
 * @link       https://github.com/19h47/library
 * @since      10.6.2
 *
 * @package    Library
 * @subpackage Library/app/components
 */

?>
<months-chart>
	<ol class="library-months-chart" data-part="months"></ol>
	<template>
		<li>
			<span class="app-text-s app-text-muted app-font-ui app-uppercase" data-part="count" aria-hidden="true"></span>
			<span class="library-bar library-bar--vertical" aria-hidden="true"></span>
			<span class="app-text-s app-text-muted app-font-ui app-uppercase" data-part="initial" aria-hidden="true"></span>
			<span class="sr-only" data-part="said"></span>
		</li>
	</template>
</months-chart>
