<?php
/**
 * The Covers checkbox at the end of a title, which shows the lists of books as a grid of their covers, or as cards, as
 * library-covers-toggle, which holds it in the app, says to the store.
 *
 * @link       https://github.com/19h47/library
 * @since      9.26.3
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string} $args
 */

use Library\Markup;

Markup::render_component(
	'pill-checkbox',
	array(
		'id'    => $args['id'],
		'label' => __( 'Covers', 'library' ),
	)
);
