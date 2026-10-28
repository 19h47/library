<?php
/**
 * A field of an image, such as the cover of a book: the image shown, the pill that picks a photo, and the one that
 * removes it, shown when there is one.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, name: string, label: string, remove: string} $args
 */

?>
<div class="library-image-field">
	<img class="library-image-field__preview" alt="" hidden>
	<label class="app-pill" for="<?php echo esc_attr( $args['id'] ); ?>">
		<input class="sr-only" id="<?php echo esc_attr( $args['id'] ); ?>" type="file" name="<?php echo esc_attr( $args['name'] ); ?>" accept="image/*">
		<?php echo esc_html( $args['label'] ); ?>
	</label>
	<button class="app-pill" type="button" data-action="<?php echo esc_attr( "remove-{$args['name']}" ); ?>" hidden><?php echo esc_html( $args['remove'] ); ?></button>
</div>
