<?php
/**
 * Book information metabox form fields.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin/partials
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */

?>

<table class="form-table" role="presentation">

	<!-- Series -->
	<tr>
		<th scope="row">
			<label for="library-series">
				<?php esc_html_e( 'Series', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="text"
				id="library-series"
				name="series"
				class="regular-text"
				value="<?php echo esc_attr( $values['series'] ); ?>"
			>
			<p class="description"><?php esc_html_e( 'Separate series with commas', 'library' ); ?></p>
		</td>
	</tr>

	<!-- ISBN -->
	<tr>
		<th>
			<label for="library-isbn">
				<?php esc_html_e( 'ISBN', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="text"
				pattern="(?:(?=.{17}$)(97[89]|501)[ -](?:[0-9]+[ -]){2}[0-9]+[ -][0-9]|97[89][0-9]{10}|(?=.{13}$)(?:[0-9]+[ -]){2}[0-9]+[ -][0-9Xx]|[0-9]{9}[0-9Xx])"
				id="library-isbn"
				name="isbn"
				class="regular-text"
				value="<?php echo esc_attr( $values['isbn'] ); ?>"
			>
		</td>
	</tr>

	<!-- ISSN -->
	<tr>
		<th>
			<label for="library-issn">
				<?php esc_html_e( 'ISSN', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="text"
				pattern="[\S]{4}\-[\S]{4}"
				id="library-issn"
				name="issn"
				class="regular-text"
				value="<?php echo esc_attr( $values['issn'] ); ?>"
			>
		</td>
	</tr>

	<!-- Volume Number -->
	<tr>
		<th>
			<label for="library-volume-number">
				<?php esc_html_e( 'Volume Number', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="text"
				id="library-volume-number"
				name="volume_number"
				class="regular-text"
				value="<?php echo esc_attr( $values['volume_number'] ); ?>"
			>
		</td>
	</tr>

	<!-- Date Published -->
	<tr>
		<th>
			<label for="library-date-published">
				<?php esc_html_e( 'Date Published', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="date"
				id="library-date-published"
				name="date_published"
				class="regular-text"
				value="<?php echo esc_attr( $values['date_published'] ); ?>"
			>
		</td>
	</tr>

	<!-- Read -->
	<tr>
		<th>
			<label for="library-read">
				<?php esc_html_e( 'Read', 'library' ); ?>
			</label>
		</th>
		<td>
			<input type="hidden" name="read" value="0">
			<input
				type="checkbox"
				id="library-read"
				name="read"
				value="1"
				<?php checked( (bool) $values['read'] ); ?>
			>
		</td>
	</tr>

	<!-- Read Date -->
	<tr>
		<th>
			<label for="library-read-date">
				<?php esc_html_e( 'Read date', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="date"
				id="library-read-date"
				name="read_date"
				class="regular-text"
				value="<?php echo esc_attr( $values['read_date'] ); ?>"
			>
			<p class="description">
				<?php esc_html_e( 'Optional. Setting a date marks the book as read; marking it as read without a date sets today.', 'library' ); ?>
			</p>
		</td>
	</tr>

	<!-- Rating -->
	<tr>
		<th>
			<label for="library-rating">
				<?php esc_html_e( 'Rating', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="number"
				id="library-rating"
				name="rating"
				class="small-text"
				min="1"
				max="10"
				step="0.5"
				value="<?php echo esc_attr( $values['rating'] ); ?>"
			>
			<p class="description"><?php esc_html_e( 'Rating from 1 to 10.', 'library' ); ?></p>
		</td>
	</tr>

	<!-- Translators -->
	<tr>
		<th scope="row">
			<label for="library-translators">
				<?php esc_html_e( 'Translators', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="text"
				id="library-translators"
				name="translators"
				class="regular-text"
				value="<?php echo esc_attr( $values['translators'] ); ?>"
			>
			<p class="description"><?php esc_html_e( 'Separate translators with commas', 'library' ); ?></p>
		</td>
	</tr>

	<!-- Book Editions -->
	<tr>
		<th scope="row">
			<label for="library-book-editions">
				<?php esc_html_e( 'Book Editions', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="text"
				id="library-book-editions"
				name="book_editions"
				class="regular-text"
				value="<?php echo esc_attr( $values['book_editions'] ); ?>"
			>
			<p class="description"><?php esc_html_e( 'Separate book editions with commas', 'library' ); ?></p>
		</td>
	</tr>
</table>
