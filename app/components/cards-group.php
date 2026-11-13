<?php
/**
 * A group of cards, such as the authors of a series: its first cards, then the others folded in a .library-disclosure,
 * under a pill that counts them. fillCardsGroup() fills it with the cards it is given; the place that holds it, such
 * as a section and its title, is the page's. Its cards sit side by side, as many as fit, each at least
 * --library-cards-group-min wide, a third of the page by default, which its place sets for wider cards.
 *
 * @link       https://github.com/19h47/library
 * @since      9.43.0
 *
 * @package    Library
 * @subpackage Library/app/components
 */

?>
<div class="library-cards-group">
	<ul class="app-list library-cards-group__list" data-list="shown"></ul>
	<details class="library-disclosure library-cards-group__more" data-row="more" hidden>
		<summary class="app-pill" data-field="more"></summary>
		<ul class="app-list library-cards-group__list" data-list="more"></ul>
	</details>
</div>
