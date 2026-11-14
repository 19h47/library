<?php
/**
 * The field of the cover of a book: the cover shown, a book-cover, the photo picked or the cover of the book, the pill
 * that picks a photo, and the one that removes it, shown while there is one. Its root is its custom element, image-field,
 * which shows the photo picked at once and, removed, checks its checkbox, `<name>_removed`, which the reset of the form
 * unchecks; its place shows the cover the book has.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, name: string, label: string, remove: string} $args
 */

use Library\Markup;

?>
<image-field>
	<?php Markup::render_component( 'book-cover', array( 'attributes' => array( 'data-part' => 'preview' ) ) ); ?>
	<label class="app-pill" for="<?php echo esc_attr( $args['id'] ); ?>">
		<input class="sr-only" id="<?php echo esc_attr( $args['id'] ); ?>" type="file" name="<?php echo esc_attr( $args['name'] ); ?>" accept="image/*">
		<?php echo esc_html( $args['label'] ); ?>
	</label>
	<button class="app-pill" type="button" data-action="remove"><?php echo esc_html( $args['remove'] ); ?></button>
	<input type="checkbox" name="<?php echo esc_attr( $args['name'] ); ?>_removed" hidden>
</image-field>
