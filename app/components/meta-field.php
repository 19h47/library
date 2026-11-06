<?php
/**
 * A field of a book meta in the form of a book: its label, its input and its description, as registered.
 *
 * @link       https://github.com/19h47/library
 * @since      6.10.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{key: string, attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;
use Library\Meta;

$args = wp_parse_args( $args, array( 'attributes' => array() ) );

Markup::render_component(
	'field',
	array(
		'id'          => 'library-edit-' . str_replace( '_', '-', $args['key'] ),
		'label'       => Meta::get_label( $args['key'] ),
		'description' => Meta::get_description( $args['key'] ),
		'control'     => Meta::get_control(
			$args['key'],
			$args['attributes'] + array(
				'data-meta'    => true,
				'autocomplete' => 'off',
			)
		),
	)
);
