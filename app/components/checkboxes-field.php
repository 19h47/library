<?php
/**
 * A field of several choices, such as the original languages of a book: its legend above a pill for each choice made,
 * which removes it, and a button that opens all of them in a sheet, a checkbox each; its description under them. The
 * checkboxes are the field, in its form: the pills only show them. The choices checked by default are the ones a reset
 * of the form checks again.
 *
 * @link       https://github.com/19h47/library
 * @since      9.28.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, label: string, name: string, add: string, choices: array<int|string, string>, checked?: array<int|string>, description?: string} $args
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
$dialog_id      = "{$args['id']}-dialog";
$title_id       = "{$args['id']}-title";
$fieldset       = Markup::set_attributes(
	'<fieldset class="library-field">',
	array(
		'id'               => $args['id'],
		'aria-describedby' => '' !== $args['description'] ? $description_id : null,
	)
);
?>
<library-choices>
	<?php echo $fieldset; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?>
		<legend class="app-meta"><?php echo esc_html( $args['label'] ); ?></legend>
		<p class="library-pills">
			<?php foreach ( $args['choices'] as $value => $label ) : ?>
				<button class="app-pill" type="button" data-value="<?php echo esc_attr( (string) $value ); ?>" hidden>
					<span class="sr-only"><?php esc_html_e( 'Remove:', 'library' ); ?></span>
					<?php echo esc_html( $label ); ?>
					<span aria-hidden="true">×</span>
				</button>
			<?php endforeach; ?>
			<button class="app-pill" type="button" commandfor="<?php echo esc_attr( $dialog_id ); ?>" command="show-modal" aria-haspopup="dialog"><?php echo esc_html( $args['add'] ); ?></button>
		</p>
		<dialog class="library-sheet" id="<?php echo esc_attr( $dialog_id ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<p class="app-toolbar">
				<button class="app-control" type="button" commandfor="<?php echo esc_attr( $dialog_id ); ?>" command="close" aria-label="<?php esc_attr_e( 'Close', 'library' ); ?>" title="<?php esc_attr_e( 'Close', 'library' ); ?>">×</button>
			</p>
			<h2 class="library-book__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $args['label'] ); ?></h2>
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
			<p>
				<button class="app-pill app-pill--primary" type="button" commandfor="<?php echo esc_attr( $dialog_id ); ?>" command="close"><?php esc_html_e( 'Done', 'library' ); ?></button>
			</p>
		</dialog>
		<?php if ( '' !== $args['description'] ) : ?>
			<small id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $args['description'] ); ?></small>
		<?php endif; ?>
	</fieldset>
</library-choices>
