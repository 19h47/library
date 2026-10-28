<?php
/**
 * A pill that holds a checkbox and its label, such as whether a book is read: the checkbox given built, or a plain one.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/partials/components
 *
 * @var array{id: string, label: string, control?: string, attributes?: array<string, string|bool|null>} $args
 */

$checkbox = Library::control( $args['control'] ?? '<input type="checkbox">', ( $args['attributes'] ?? array() ) + array( 'id' => $args['id'] ) );
?>
<label class="app-pill" for="<?php echo esc_attr( $args['id'] ); ?>">
	<?php echo $checkbox; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?>
	<?php echo esc_html( $args['label'] ); ?>
</label>
