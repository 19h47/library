<?php
/**
 * Book information metabox form fields, as the book meta are registered.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin/partials
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 *
 * @var array             $values Values of the book, by meta key; the read dates as a list.
 * @var WP_Term[]         $names  Names of the book, authors or with another role.
 * @var array<int,string> $roles  Roles of the names with another role than author, by term ID.
 */

?>

<table class="form-table" role="presentation">

	<?php
	foreach ( array( 'library_subtitle', 'library_series', 'library_isbn', 'library_issn', 'library_volume_number', 'library_collection_number', 'library_pages', 'library_edition_date', 'library_published_date', 'library_read' ) as $key ) {
		include __DIR__ . '/library-form-field.php';
	}
	?>

	<!-- Read Dates -->
	<tr>
		<th scope="row">
			<?php echo esc_html( Library_Meta::get_label( 'library_read_date' ) ); ?>
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text"><?php echo esc_html( Library_Meta::get_label( 'library_read_date' ) ); ?></legend>
				<?php foreach ( array( ...$values['library_read_date'], '' ) as $index => $read_date ) : ?>
					<label for="library-read-date-<?php echo esc_attr( $index ); ?>" class="screen-reader-text">
						<?php
						/* translators: %d: number of the reading, e.g. 2 for the second one. */
						echo esc_html( '' !== $read_date ? sprintf( __( 'Reading %d', 'library' ), $index + 1 ) : __( 'New reading', 'library' ) );
						?>
					</label>
					<?php
					$control = Library_Meta::get_control(
						'library_read_date',
						array(
							'id'               => "library-read-date-$index",
							'name'             => 'library_read_date[]',
							'class'            => 'regular-text',
							'aria-describedby' => 'library-read-date-description',
							'value'            => $read_date,
						)
					);

					echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
					?>
					<br>
				<?php endforeach; ?>
			</fieldset>
			<p class="description" id="library-read-date-description">
				<?php esc_html_e( 'Optional. One date per reading: fill in the empty one to add a reading, empty a date to remove it. Setting a date marks the book as read; marking it as read without a date sets today.', 'library' ); ?>
			</p>
		</td>
	</tr>

	<?php
	foreach ( array( 'library_rating', 'library_collective' ) as $key ) {
		include __DIR__ . '/library-form-field.php';
	}
	?>

	<?php if ( $names ) : ?>
		<!-- Roles -->
		<tr>
			<th scope="row"><?php esc_html_e( 'Roles', 'library' ); ?></th>
			<td>
				<fieldset aria-describedby="library-roles-description">
					<legend class="screen-reader-text"><?php esc_html_e( 'Roles', 'library' ); ?></legend>
					<?php foreach ( $names as $name ) : ?>
						<label for="library-role-<?php echo esc_attr( $name->term_id ); ?>"><?php echo esc_html( $name->name ); ?></label>
						<?php
						$select = Library::get_select( Library_Meta::get_role_choices(), $roles[ $name->term_id ] ?? 'author' );
						$select = Library::set_attributes(
							$select,
							array(
								'id'   => "library-role-{$name->term_id}",
								'name' => "library_roles[{$name->term_id}]",
							)
						);

						echo $select; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
						?>
						<br>
					<?php endforeach; ?>
				</fieldset>
				<p class="description" id="library-roles-description">
					<?php esc_html_e( 'The role of each name of the book: a colorist, for instance, is not one of its authors. A name just added is an author: save the book to choose another role.', 'library' ); ?>
				</p>
			</td>
		</tr>
	<?php endif; ?>
</table>
