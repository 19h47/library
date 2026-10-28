<?php
/**
 * A pill that holds a select, its label hidden: the value it shows says what it chooses, such as the reading state of the books.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/partials/components
 *
 * @var array{id: string, label: string, options: array<string|int, string>, attributes?: array<string, string|bool|null>} $args
 */

$args   = wp_parse_args( $args, array( 'attributes' => array() ) );
$select = Library::control( Library::select( $args['options'] ), $args['attributes'] + array( 'id' => $args['id'] ) );
?>
<label class="app-pill" for="<?php echo esc_attr( $args['id'] ); ?>">
	<span class="sr-only"><?php echo esc_html( $args['label'] ); ?></span>
	<?php echo $select; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?>
</label>
