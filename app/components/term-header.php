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
 * @var array{lines?: list<array{field: string, label?: string, link?: bool}>} $args
 */

$args = wp_parse_args( $args, array( 'lines' => array() ) );
?>
<header class="library-term-header">
	<h2 class="library-heading" data-field="name" tabindex="-1"></h2>
	<?php foreach ( $args['lines'] as $line ) : ?>
		<?php if ( isset( $line['label'] ) ) : ?>
			<?php $line_tag = empty( $line['link'] ) ? 'span' : 'a'; ?>
			<p data-row="<?php echo esc_attr( $line['field'] ); ?>" hidden>
				<?php echo esc_html( $line['label'] ); ?>
				<<?php echo esc_html( $line_tag ); ?> data-field="<?php echo esc_attr( $line['field'] ); ?>"<?php echo 'a' === $line_tag ? ' href=""' : ''; ?>></<?php echo esc_html( $line_tag ); ?>>
			</p>
		<?php else : ?>
			<p data-field="<?php echo esc_attr( $line['field'] ); ?>"></p>
		<?php endif; ?>
	<?php endforeach; ?>
	<div class="library-prose" data-html="description" hidden></div>
	<p data-field="summary"></p>
</header>
