<?php
/**
 * A reading ended in the book information metabox: its start and its end, either empty when it is unknown, the rating it
 * ended with, and the button that removes it.
 *
 * @link       https://github.com/19h47/library
 * @since      8.6.0
 *
 * @package    Library
 * @subpackage Library/admin/partials
 *
 * @var Reading $reading Reading: its start and its end, Y-m-d, with their times or not, or empty, and its rating. The
 *                       fields give their days, which keep their times once saved.
 */

use Library\Meta;
use Library\Reading;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<li>
	<input type="date" name="library_reading_start[]" max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( substr( $reading->start, 0, 10 ) ); ?>" aria-label="<?php esc_attr_e( 'Start of the reading', 'library' ); ?>">
	<span aria-hidden="true">→</span>
	<input type="date" name="library_reading_end[]" max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( substr( $reading->end, 0, 10 ) ); ?>" aria-label="<?php esc_attr_e( 'Read date', 'library' ); ?>">
	<?php
	$rating = Meta::get_control(
		'library_rating',
		array(
			'name'       => 'library_reading_rating[]',
			'value'      => null !== $reading->rating ? (string) $reading->rating : null,
			'aria-label' => Meta::get_label( 'library_rating' ),
		)
	);

	echo $rating; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes it.
	?>
	<button class="button-link button-link-delete" type="button" data-action="remove"><?php esc_html_e( 'Remove this reading', 'library' ); ?></button>
</li>
