<?php
/**
 * A field of several choices: its legend above a pill for each one, a checkbox, and its description under them, such as
 * the original languages of a book. The choices checked by default are the ones a reset of the form checks again.
 *
 * @link       https://github.com/19h47/library
 * @since      9.28.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, label: string, name: string, choices: array<int|string, string>, checked?: array<int|string>, description?: string} $args
 */

use Library\Markup;

$args = wp_parse_args(
	$args,
	array(
		'checked'     => array(),
		'description' => '',
	)
);

$description_id = "{$args['id']}-description";
$fieldset       = Markup::set_attributes(
	'<fieldset class="library-field">',
	array(
		'id'               => $args['id'],
		'aria-describedby' => '' !== $args['description'] ? $description_id : null,
	)
);

echo $fieldset; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
?>
	<legend class="app-meta"><?php echo esc_html( $args['label'] ); ?></legend>
	<div class="library-pills">
		<?php
		foreach ( $args['choices'] as $value => $label ) {
			Markup::render_component(
				'pill-checkbox',
				array(
					'id'         => "{$args['id']}-$value",
					'label'      => $label,
					'attributes' => array(
						'name'    => $args['name'],
						'value'   => (string) $value,
						'checked' => in_array( $value, $args['checked'], true ),
					),
				)
			);
		}
		?>
	</div>
	<?php if ( '' !== $args['description'] ) : ?>
		<small id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $args['description'] ); ?></small>
	<?php endif; ?>
</fieldset>
