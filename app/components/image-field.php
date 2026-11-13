<?php
/**
 * The field of the cover of a book: the cover shown, a book-cover, the photo picked or the cover of the book, the pill
 * that picks a photo, and the one that removes it, by its action, shown when there is one.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, name: string, label: string, remove: string, action: string} $args
 */

use Library\Markup;

?>
<div class="library-image-field">
	<?php Markup::render_component( 'book-cover', array( 'attributes' => array( 'data-part' => 'preview' ) ) ); ?>
	<label class="app-pill" for="<?php echo esc_attr( $args['id'] ); ?>">
		<input class="sr-only" id="<?php echo esc_attr( $args['id'] ); ?>" type="file" name="<?php echo esc_attr( $args['name'] ); ?>" accept="image/*">
		<?php echo esc_html( $args['label'] ); ?>
	</label>
	<button class="app-pill" type="button" data-action="<?php echo esc_attr( $args['action'] ); ?>" hidden><?php echo esc_html( $args['remove'] ); ?></button>
</div>
