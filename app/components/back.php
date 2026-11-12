<?php
/**
 * The way back, above the page of a book, of a person, of a series or of a collection and the recap: a pill with its
 * arrow, which goes back as the history does.
 *
 * @link       https://github.com/19h47/library
 * @since      9.37.0
 *
 * @package    Library
 * @subpackage Library/app/components
 */

use Library\Markup;
?>
<library-back class="library-back">
	<button class="app-pill" type="button"><?php Markup::render_icon( 'arrow_back' ); ?> <?php esc_html_e( 'Back', 'library' ); ?></button>
</library-back>
