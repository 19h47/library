<?php
/**
 * A field of several choices shown all at once, such as the forms of the books shown: its legend above a pill checkbox
 * for each option, named as the field, with how many it has, `counts`, such as the books of a form, 0 for an option
 * left out of them; the options checked given by `checked`.
 *
 * @link       https://github.com/19h47/library
 * @since      10.5.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, label: string, name: string, options: array<int|string, string>, counts?: array<int|string, int>, checked?: array<int|string>} $args
 */

use Library\Markup;

$args = wp_parse_args(
	$args,
	array(
		'counts'  => array(),
		'checked' => array(),
	)
);
?>
<fieldset class="library-field" id="<?php echo esc_attr( $args['id'] ); ?>">
	<legend class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( $args['label'] ); ?></legend>
	<p class="library-pills">
		<?php
		foreach ( $args['options'] as $value => $label ) {
			Markup::render_component(
				'pill-checkbox',
				array(
					'id'         => "{$args['id']}-$value",
					'label'      => $label,
					'count'      => $args['counts'] ? $args['counts'][ $value ] ?? 0 : null,
					'attributes' => array(
						'name'    => $args['name'],
						'value'   => (string) $value,
						'checked' => in_array( $value, $args['checked'], true ),
					),
				)
			);
		}
		?>
	</p>
</fieldset>
