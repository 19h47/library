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
				placeholder="<?php esc_html_e( 'Series', 'library' ); ?>"
				value="<?php echo esc_html( $series ); ?>"
			>
			<p class="description"><?php esc_html_e( 'Separate series with commas', 'library' ); ?></p>
		</td>
	</tr>

	<!-- Authors -->
	<tr>
		<th scope="row">
			<label for="library-authors">
				<?php esc_html_e( 'Authors', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="text"
				id="library-authors"
				name="authors"
				class="regular-text"
				placeholder="<?php esc_html_e( 'Authors', 'library' ); ?>"
				value="<?php echo esc_html( $authors ); ?>"
			>
			<p class="description"><?php esc_html_e( 'Separate authors with commas', 'library' ); ?></p>
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
				placeholder="<?php esc_html_e( 'ISBN', 'library' ); ?>"
				value="<?php echo esc_html( $isbn ); ?>"
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
				placeholder="<?php esc_html_e( 'ISSN', 'library' ); ?>"
				value="<?php echo esc_html( $issn ); ?>"
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
				placeholder="<?php esc_html_e( 'Volume Number', 'library' ); ?>"
				value="<?php echo esc_html( $volume_number ); ?>"
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
				placeholder="<?php esc_html_e( 'Date Published', 'library' ); ?>"
				value="<?php echo esc_html( $date_published ); ?>"
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
				placeholder="<?php esc_html_e( 'Read date', 'library' ); ?>"
				value="<?php echo esc_html( $read_date ); ?>"
			>
			<p class="description">
				<?php esc_html_e( 'Leave empty if the book has not been read. Checking “Read” in the books list sets it to today.', 'library' ); ?>
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
				placeholder="1–10"
				value="<?php echo esc_attr( $rating ); ?>"
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
				placeholder="<?php esc_html_e( 'Translators', 'library' ); ?>"
				value="<?php echo esc_html( $translators ); ?>"
			>
			<p class="description"><?php esc_html_e( 'Separate translators with commas', 'library' ); ?></p>
		</td>
	</tr>

	<!-- Publishers -->
	<tr>
		<th scope="row">
			<label for="library-publishers">
				<?php esc_html_e( 'Publishers', 'library' ); ?>
			</label>
		</th>
		<td>
			<input
				type="text"
				id="library-publishers"
				name="publishers"
				class="regular-text"
				placeholder="<?php esc_html_e( 'Publishers', 'library' ); ?>"
				value="<?php echo esc_html( $publishers ); ?>"
			>
			<p class="description"><?php esc_html_e( 'Separate publishers with commas', 'library' ); ?></p>
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
				placeholder="<?php esc_html_e( 'Book Editions', 'library' ); ?>"
				value="<?php echo esc_html( $book_editions ); ?>"
			>
			<p class="description"><?php esc_html_e( 'Separate book editions with commas', 'library' ); ?></p>
		</td>
	</tr>
</table>
