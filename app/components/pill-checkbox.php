<?php
/**
 * A pill that holds a checkbox and its label, such as whether a book is read: the checkbox given built, or a plain one.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, label: string, control?: string, attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;

$args = wp_parse_args(
	$args,
	array(
		'control'    => '<input type="checkbox">',
		'attributes' => array(),
	)
);
?>
<label class="app-pill" for="<?php echo esc_attr( $args['id'] ); ?>">
	<?php Markup::render_tag( $args['control'], $args['attributes'] + array( 'id' => $args['id'] ) ); ?>
	<?php echo esc_html( $args['label'] ); ?>
</label>
