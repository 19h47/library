<?php
/**
 * The box of the shelf of the reader on the screen of a book: where it is for them, and their copy, its printed date,
 * their readings and their notes, as the form of their reading in the app; the record is in the Information box.
 *
 * @link       https://github.com/19h47/library
 * @since      10.0.1
 *
 * @package    Library
 * @subpackage Library/admin/partials
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 *
 * @var string $selected Status of the book for the reader, empty on none of their shelves.
 * @var array  $values   Copy of the reader, by meta key, see Shelf::get_values(): the readings as a list.
 */

use Library\Meta;
use Library\Reading;
use Library\Status;

?>

<table class="form-table" role="presentation">

	<!-- Shelf -->
	<tr>
		<th scope="row">
			<label for="library-shelf"><?php esc_html_e( 'Shelf', 'library' ); ?></label>
		</th>
		<td>
			<select id="library-shelf" name="library_shelf_status" aria-describedby="library-shelf-description">
				<option value="" <?php selected( '', $selected ); ?>><?php esc_html_e( 'On none of my shelves', 'library' ); ?></option>
				<?php foreach ( Status::get_choices() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $selected ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description" id="library-shelf-description">
				<?php esc_html_e( 'Not kept: read but not in my library. To buy: not in my library yet. The book stays in the catalogue for the other readers.', 'library' ); ?>
			</p>
		</td>
	</tr>

	<?php
	foreach ( array( 'library_printed_date', 'library_read' ) as $key ) {
		include __DIR__ . '/library-form-field.php';
	}

	// The reading in progress, since its start: today when it starts.
	$in_progress = array_find( $values['library_reading'], fn( Reading $reading ) => ! $reading->is_ended() );
	$started     = null !== $in_progress ? substr( $in_progress->start, 0, 10 ) : current_time( 'Y-m-d' );
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
			<fieldset data-part="readings" aria-describedby="library-reading-description">
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
					$reading = new Reading( end: current_time( 'Y-m-d' ) );

					require __DIR__ . '/library-reading.php';
					?>
				</template>
			</fieldset>
			<p class="description" id="library-reading-description">
				<?php esc_html_e( 'Optional. One line per reading, from its start to its end, each left empty when it is unknown, and the rating it ended with. Ending a reading marks the book as read; marking it as read without a reading ends one today. The rating of the book is the one of its latest reading rated.', 'library' ); ?>
			</p>
		</td>
	</tr>

	<!-- Notes -->
	<tr>
		<th scope="row">
			<label for="library-notes"><?php echo esc_html( Meta::get_label( 'library_notes' ) ); ?></label>
		</th>
		<td>
			<textarea id="library-notes" class="large-text" name="library_notes" rows="5"><?php echo esc_textarea( $values['library_notes'] ); ?></textarea>
		</td>
	</tr>

</table>
