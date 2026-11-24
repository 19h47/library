<?php
/**
 * A progress bar, how far a thing is: its bar, then its count, "9 / 14", which the bar itself reads, "9 of 14". Its root
 * is its custom element, progress-bar: its place sets its `value`, its `max`, and `labelledBy`, the ID of what names it,
 * such as the name of an achievement close to be earned.
 *
 * @link       https://github.com/19h47/library
 * @since      10.6.2
 *
 * @package    Library
 * @subpackage Library/app/components
 */

?>
<progress-bar>
	<progress></progress>
	<span class="app-text-s app-text-muted app-font-ui app-uppercase" data-part="count" aria-hidden="true"></span>
</progress-bar>
