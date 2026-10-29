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
 * @var array $values Values of the book, by meta key; the read dates as a list.
 */

?>

<table class="form-table" role="presentation">

	<?php
	foreach ( array( 'subtitle', 'series', 'isbn', 'issn', 'volume_number', 'pages', 'edition_date', 'published_date' ) as $key ) {
		include __DIR__ . '/library-form-field.php';
	}
	?>

	<!-- Read -->
	<tr>
		<th scope="row">
			<label for="library-read"><?php echo esc_html( Library_Meta::label( 'read' ) ); ?></label>
		</th>
		<td>
			<input type="hidden" name="read" value="0">
			<?php
			$control = Library_Meta::control(
				'read',
				array(
					'id'      => 'library-read',
					'value'   => '1',
					'checked' => (bool) $values['read'],
				)
			);

			echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
			?>
		</td>
	</tr>

	<!-- Read Dates -->
	<tr>
		<th scope="row">
			<?php echo esc_html( Library_Meta::label( 'read_date' ) ); ?>
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text"><?php echo esc_html( Library_Meta::label( 'read_date' ) ); ?></legend>
				<?php foreach ( array( ...$values['read_date'], '' ) as $index => $read_date ) : ?>
					<label for="library-read-date-<?php echo esc_attr( $index ); ?>" class="screen-reader-text">
						<?php
						/* translators: %d: number of the reading, e.g. 2 for the second one. */
						echo esc_html( '' !== $read_date ? sprintf( __( 'Reading %d', 'library' ), $index + 1 ) : __( 'New reading', 'library' ) );
						?>
					</label>
					<?php
					$control = Library_Meta::control(
						'read_date',
						array(
							'id'               => "library-read-date-$index",
							'name'             => 'read_date[]',
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
	foreach ( array( 'rating', 'translators' ) as $key ) {
		include __DIR__ . '/library-form-field.php';
	}
	?>
</table>
