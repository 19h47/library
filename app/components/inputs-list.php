<?php
/**
 * A list of inputs in a fieldset, its label above it and the button that adds an input under it, such as the readings
 * of a book: its rows are clones of its template. Its description, under its rows, describes it; the buttons of its
 * variants may follow the one that adds, each a row of its own template and of a fixed value, such as a reading whose
 * date is unknown. Its root is its custom element, inputs-list, which adds and removes its rows; its attributes are
 * the ones of its fieldset.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, label: string, description?: string, template: string, add: string, variants?: array<array{label: string, template: string, value: string}>, attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;

$args           = wp_parse_args(
	$args,
	array(
		'description' => '',
		'variants'    => array(),
		'attributes'  => array(),
	)
);
$description_id = "{$args['id']}-description";
?>
<inputs-list template="<?php echo esc_attr( $args['template'] ); ?>">
<?php
Markup::render_tag(
	'<fieldset>',
	$args['attributes'] + array(
		'id'               => $args['id'],
		'class'            => 'library-inputs-list',
		'aria-describedby' => '' !== $args['description'] ? $description_id : null,
	)
);
?>
	<legend class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( $args['label'] ); ?></legend>
	<ul class="library-inputs-list__items"></ul>
	<?php if ( '' !== $args['description'] ) : ?>
		<small id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $args['description'] ); ?></small>
	<?php endif; ?>
	<p class="library-pills">
		<button class="app-pill" type="button" data-action="add"><?php echo esc_html( $args['add'] ); ?></button>
		<?php foreach ( $args['variants'] as $variant ) : ?>
			<button class="app-pill" type="button" data-action="add" data-template="<?php echo esc_attr( $variant['template'] ); ?>" data-value="<?php echo esc_attr( $variant['value'] ); ?>"><?php echo esc_html( $variant['label'] ); ?></button>
		<?php endforeach; ?>
	</p>
</fieldset>
</inputs-list>
