<?php
/**
 * Roles of the readers of the library, and the capabilities of the books.
 *
 * @link       https://github.com/19h47/library
 * @since      10.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * Gives each reader their shelves, without the right to change the books of others: a reader, Reader, shelves the books
 * and adds the ones the catalogue lacks; a librarian, Librarian, refines every book and every name of the catalogue too;
 * the editors and the administrators delete them besides. The books have capabilities of their own, `library_book`,
 * which the roles of WordPress do not give, the editor and the administrator but.
 *
 * @since      10.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Roles {

	/**
	 * Option holding the capabilities given to each role, as last given: the ones the roles lose when ROLES changes.
	 *
	 * @var string
	 */
	public const string OPTION = 'library_roles';

	/**
	 * Capability of shelving the books: the readings, their ratings, the read state.
	 *
	 * @var string
	 */
	public const string SHELVE = 'shelve_library_books';

	/**
	 * Capabilities of WordPress the roles of the plugin have, given as they are added: open the admin, upload a cover.
	 *
	 * @var array<string,bool>
	 */
	private const array BASE = array(
		'read'         => true,
		'upload_files' => true,
	);

	/**
	 * Capabilities of a reader: shelve the books, add the ones the catalogue lacks and change them.
	 *
	 * @var list<string>
	 */
	private const array READER = array( self::SHELVE, 'edit_library_books', 'edit_published_library_books', 'publish_library_books' );

	/**
	 * Capabilities of a librarian besides: change the books of others and the names of the catalogue.
	 *
	 * @var list<string>
	 */
	private const array LIBRARIAN = array( 'edit_others_library_books', 'manage_library_terms' );

	/**
	 * Capabilities of the editors and the administrators besides: delete the books.
	 *
	 * @var list<string>
	 */
	private const array EDITOR = array( 'delete_library_books', 'delete_others_library_books', 'delete_published_library_books', 'delete_private_library_books', 'edit_private_library_books', 'read_private_library_books' );

	/**
	 * Capabilities each role has from the plugin, its own roles and those of WordPress, which the plugin gives and takes
	 * back: never one of WordPress, which a role may have of its own.
	 *
	 * @var array<string,list<string>>
	 */
	private const array ROLES = array(
		'library_reader'    => self::READER,
		'library_librarian' => array( ...self::READER, ...self::LIBRARIAN ),
		'editor'            => array( ...self::READER, ...self::LIBRARIAN, ...self::EDITOR ),
		'administrator'     => array( ...self::READER, ...self::LIBRARIAN, ...self::EDITOR ),
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'give_capabilities' ) );
		add_filter( 'map_meta_cap', array( $this, 'map_shelve_book' ), 10, 4 );
		add_filter( 'gettext_with_context_default', array( $this, 'translate_role_name' ), 10, 3 );
	}

	/**
	 * Names of the roles of the plugin, as WordPress stores them, untranslated, by role.
	 *
	 * @return array<string,string>
	 */
	private static function get_names(): array {
		return array(
			'library_reader'    => 'Reader',
			'library_librarian' => 'Librarian',
		);
	}

	/**
	 * Give the roles their capabilities, once ROLES changes: WordPress keeps the roles in the database. The capabilities
	 * a role no longer has from the plugin are taken back, its other ones kept.
	 *
	 * @return void
	 */
	public function give_capabilities(): void {
		$given = get_option( self::OPTION, array() );

		if ( self::ROLES === $given ) {
			return;
		}

		$given = is_array( $given ) ? $given : array();

		foreach ( self::get_names() as $role => $name ) {
			if ( ! get_role( $role ) ) {
				add_role( $role, $name, self::BASE );
			}
		}

		foreach ( self::ROLES as $role => $capabilities ) {
			$role = get_role( $role );

			if ( ! $role ) {
				continue;
			}

			foreach ( array_diff( $given[ $role->name ] ?? array(), $capabilities ) as $capability ) {
				$role->remove_cap( $capability );
			}

			foreach ( $capabilities as $capability ) {
				$role->add_cap( $capability );
			}
		}

		update_option( self::OPTION, self::ROLES, true );
	}

	/**
	 * Shelving a book, `shelve_book` with its ID, takes the capability of shelving the books, for a book of the
	 * catalogue: neither in the trash nor a draft never saved.
	 *
	 * @param string[] $caps    Primitive capabilities the user needs.
	 * @param string   $cap     Capability checked.
	 * @param int      $user_id User ID.
	 * @param mixed[]  $args    Arguments of the check, the book ID first.
	 * @return string[]
	 */
	public function map_shelve_book( array $caps, string $cap, int $user_id, array $args ): array {
		if ( 'shelve_book' !== $cap ) {
			return $caps;
		}

		$book_id = (int) ( $args[0] ?? 0 );

		if ( 'library_book' !== get_post_type( $book_id ) || ! in_array( get_post_status( $book_id ), Status::get_values(), true ) ) {
			return array( 'do_not_allow' );
		}

		return array( self::SHELVE );
	}

	/**
	 * Translate the names of the roles of the plugin, which WordPress translates with its own strings, as a user role.
	 *
	 * @param string $translation Translation.
	 * @param string $text        Text to translate.
	 * @param string $context     Context.
	 * @return string
	 */
	public function translate_role_name( string $translation, string $text, string $context ): string {
		if ( 'User role' !== $context ) {
			return $translation;
		}

		return match ( $text ) {
			'Reader'    => _x( 'Reader', 'User role', 'library' ),
			'Librarian' => _x( 'Librarian', 'User role', 'library' ),
			default     => $translation,
		};
	}
}
