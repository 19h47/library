<?php
/**
 * Fields of the term screens of the admin: the term meta of the library taxonomies, such as the ISSN of a series.
 *
 * @link       https://github.com/19h47/library
 * @since      9.35.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

namespace Library\Admin;

use Library\Markup;
use Library\Taxonomies;
use WP_Term;

/**
 * Adds a field per term meta registered on a library taxonomy to the screen that edits a term, with the label, the
 * description and the pattern of its registration, and saves it.
 *
 * @since      9.35.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Term_Fields {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		foreach ( Taxonomies::TAXONOMIES as $taxonomy ) {
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'render_fields' ), 10, 2 );
			add_action( "edited_{$taxonomy}", array( $this, 'save_fields' ) );
		}
	}

	/**
	 * Term meta registered on a taxonomy, by key, each with its registration.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return array<string,array<string,mixed>>
	 */
	private static function get_fields( string $taxonomy ): array {
		return get_registered_meta_keys( 'term', $taxonomy );
	}

	/**
	 * Render a row per term meta of the taxonomy, after those of core: a text field with its pattern, anchored already by
	 * HTML, and its description.
	 *
	 * @param WP_Term $term     Term edited.
	 * @param string  $taxonomy Taxonomy.
	 * @return void
	 */
	public function render_fields( WP_Term $term, string $taxonomy ): void {
		foreach ( self::get_fields( $taxonomy ) as $key => $meta ) {
			$field_id    = str_replace( '_', '-', $key );
			$description = (string) $meta['description'];
			$pattern     = $meta['show_in_rest']['schema']['pattern'] ?? null;
			$control     = Markup::set_attributes(
				'<input>',
				array(
					'type'             => 'text',
					'id'               => $field_id,
					'name'             => $key,
					'value'            => (string) get_term_meta( $term->term_id, $key, true ),
					'pattern'          => null !== $pattern ? trim( $pattern, '^$' ) : null,
					'aria-describedby' => '' !== $description ? "$field_id-description" : null,
				)
			);
			?>
			<tr class="form-field">
				<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $meta['label'] ); ?></label></th>
				<td>
					<?php echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?>
					<?php if ( '' !== $description ) : ?>
						<p class="description" id="<?php echo esc_attr( "$field_id-description" ); ?>"><?php echo esc_html( $description ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Save the term meta sent by the screen that edits a term, after core checked its nonce, which is checked again here:
	 * a term edited elsewhere, such as through the REST API, saves its meta itself. An empty value removes the meta, a
	 * value its schema refuses leaves it as it was.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_fields( int $term_id ): void {
		$nonce = sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce, 'update-tag_' . $term_id ) ) {
			return;
		}

		$term = get_term( $term_id );

		if ( ! $term instanceof WP_Term ) {
			return;
		}

		foreach ( self::get_fields( $term->taxonomy ) as $key => $meta ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$value = sanitize_meta( $key, wp_unslash( $_POST[ $key ] ), 'term', $term->taxonomy ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by sanitize_meta(), with the callback of its registration.

			if ( '' === $value ) {
				delete_term_meta( $term_id, $key );
				continue;
			}

			if ( true === rest_validate_value_from_schema( $value, array( 'type' => $meta['type'] ) + ( $meta['show_in_rest']['schema'] ?? array() ), $key ) ) {
				update_term_meta( $term_id, $key, wp_slash( $value ) );
			}
		}
	}
}
