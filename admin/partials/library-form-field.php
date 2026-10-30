<?php
/**
 * A row of the book information metabox: the label, the control and the description of a book meta, as registered.
 * A boolean is a checkbox, sent as 0 when unchecked.
 *
 * @link       https://github.com/19h47/library
 * @since      6.0.0
 *
 * @package    Library
 * @subpackage Library/admin/partials
 *
 * @var string $key    Meta key.
 * @var array  $values Values of the book, by meta key.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field_id    = str_replace( '_', '-', $key );
$description = Library_Meta::get_description( $key );
$is_boolean  = 'boolean' === get_registered_meta_keys( 'post', 'library_book' )[ $key ]['type'];
// The select of a rating keeps the width of its choices.
$widths = array(
	'library_subtitle' => 'large-text',
	'library_pages'    => 'small-text',
	'library_rating'   => null,
);
?>

<tr>
	<th scope="row">
		<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( Library_Meta::get_label( $key ) ); ?></label>
	</th>
	<td>
		<?php if ( $is_boolean ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="0">
		<?php endif; ?>
		<?php
		$control = Library_Meta::get_control(
			$key,
			array(
				'id'               => $field_id,
				'class'            => $is_boolean ? null : ( array_key_exists( $key, $widths ) ? $widths[ $key ] : 'regular-text' ),
				'value'            => $is_boolean ? '1' : (string) $values[ $key ],
				'checked'          => $is_boolean ? (bool) $values[ $key ] : null,
				'aria-describedby' => '' !== $description ? "$field_id-description" : null,
			)
		);

		echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
		?>
		<?php if ( '' !== $description ) : ?>
			<p class="description" id="<?php echo esc_attr( "$field_id-description" ); ?>"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
	</td>
</tr>
