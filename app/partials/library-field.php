<?php
/**
 * A field of the form of a book to add: the label, the input and the description of a book meta, as registered.
 *
 * @link       https://github.com/19h47/library
 * @since      6.0.0
 *
 * @package    Library
 * @subpackage Library/app/partials
 *
 * @var string $key        Meta key.
 * @var array  $attributes More attributes of the input, such as its inputmode.
 */

$field_id    = 'library-add-' . str_replace( '_', '-', $key );
$description = Library_Meta::description( $key );
?>
<p class="library-field">
	<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( Library_Meta::label( $key ) ); ?></label>
	<?php
	$input = Library_Meta::input(
		$key,
		$attributes + array(
			'id'               => $field_id,
			'data-meta'        => true,
			'autocomplete'     => 'off',
			'aria-describedby' => '' !== $description ? "$field_id-description" : null,
		)
	);

	echo $input; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
	?>
	<?php if ( '' !== $description ) : ?>
		<small id="<?php echo esc_attr( "$field_id-description" ); ?>"><?php echo esc_html( $description ); ?></small>
	<?php endif; ?>
</p>
