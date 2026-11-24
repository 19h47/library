<?php
/**
 * The link to the recap of a year: as the home shows it in January, an .app-item, its year large, then what it opens;
 * `pill`, as the diary shows it before the latest month of a year gone by, a pill that names the recap. fillRecapLink()
 * fills its year and its link.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{pill?: bool} $args
 */

$args = wp_parse_args( $args, array( 'pill' => false ) );
?>
<?php if ( $args['pill'] ) : ?>
	<a class="app-pill library-recap-link library-recap-link--pill" href="">
		<span data-field="recap"></span>
	</a>
<?php else : ?>
	<a class="app-item library-recap-link" href="">
		<span class="library-recap-link__year" data-field="year"></span>
		<span><?php esc_html_e( 'The recap of the year', 'library' ); ?></span>
	</a>
<?php endif; ?>
