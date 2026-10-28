<?php
/**
 * A field of a form: its label above its control, and its description under it, such as the title of a book.
 *
 * A required field says so in its label; its description describes its control. Its control is an input, unless it is
 * given another, such as a textarea, a select with its options or the input of a book meta; the field sets its
 * attributes on it.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, label: string, required?: bool, description?: string, control?: string, attributes?: array<string, string|bool|null>} $args
 */

$args = wp_parse_args(
	$args,
	array(
		'required'    => false,
		'description' => '',
		'control'     => '<input>',
		'attributes'  => array(),
	)
);

$description_id = "{$args['id']}-description";
$control        = Library::control(
	$args['control'],
	$args['attributes'] + array(
		'id'               => $args['id'],
		'required'         => $args['required'] ? true : null,
		'aria-describedby' => '' !== $args['description'] ? $description_id : null,
	)
);
?>
<p class="library-field">
	<label for="<?php echo esc_attr( $args['id'] ); ?>">
		<?php
		/* translators: %s: label of a field that must be filled in, e.g. Title. */
		echo esc_html( $args['required'] ? sprintf( _x( '%s (required)', 'one field', 'library' ), $args['label'] ) : $args['label'] );
		?>
	</label>
	<?php echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?>
	<?php if ( '' !== $args['description'] ) : ?>
		<small id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $args['description'] ); ?></small>
	<?php endif; ?>
</p>
