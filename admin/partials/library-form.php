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
 * @var array             $values Values of the book, by meta key; the readings as a list.
 * @var string            $series Series of the book, after its cycles, or empty.
 * @var string[]          $paths  Every series, after its cycles, to suggest.
 * @var WP_Term[]         $names  Names of the book, authors or with another role.
 * @var array<int,string> $roles  Roles of the names with another role than author, by term ID.
 * @var array<int,string> $languages Languages of the library, by term ID, in plain text, which its edition languages are among.
 */

use Library\Markup;
use Library\Meta;

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
	foreach ( array( 'library_volume_number', 'library_collection_number', 'library_printed_date', 'library_subtitle', 'library_published_date', 'library_edition_date', 'library_pages' ) as $key ) {
		include __DIR__ . '/library-form-field.php';
	}
	?>

	<!-- Edition languages, beside the original languages of the box of the taxonomy -->
	<tr>
		<th scope="row"><?php echo esc_html( Meta::get_label( 'library_edition_languages' ) ); ?></th>
		<td>
			<fieldset aria-describedby="library-edition-languages-description">
				<legend class="screen-reader-text"><?php echo esc_html( Meta::get_label( 'library_edition_languages' ) ); ?></legend>
				<?php foreach ( $languages as $term_id => $language ) : ?>
					<label>
						<input type="checkbox" name="library_edition_languages[]" value="<?php echo esc_attr( $term_id ); ?>" <?php checked( in_array( $term_id, $values['library_edition_languages'], true ) ); ?>>
						<?php echo esc_html( $language ); ?>
					</label>
					<br>
				<?php endforeach; ?>
			</fieldset>
			<p class="description" id="library-edition-languages-description"><?php echo esc_html( Meta::get_description( 'library_edition_languages' ) ); ?></p>
		</td>
	</tr>

	<?php
	foreach ( array( 'library_collective', 'library_read' ) as $key ) {
		include __DIR__ . '/library-form-field.php';
	}

	// The reading in progress, since its start: today when it starts.
	$in_progress = array_find( $values['library_reading'], fn( string $reading ) => Meta::IN_PROGRESS === Meta::split_reading( $reading )[1] );
	$started     = null !== $in_progress ? substr( Meta::split_reading( $in_progress )[0], 0, 10 ) : current_time( 'Y-m-d' );
	?>

	<!-- In progress -->
	<tr>
		<th scope="row">
			<label for="library-in-progress"><?php echo esc_html( Meta::get_label( 'library_in_progress' ) ); ?></label>
		</th>
		<td>
			<input type="hidden" name="library_in_progress" value="0">
			<input id="library-in-progress" type="checkbox" name="library_in_progress" value="1" <?php checked( null !== $in_progress ); ?> aria-describedby="library-in-progress-description">
			<label>
				<?php esc_html_e( 'Started', 'library' ); ?>
				<input type="date" name="library_started" max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( $started ); ?>">
			</label>
			<p class="description" id="library-in-progress-description"><?php echo esc_html( Meta::get_description( 'library_in_progress' ) ); ?></p>
		</td>
	</tr>

	<!-- Readings -->
	<tr>
		<th scope="row">
			<?php echo esc_html( Meta::get_label( 'library_reading' ) ); ?>
		</th>
		<td>
			<fieldset class="library-readings" aria-describedby="library-reading-description">
				<legend class="screen-reader-text"><?php echo esc_html( Meta::get_label( 'library_reading' ) ); ?></legend>
				<ul>
					<?php foreach ( $values['library_reading'] as $reading ) : ?>
						<?php if ( $reading !== $in_progress ) : ?>
							<?php include __DIR__ . '/library-reading.php'; ?>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
				<button class="button" type="button" data-action="add"><?php esc_html_e( 'Add a reading', 'library' ); ?></button>
				<template>
					<?php
					// A reading added ends today, unless changed.
					$reading = '/' . current_time( 'Y-m-d' );

					require __DIR__ . '/library-reading.php';
					?>
				</template>
			</fieldset>
			<p class="description" id="library-reading-description">
				<?php esc_html_e( 'Optional. One line per reading, from its start to its end, each left empty when it is unknown. Ending a reading marks the book as read; marking it as read without a reading ends one today.', 'library' ); ?>
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
						$select = Markup::get_select( Meta::get_role_choices(), $roles[ $name->term_id ] ?? 'author' );
						$select = Markup::set_attributes(
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
