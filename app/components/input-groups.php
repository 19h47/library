<?php
/**
 * A list of inputs in groups, in a fieldset, such as the people of a book by role: each group a fieldset of its own, a
 * field apart, its legend above its rows, shown once it holds one; then the pills that add an input, the group it goes
 * in, chosen among its options, and the button. Its label is read only: the legends of its groups show what they hold.
 * Its rows are clones of a template, which inputsList() adds, in the group chosen, and removes. Its attributes are the
 * ones of its fieldset.
 *
 * @link       https://github.com/19h47/library
 * @since      9.38.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, label: string, groups: array<string, string>, options: array<string, string>, options_label: string, add: string, action: string, attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;

$args = wp_parse_args( $args, array( 'attributes' => array() ) );

Markup::render_tag( '<fieldset>', $args['attributes'] + array( 'class' => 'library-inputs-list library-inputs-list--groups' ) );
?>
	<legend class="sr-only"><?php echo esc_html( $args['label'] ); ?></legend>
	<?php foreach ( $args['groups'] as $group => $legend ) : ?>
		<fieldset class="library-inputs-list__group" data-group="<?php echo esc_attr( $group ); ?>">
			<legend class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( $legend ); ?></legend>
			<ul class="library-inputs-list__items"></ul>
		</fieldset>
	<?php endforeach; ?>
	<p class="library-pills">
		<?php
		Markup::render_component(
			'pill-select',
			array(
				'id'      => $args['id'],
				'label'   => $args['options_label'],
				'options' => $args['options'],
			)
		);
		?>
		<button class="app-pill" type="button" data-action="<?php echo esc_attr( $args['action'] ); ?>"><?php echo esc_html( $args['add'] ); ?></button>
	</p>
</fieldset>
