<?php
/**
 * A reading ended in the book information metabox: its start and its end, either empty when it is unknown, and the button
 * that removes it.
 *
 * @link       https://github.com/19h47/library
 * @since      8.6.0
 *
 * @package    Library
 * @subpackage Library/admin/partials
 *
 * @var string $reading Reading, as an interval: its start and its end, Y-m-d, with their times or not, or empty. The fields
 *                      give their days, which keep their times once saved.
 */

use Library\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

[ $reading_start, $reading_end ] = Meta::split_reading( $reading );
?>
<li>
	<input type="date" name="library_reading_start[]" max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( substr( $reading_start, 0, 10 ) ); ?>" aria-label="<?php esc_attr_e( 'Start of the reading', 'library' ); ?>">
	<span aria-hidden="true">→</span>
	<input type="date" name="library_reading_end[]" max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( substr( $reading_end, 0, 10 ) ); ?>" aria-label="<?php esc_attr_e( 'Read date', 'library' ); ?>">
	<button class="button-link button-link-delete" type="button" data-action="remove"><?php esc_html_e( 'Remove this reading', 'library' ); ?></button>
</li>
