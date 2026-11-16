<?php
/**
 * A date often known by its year only, such as the first publication of a book: its legend above its day, its month in
 * words and its year, side by side, then its description. Its root is its custom element, partial-date, named as its
 * field: the day waits for a month and lists its days, the year is required once a month is chosen, and its form sends
 * its value, 1929, 1929-11 or 1929-11-08, under its name, its parts sending none.
 *
 * @link       https://github.com/19h47/library
 * @since      10.3.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string, label: string, name: string, description?: string, attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;

$args = wp_parse_args(
	$args,
	array(
		'description' => '',
		'attributes'  => array(),
	)
);

global $wp_locale;

$description_id = "{$args['id']}-description";
$days           = array( '' => __( 'Day', 'library' ) );
$months         = array( '' => __( 'Month', 'library' ) );

foreach ( range( 1, 31 ) as $day ) {
	$days[ sprintf( '%02d', $day ) ] = (string) $day;
}

foreach ( range( 1, 12 ) as $month ) {
	$months[ sprintf( '%02d', $month ) ] = $wp_locale->get_month( $month );
}

Markup::render_tag( '<partial-date>', $args['attributes'] + array( 'name' => $args['name'] ) );
Markup::render_tag(
	'<fieldset class="library-field">',
	array(
		'id'               => $args['id'],
		'aria-describedby' => '' !== $args['description'] ? $description_id : null,
	)
);
?>
	<legend class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( $args['label'] ); ?></legend>
	<?php
	Markup::render_tag(
		Markup::get_select( $days ),
		array(
			'class'      => 'library-input',
			'aria-label' => __( 'Day', 'library' ),
			'data-part'  => 'day',
		)
	);
	Markup::render_tag(
		Markup::get_select( $months ),
		array(
			'class'      => 'library-input',
			'aria-label' => __( 'Month', 'library' ),
			'data-part'  => 'month',
		)
	);
	?>
	<input class="library-input" type="text" inputmode="numeric" pattern="\d{4}" maxlength="4" size="5" autocomplete="off" placeholder="<?php esc_attr_e( 'Year', 'library' ); ?>" aria-label="<?php esc_attr_e( 'Year', 'library' ); ?>" data-part="year">
	<?php if ( '' !== $args['description'] ) : ?>
		<small id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $args['description'] ); ?></small>
	<?php endif; ?>
</fieldset>
</partial-date>
