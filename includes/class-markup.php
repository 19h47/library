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
 * Prints the components of the app and its icons, and builds the markup of a control: its attributes, a select with its options.
 *
 * @since      9.25.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Markup {

	/**
	 * The icons of the app, from Material Symbols, outlined, at 24 px, as its own icon, by Google, under the Apache License
	 * 2.0 (https://github.com/google/material-design-icons): the path of each one, in a box of 960 drawn from -960.
	 *
	 * @var array<string,string>
	 */
	public const array ICONS = array(
		'add'        => 'M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z',
		'arrow_back' => 'm313-440 224 224-57 56-320-320 320-320 57 56-224 224h487v80H313Z',
		'close'      => 'm256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z',
		'dark_mode'  => 'M480-120q-150 0-255-105T120-480q0-150 105-255t255-105q14 0 27.5 1t26.5 3q-41 29-65.5 75.5T444-660q0 90 63 153t153 63q55 0 101-24.5t75-65.5q2 13 3 26.5t1 27.5q0 150-105 255T480-120Zm0-80q88 0 158-48.5T740-375q-20 5-40 8t-40 3q-123 0-209.5-86.5T364-660q0-20 3-40t8-40q-78 32-126.5 102T200-480q0 116 82 198t198 82Zm-10-270Z',
		'light_mode' => 'M480-360q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Zm0 80q-83 0-141.5-58.5T280-480q0-83 58.5-141.5T480-680q83 0 141.5 58.5T680-480q0 83-58.5 141.5T480-280ZM200-440H40v-80h160v80Zm720 0H760v-80h160v80ZM440-760v-160h80v160h-80Zm0 720v-160h80v160h-80ZM256-650l-101-97 57-59 96 100-52 56Zm492 496-97-101 53-55 101 97-57 59Zm-98-550 97-101 59 57-100 96-56-52ZM154-212l101-97 55 53-97 101-59-57Zm326-268Z',
		'search'     => 'M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z',
	);

	/**
	 * Print an icon of ICONS, the size of the text, in its color, hidden from assistive technologies: what holds it names
	 * what it does, such as a button by its label.
	 *
	 * @param string $name Name of the icon, as Material Symbols names it, e.g. close.
	 * @return void
	 */
	public static function render_icon( string $name ): void {
		printf(
			'<svg class="library-icon" data-icon="%1$s" viewBox="0 -960 960 960" aria-hidden="true" focusable="false"><path d="%2$s"/></svg>',
			esc_attr( $name ),
			esc_attr( self::ICONS[ $name ] )
		);
	}

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
	 * Print a tag with its attributes, as set_attributes() sets them: the opening tag of an element a component holds,
	 * such as a fieldset whose attributes its arguments give, or a whole element, such as a control.
	 *
	 * @param string                          $markup     Markup of the tag, or of the element.
	 * @param array<string, string|bool|null> $attributes Attributes of its first tag.
	 * @return void
	 */
	public static function render_tag( string $markup, array $attributes ): void {
		echo self::set_attributes( $markup, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
	}

	/**
	 * Name of the tag of a heading of a level, from 1 to 6, such as h3: a component that has a heading takes its level
	 * from the place that holds it.
	 *
	 * @param int $level Level of the heading.
	 * @return string
	 */
	public static function get_heading( int $level ): string {
		return 'h' . min( 6, max( 1, $level ) );
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
