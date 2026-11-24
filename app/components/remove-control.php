<?php
/**
 * The control that removes a row of a list of inputs, such as a reading or a person of a book: a cross, its label for
 * assistive technologies and on hover, `label`. library-app/rows removes its row.
 *
 * @link       https://github.com/19h47/library
 * @since      10.6.1
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{label: string} $args
 */

use Library\Markup;

?>
<button
	class="app-control"
	type="button"
	data-action="remove"
	aria-label="<?php echo esc_attr( $args['label'] ); ?>"
	title="<?php echo esc_attr( $args['label'] ); ?>"
><?php Markup::render_icon( 'close' ); ?></button>
