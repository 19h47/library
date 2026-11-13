<?php
/**
 * The header of the page of a person, of a series or of a collection: its name, a heading, the lines that place it, its
 * description, then its reading. Its brick fills each field and shows each row it has.
 *
 * A line is a field, such as the other names of a person, or a row hidden until it has a value: its label, then its
 * field, a link to a page, such as the cycle of a series, or a text, such as its ISSN.
 *
 * @link       https://github.com/19h47/library
 * @since      9.37.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{lines?: list<array{field: string, label?: string, link?: bool}>, level?: int} $args
 */

use Library\Markup;

$args    = wp_parse_args(
	$args,
	array(
		'lines' => array(),
		'level' => 2,
	)
);
$heading = Markup::get_heading( $args['level'] );
?>
<header class="library-term-header">
	<<?php echo esc_html( $heading ); ?> class="library-heading" data-field="name" tabindex="-1"></<?php echo esc_html( $heading ); ?>>
	<?php foreach ( $args['lines'] as $line ) : ?>
		<?php if ( isset( $line['label'] ) ) : ?>
			<p data-row="<?php echo esc_attr( $line['field'] ); ?>" hidden>
				<?php echo esc_html( $line['label'] ); ?>
				<?php
				Markup::render_tag(
					empty( $line['link'] ) ? '<span></span>' : '<a></a>',
					array(
						'data-field' => $line['field'],
						'href'       => empty( $line['link'] ) ? null : '',
					)
				);
				?>
			</p>
		<?php else : ?>
			<p data-field="<?php echo esc_attr( $line['field'] ); ?>"></p>
		<?php endif; ?>
	<?php endforeach; ?>
	<div class="library-prose" data-html="description" hidden></div>
	<p data-field="summary"></p>
</header>
