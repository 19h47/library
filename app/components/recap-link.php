<?php
/**
 * The link to the recap of the year gone by, as the home shows it in January: an .app-item, its year large, then what it
 * opens. library-recap-link fills its year and its link.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 */

?>
<a class="app-item library-recap-link" href="">
	<span class="library-recap-link__year" data-field="year"></span>
	<span><?php esc_html_e( 'The recap of the year', 'library' ); ?></span>
</a>
