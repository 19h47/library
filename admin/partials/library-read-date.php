<?php
/**
 * A reading in the book information metabox: its date, empty when it is unknown, and the button that removes it.
 *
 * @link       https://github.com/19h47/library
 * @since      8.6.0
 *
 * @package    Library
 * @subpackage Library/admin/partials
 *
 * @var string $read_date Read date, Y-m-d, or empty.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<li>
	<?php
	$control = Library_Meta::get_control(
		'library_read_date',
		array(
			'name'       => 'library_read_date[]',
			'max'        => current_time( 'Y-m-d' ),
			'value'      => $read_date,
			'aria-label' => __( 'Read date', 'library' ),
		)
	);

	echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
	?>
	<button class="button-link button-link-delete" type="button" data-action="remove"><?php esc_html_e( 'Remove this reading', 'library' ); ?></button>
</li>
