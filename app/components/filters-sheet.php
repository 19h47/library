<?php
/**
 * The filters of a list, such as the books or the readings of the diary: a button that opens their fields in a sheet,
 * `id`, and the filters chosen, a pill each, which says its choice, or its label and how many of its values are
 * checked, and removes it. A filter is a select of its options, the first one for none, or, `multiple`, a pill
 * checkbox for each of them, none checked for any, with how many each has, `counts`, if given, and a button that
 * checks them all, or unchecks them; each field is named after its filter.
 * Its parts take their place among the controls that hold it. Its root is its custom element, filters-sheet, which keeps
 * the pills, the counts and the button that checks all as the fields change; the brick library-filters around it in the
 * app ties it to the store.
 *
 * @link       https://github.com/19h47/library
 * @since      10.5.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, filters: array<string, array{label: string, options: array<int|string, string>, multiple?: bool, counts?: array<int|string, int>}>} $args
 */

use Library\Markup;

$title_id = "{$args['id']}-title";
?>
<filters-sheet>
	<button class="app-pill" type="button" commandfor="<?php echo esc_attr( $args['id'] ); ?>" command="show-modal" aria-haspopup="dialog" data-part="open">
		<span data-field="label"><?php esc_html_e( 'Filters', 'library' ); ?></span>
	</button>
	<p data-list="chosen" hidden>
		<?php foreach ( $args['filters'] as $filter => $filter_args ) : ?>
			<button class="app-pill" type="button" data-filter="<?php echo esc_attr( $filter ); ?>" data-label="<?php echo esc_attr( $filter_args['label'] ); ?>" hidden>
				<span class="sr-only"><?php esc_html_e( 'Remove the filter:', 'library' ); ?></span>
				<span data-field="chosen"></span>
				<?php Markup::render_icon( 'close' ); ?>
			</button>
		<?php endforeach; ?>
	</p>
	<dialog class="library-sheet" id="<?php echo esc_attr( $args['id'] ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
		<?php Markup::render_component( 'dialog-close', array( 'dialog' => $args['id'] ) ); ?>
		<h2 class="library-heading" id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'Filters', 'library' ); ?></h2>
		<?php
		foreach ( $args['filters'] as $filter => $filter_args ) {
			$field_id = "{$args['id']}-$filter";

			if ( $filter_args['multiple'] ?? false ) {
				Markup::render_component(
					'checkboxes-field',
					array(
						'inline'     => true,
						'id'         => $field_id,
						'label'      => $filter_args['label'],
						'name'       => $filter,
						'options'    => $filter_args['options'],
						'counts'     => $filter_args['counts'] ?? array(),
						'toggle_all' => true,
					)
				);
			} else {
				Markup::render_component(
					'field',
					array(
						'id'         => $field_id,
						'label'      => $filter_args['label'],
						'control'    => Markup::get_select( $filter_args['options'] ),
						'attributes' => array( 'name' => $filter ),
					)
				);
			}
		}
		?>
		<p>
			<button class="app-pill app-pill--primary" type="button" commandfor="<?php echo esc_attr( $args['id'] ); ?>" command="close" data-field="show"></button>
		</p>
	</dialog>
</filters-sheet>
