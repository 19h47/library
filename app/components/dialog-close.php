<?php
/**
 * The control that closes a dialog, at its corner, as the controls of the toolbar: a command of the dialog, which closes
 * it without a script, inside a form as well.
 *
 * @link       https://github.com/19h47/library
 * @since      9.37.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{dialog: string} $args
 */

use Library\Markup;
?>
<p class="app-toolbar">
	<button class="app-control" type="button" commandfor="<?php echo esc_attr( $args['dialog'] ); ?>" command="close" aria-label="<?php esc_attr_e( 'Close', 'library' ); ?>" title="<?php esc_attr_e( 'Close', 'library' ); ?>"><?php Markup::render_icon( 'close' ); ?></button>
</p>
