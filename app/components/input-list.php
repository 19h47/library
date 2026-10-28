<?php
/**
 * A list of inputs in a fieldset, its legend above it and the button that adds an input under it, such as the authors
 * of a book: its rows are clones of a template, which inputList() adds and removes. Required, its legend says so; a
 * class names it in its place, such as the read dates of the reading of a book.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{class?: string, legend: string, required?: bool, add: string, action: string, list?: array<string, string|bool|null>, attributes?: array<string, string|bool|null>} $args
 */

$args  = wp_parse_args(
	$args,
	array(
		'class'      => '',
		'required'   => false,
		'list'       => array(),
		'attributes' => array(),
	)
);
$class = trim( "library-input-list {$args['class']}" );

echo Library::control( '<fieldset>', $args['attributes'] + array( 'class' => $class ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
?>
	<legend>
		<?php
		/* translators: %s: label of fields that must be filled in, e.g. Read dates. */
		echo esc_html( $args['required'] ? sprintf( _x( '%s (required)', 'several fields', 'library' ), $args['legend'] ) : $args['legend'] );
		?>
	</legend>
	<?php echo Library::control( '<ul></ul>', $args['list'] + array( 'class' => 'library-input-list__items' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?>
	<p>
		<button class="app-pill" type="button" data-action="<?php echo esc_attr( $args['action'] ); ?>"><?php echo esc_html( $args['add'] ); ?></button>
	</p>
</fieldset>
