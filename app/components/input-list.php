<?php
/**
 * A list of inputs in a fieldset, its legend above it and the button that adds an input under it, such as the people
 * of a book: its rows are clones of a template, which inputList() adds and removes. Its description, under its rows,
 * describes it; other buttons may follow the one that adds, each named by its action, such as a reading whose date is
 * unknown; a class names it in its place, such as the read dates of the reading of a book.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id?: string, class?: string, legend: string, description?: string, add: string, action: string, actions?: array<string, string>, list?: array<string, string|bool|null>, attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;

$args           = wp_parse_args(
	$args,
	array(
		'id'          => '',
		'class'       => '',
		'description' => '',
		'actions'     => array(),
		'list'        => array(),
		'attributes'  => array(),
	)
);
$description_id = "{$args['id']}-description";
$fieldset       = Markup::set_attributes(
	'<fieldset>',
	$args['attributes'] + array(
		'class'            => trim( "library-input-list {$args['class']}" ),
		'aria-describedby' => '' !== $args['description'] ? $description_id : null,
	)
);

echo $fieldset; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
?>
	<legend class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( $args['legend'] ); ?></legend>
	<?php echo Markup::set_attributes( '<ul></ul>', $args['list'] + array( 'class' => 'library-input-list__items' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?>
	<?php if ( '' !== $args['description'] ) : ?>
		<small id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $args['description'] ); ?></small>
	<?php endif; ?>
	<p class="library-pills">
		<button class="app-pill" type="button" data-action="<?php echo esc_attr( $args['action'] ); ?>"><?php echo esc_html( $args['add'] ); ?></button>
		<?php foreach ( $args['actions'] as $other_action => $label ) : ?>
			<button class="app-pill" type="button" data-action="<?php echo esc_attr( $other_action ); ?>"><?php echo esc_html( $label ); ?></button>
		<?php endforeach; ?>
	</p>
</fieldset>
