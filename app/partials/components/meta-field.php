<?php
/**
 * A field of a book meta in the form of a book: its label, its input and its description, as registered.
 *
 * @link       https://github.com/19h47/library
 * @since      6.0.0
 *
 * @package    Library
 * @subpackage Library/app/partials/components
 *
 * @var array{key: string, attributes?: array<string, string|bool|null>} $args
 */

Library::component(
	'field',
	array(
		'id'          => 'library-edit-' . str_replace( '_', '-', $args['key'] ),
		'label'       => Library_Meta::label( $args['key'] ),
		'description' => Library_Meta::description( $args['key'] ),
		'control'     => Library_Meta::input(
			$args['key'],
			( $args['attributes'] ?? array() ) + array(
				'data-meta'    => true,
				'autocomplete' => 'off',
			)
		),
	)
);
