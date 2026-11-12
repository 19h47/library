<?php
/**
 * A list of inputs in groups, in a fieldset, such as the people of a book by role: each group a fieldset of its own, a
 * field apart, its legend above its rows, shown once it holds one; then the pills that add an input, the group it goes
 * in, chosen, and the button. The legend of the whole is read only: the ones of its groups show what they hold. Its rows
 * are clones of a template, which inputList() adds, in the group chosen, and removes.
 *
 * @link       https://github.com/19h47/library
 * @since      9.38.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, legend: string, groups: array<string, string>, choices: array<string, string>, label: string, add: string, action: string, attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;

$args     = wp_parse_args( $args, array( 'attributes' => array() ) );
$fieldset = Markup::set_attributes( '<fieldset>', $args['attributes'] + array( 'class' => 'library-input-list library-input-list--groups' ) );

echo $fieldset; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
?>
	<legend class="sr-only"><?php echo esc_html( $args['legend'] ); ?></legend>
	<?php foreach ( $args['groups'] as $group => $legend ) : ?>
		<fieldset class="library-input-list__group" data-group="<?php echo esc_attr( $group ); ?>">
			<legend class="app-meta"><?php echo esc_html( $legend ); ?></legend>
			<ul class="library-input-list__items"></ul>
		</fieldset>
	<?php endforeach; ?>
	<p class="library-pills">
		<?php
		Markup::render_component(
			'pill-select',
			array(
				'id'      => $args['id'],
				'label'   => $args['label'],
				'options' => $args['choices'],
			)
		);
		?>
		<button class="app-pill" type="button" data-action="<?php echo esc_attr( $args['action'] ); ?>"><?php echo esc_html( $args['add'] ); ?></button>
	</p>
</fieldset>
