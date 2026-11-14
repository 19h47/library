<?php
/**
 * The period of a reading: its start, an arrow, then its end, two date fields, as a row of the readings of a book holds
 * it. Its root is its custom element, reading-period, which keeps the end after the start, both up to today.
 *
 * @link       https://github.com/19h47/library
 * @since      9.45.0
 *
 * @package    Library
 * @subpackage Library/app/components
 */

use Library\Markup;
?>
<reading-period>
	<input class="library-input" type="date" name="reading_start" aria-label="<?php esc_attr_e( 'Start of the reading', 'library' ); ?>">
	<?php Markup::render_icon( 'arrow_forward' ); ?>
	<input class="library-input" type="date" name="reading_end" aria-label="<?php esc_attr_e( 'Read date', 'library' ); ?>">
</reading-period>
