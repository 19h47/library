<?php
/**
 * Markup shared by the forms of the admin and of the reading app.
 *
 * @link       https://github.com/19h47/library
 * @since      9.25.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

use WP_HTML_Tag_Processor;

/**
 * Prints the components of the app, and builds the markup of a control: its attributes, a select with its options.
 *
 * @since      9.25.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Markup {

	/**
	 * Print a component of the app, from app/components, with its arguments in `$args`.
	 *
	 * As load_template() does, without the variables of the query it brings: get_template_part() looks in the theme only.
	 *
	 * @param string               $name Name of the component, e.g. field.
	 * @param array<string, mixed> $args Arguments of the component, as its file describes them.
	 * @return void
	 */
	public static function render_component( string $name, array $args = array() ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Read by the component it includes.
		require LIBRARY_DIR_PATH . "app/components/$name.php";
	}

	/**
	 * Markup of a control with its attributes, set on its first tag: an attribute true is set alone, null is left out.
	 *
	 * @param string                          $markup     Markup of the control, such as <input> or a select with its options.
	 * @param array<string, string|bool|null> $attributes Attributes of the control.
	 * @return string
	 */
	public static function set_attributes( string $markup, array $attributes ): string {
		$control = new WP_HTML_Tag_Processor( $markup );

		$control->next_tag();

		foreach ( array_filter( $attributes, fn( $value ) => null !== $value ) as $name => $value ) {
			$control->set_attribute( $name, is_bool( $value ) ? $value : (string) $value );
		}

		return $control->get_updated_html();
	}

	/**
	 * Markup of a select with its options, the one of its value selected.
	 *
	 * @param array<string|int, string> $options Labels of the options, by value.
	 * @param string|null               $value   Value of the option selected, or null for the first one.
	 * @return string
	 */
	public static function get_select( array $options, ?string $value = null ): string {
		$markup = '';

		foreach ( $options as $option => $label ) {
			$selected = null !== $value && (string) $option === $value ? ' selected' : '';
			$markup  .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( (string) $option ), $selected, esc_html( $label ) );
		}

		return "<select>$markup</select>";
	}
}
