<?php
/**
 * A pill that holds a checkbox and its label, such as whether a book is read: the checkbox given built, or a plain one;
 * its name, then how many it has, `count`, if given, such as the books of a form, each a part a script reads apart.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, label: string, count?: int|null, control?: string, attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;

$args = wp_parse_args(
	$args,
	array(
		'count'      => null,
		'control'    => '<input type="checkbox">',
		'attributes' => array(),
	)
);
?>
<label class="app-pill" for="<?php echo esc_attr( $args['id'] ); ?>">
	<?php Markup::render_tag( $args['control'], $args['attributes'] + array( 'id' => $args['id'] ) ); ?>
	<span data-part="name"><?php echo esc_html( $args['label'] ); ?></span>
	<?php if ( null !== $args['count'] ) : ?>
		<span class="app-text-muted" data-part="count"><?php echo esc_html( number_format_i18n( $args['count'] ) ); ?></span>
	<?php endif; ?>
</label>
