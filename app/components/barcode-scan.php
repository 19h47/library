<?php
/**
 * The scan of the barcode of a book: the pill that takes its photo, then what the photo told, a status. Its root is its
 * brick, barcode-scan, which shows it where the browser reads EAN-13 barcodes, and types the barcode read in
 * the field it is for, as if typed; a photo without one says so.
 *
 * @link       https://github.com/19h47/library
 * @since      9.45.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, for: string} $args
 */

?>
<barcode-scan for="<?php echo esc_attr( $args['for'] ); ?>" hidden>
	<label class="app-pill" for="<?php echo esc_attr( $args['id'] ); ?>">
		<input class="sr-only" id="<?php echo esc_attr( $args['id'] ); ?>" type="file" accept="image/*" capture="environment">
		<?php esc_html_e( 'Scan the barcode', 'library' ); ?>
	</label>
	<p role="status"><span data-field="message" hidden></span></p>
</barcode-scan>
