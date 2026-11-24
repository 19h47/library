<?php
/**
 * A reading of a book, on its row of the reading form: its period, from its start to its end, or that its dates are
 * unknown, then the rating it ended with, or that it was abandoned, under its dates on a phone. Its root is its custom
 * element, reading-row, whose value is the reading as an interval of days and its rating, such as
 * 2025-03-10/2025-03-21#8, or #abandoned, a side empty when unknown, "/" for a reading whose dates are unknown, which says
 * so rather than showing its dates, empty, which it sends.
 *
 * @link       https://github.com/19h47/library
 * @since      10.6.1
 *
 * @package    Library
 * @subpackage Library/app/components
 */

use Library\Markup;
use Library\Meta;

// The rating the reading ended with, or abandoned.
$rating = Meta::get_ending_control(
	null,
	array(
		'name'       => 'reading_rating',
		'class'      => 'library-input',
		'aria-label' => Meta::get_label( 'library_rating' ),
	)
);
?>
<reading-row>
	<?php Markup::render_component( 'reading-period' ); ?>
	<span class="library-reading-row__unknown" data-part="unknown" hidden><?php esc_html_e( 'Unknown date', 'library' ); ?></span>
	<?php echo $rating; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes it. ?>
</reading-row>
