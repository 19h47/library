<?php
/**
 * Plugin readme, as the sections of the "View details" modal.
 *
 * @link       https://github.com/19h47/library
 * @since      2.3.2
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Turns a readme.txt into the HTML sections of the plugin information, as WordPress.org does.
 *
 * Handles what the readme uses: "== Section ==", "= Heading =", paragraphs, nested bulleted and
 * numbered lists, **bold**, `code` and [links](https://…).
 *
 * @since      2.3.2
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Readme {

	/**
	 * Section keys of the plugin information by readme section name.
	 *
	 * @var array<string,string>
	 */
	private const SECTIONS = array(
		'Description'                => 'description',
		'Installation'               => 'installation',
		'Frequently Asked Questions' => 'faq',
		'Changelog'                  => 'changelog',
	);

	/**
	 * Pattern of a list item: its indent, its marker and its text.
	 *
	 * @var string
	 */
	private const LIST_ITEM = '/^( *)(\*|\d+\.) +(.*)$/';

	/**
	 * HTML sections of a readme, keyed as the plugin information expects them.
	 *
	 * @param string $readme Readme content.
	 * @return array<string,string>
	 */
	public static function get_sections( string $readme ): array {
		$parts    = preg_split( '/^==\s*([^=].*?)\s*==\s*$/m', str_replace( "\r\n", "\n", $readme ), -1, PREG_SPLIT_DELIM_CAPTURE );
		$sections = array();

		// The text before the first section holds the headers.
		foreach ( array_chunk( array_slice( $parts, 1 ), 2 ) as [ $name, $text ] ) {
			$key = self::SECTIONS[ $name ] ?? null;

			if ( $key ) {
				$sections[ $key ] = self::get_section_html( $text );
			}
		}

		return $sections;
	}

	/**
	 * Value of a readme header, e.g. "Tested up to".
	 *
	 * @param string $readme Readme content.
	 * @param string $name   Header name.
	 * @return string
	 */
	public static function get_header( string $readme, string $name ): string {
		return preg_match( '/^' . preg_quote( $name, '/' ) . ':\s*(.+?)\s*$/mi', $readme, $match ) ? $match[1] : '';
	}

	/**
	 * HTML of a section: headings, lists and paragraphs, split by blank lines.
	 *
	 * @param string $text Section text.
	 * @return string
	 */
	private static function get_section_html( string $text ): string {
		$html = '';

		foreach ( preg_split( '/\n\s*\n/', trim( $text ) ) as $block ) {
			$lines = explode( "\n", $block );

			if ( preg_match( '/^=\s*(.+?)\s*=$/', $lines[0], $match ) ) {
				$html .= '<h4>' . self::get_inline_html( $match[1] ) . '</h4>';
				array_shift( $lines );
			}

			if ( ! $lines ) {
				continue;
			}

			$html .= preg_match( self::LIST_ITEM, $lines[0] ) ? self::get_list_html( $lines ) : '<p>' . self::get_inline_html( implode( ' ', $lines ) ) . '</p>';
		}

		return $html;
	}

	/**
	 * HTML of list lines, a deeper indent opening a list in the item above.
	 *
	 * @param string[] $lines List lines.
	 * @return string
	 */
	private static function get_list_html( array $lines ): string {
		$html  = '';
		$lists = array();

		foreach ( $lines as $line ) {
			if ( ! preg_match( self::LIST_ITEM, $line, $match ) ) {
				$html .= ' ' . self::get_inline_html( trim( $line ) );
				continue;
			}

			$indent = strlen( $match[1] );
			$tag    = '*' === $match[2] ? 'ul' : 'ol';

			while ( $lists && $indent < end( $lists )['indent'] ) {
				$html .= '</li></' . array_pop( $lists )['tag'] . '>';
			}

			if ( $lists && end( $lists )['indent'] === $indent ) {
				$html .= '</li>';
			} else {
				$html   .= "<$tag>";
				$lists[] = array(
					'indent' => $indent,
					'tag'    => $tag,
				);
			}

			$html .= '<li>' . self::get_inline_html( $match[3] );
		}

		while ( $lists ) {
			$html .= '</li></' . array_pop( $lists )['tag'] . '>';
		}

		return $html;
	}

	/**
	 * HTML of a line of text, escaped, with its code, bold and links.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function get_inline_html( string $text ): string {
		$html = esc_html( $text );
		$html = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $html );
		$html = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html );

		return preg_replace_callback(
			'/\[([^\]]+)\]\(([^)\s]+)\)/',
			fn( array $link ) => '<a href="' . esc_url( html_entity_decode( $link[2] ) ) . '">' . $link[1] . '</a>',
			$html
		);
	}
}
