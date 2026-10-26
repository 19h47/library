<?php
/**
 * A row of the book information metabox: the label, the input and the description of a book meta, as registered.
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

$field_id    = 'library-' . str_replace( '_', '-', $key );
$description = Library_Meta::description( $key );
$widths      = array(
	'subtitle' => 'large-text',
	'pages'    => 'small-text',
	'rating'   => 'small-text',
);
?>

<tr>
	<th scope="row">
		<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( Library_Meta::label( $key ) ); ?></label>
	</th>
	<td>
		<?php
		$input = Library_Meta::input(
			$key,
			array(
				'id'               => $field_id,
				'class'            => $widths[ $key ] ?? 'regular-text',
				'value'            => (string) $values[ $key ],
				'aria-describedby' => '' !== $description ? "$field_id-description" : null,
			)
		);

		echo $input; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
		?>
		<?php if ( '' !== $description ) : ?>
			<p class="description" id="<?php echo esc_attr( "$field_id-description" ); ?>"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
	</td>
</tr>
