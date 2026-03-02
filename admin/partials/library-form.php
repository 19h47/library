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
			<button type="button" id="library-fetch-isbn" class="button button-secondary" style="margin-left: 6px;">
				<?php esc_html_e( 'Fetch info', 'library' ); ?>
			</button>
			<span id="library-fetch-isbn-status" class="library-fetch-status" aria-live="polite"></span>
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
