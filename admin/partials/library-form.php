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
 * @var string            $series Series of the book, after its cycles, or empty.
 * @var string[]          $paths  Every series, after its cycles, to suggest.
 * @var WP_Term[]         $names  Names of the book, authors or with another role.
 * @var array<int,string> $roles  Roles of the names with another role than author, by term ID.
 */

?>

<table class="form-table" role="presentation">

	<?php
	// As the form of the reading app: the fields most books have, then the ones few have, then the reading.
	$key = 'library_isbn';

	require __DIR__ . '/library-form-field.php';
	?>

	<!-- Series -->
	<tr>
		<th scope="row">
			<label for="library-series"><?php echo esc_html( get_taxonomy( 'library_series' )->labels->singular_name ); ?></label>
		</th>
		<td>
			<input id="library-series" class="regular-text" type="text" name="series" value="<?php echo esc_attr( $series ); ?>" list="library-series-paths" autocomplete="off" aria-describedby="library-series-description">
			<datalist id="library-series-paths">
				<?php foreach ( $paths as $series_path ) : ?>
					<option value="<?php echo esc_attr( $series_path ); ?>"></option>
				<?php endforeach; ?>
			</datalist>
			<p class="description" id="library-series-description"><?php echo esc_html( get_taxonomy( 'library_series' )->description ); ?></p>
		</td>
	</tr>

	<?php
	foreach ( array( 'library_volume_number', 'library_collection_number', 'library_edition_date', 'library_subtitle', 'library_published_date', 'library_pages', 'library_collective', 'library_issn', 'library_read', 'library_in_progress' ) as $key ) {
		include __DIR__ . '/library-form-field.php';
	}
	?>

	<!-- Read Dates -->
	<tr>
		<th scope="row">
			<?php echo esc_html( Library_Meta::get_label( 'library_read_date' ) ); ?>
		</th>
		<td>
			<fieldset class="library-read-dates" aria-describedby="library-read-date-description">
				<legend class="screen-reader-text"><?php echo esc_html( Library_Meta::get_label( 'library_read_date' ) ); ?></legend>
				<ul>
					<?php foreach ( $values['library_read_date'] as $read_date ) : ?>
						<?php include __DIR__ . '/library-read-date.php'; ?>
					<?php endforeach; ?>
				</ul>
				<button class="button" type="button" data-action="add"><?php esc_html_e( 'Add a reading', 'library' ); ?></button>
				<template>
					<?php
					// A reading added is today, unless changed.
					$read_date = current_time( 'Y-m-d' );

					require __DIR__ . '/library-read-date.php';
					?>
				</template>
			</fieldset>
			<p class="description" id="library-read-date-description">
				<?php esc_html_e( 'Optional. One date per reading, left empty when it is unknown. Setting a date marks the book as read; marking it as read without a date sets today.', 'library' ); ?>
			</p>
		</td>
	</tr>

	<?php
	$key = 'library_rating';

	require __DIR__ . '/library-form-field.php';
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
