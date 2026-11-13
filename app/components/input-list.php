<?php
/**
 * A list of inputs in a fieldset, its label above it and the button that adds an input under it, such as the readings
 * of a book: its rows are clones of a template, which inputList() adds and removes. Its description, under its rows,
 * describes it; other buttons may follow the one that adds, each named by its action, such as a reading whose date is
 * unknown. Its attributes are the ones of its fieldset.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, label: string, description?: string, add: string, action: string, actions?: array<string, string>, attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;

$args           = wp_parse_args(
	$args,
	array(
		'description' => '',
		'actions'     => array(),
		'attributes'  => array(),
	)
);
$description_id = "{$args['id']}-description";

Markup::render_tag(
	'<fieldset>',
	$args['attributes'] + array(
		'id'               => $args['id'],
		'class'            => 'library-input-list',
		'aria-describedby' => '' !== $args['description'] ? $description_id : null,
	)
);
?>
	<legend class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( $args['label'] ); ?></legend>
	<ul class="library-input-list__items"></ul>
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
