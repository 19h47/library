<?php
/**
 * The reference of the API, for development, opened by searching "api" then Enter: every route of the plugin, then the
 * routes of core the app reads, as the API describes itself, each with its endpoints, their arguments, what it gives when
 * its schema says it, and a GET request to try. library-api fills it, from the templates below.
 *
 * @link       https://github.com/19h47/library
 * @since      9.36.0
 *
 * @package    Library
 * @subpackage Library/app/partials
 */

?>
<library-api class="library-sections">
	<header class="library-api__header">
		<h2 class="library-title"><?php esc_html_e( 'API', 'library' ); ?></h2>
		<p class="library-api__intro">
			<?php
			printf(
				/* translators: 1: namespace of the plugin, library/v1, 2: namespace of core, wp/v2. */
				esc_html__( 'The routes of %1$s, then those of %2$s the app reads, as the REST API describes itself: what they take, and what they give when their schema says it. A GET request can be tried, as the user logged in.', 'library' ),
				'<code>library/v1</code>',
				'<code>wp/v2</code>'
			);
			?>
		</p>
	</header>
	<p class="app-status" data-field="status" hidden></p>
	<div class="library-api__routes" data-list="routes"></div>

	<template id="library-api-route">
		<details class="library-api__route">
			<summary>
				<span class="app-text-s app-text-muted app-font-ui" data-field="methods"></span>
				<code class="library-api__path" data-field="path"></code>
			</summary>
			<div class="library-api__body">
				<div class="library-api__endpoints" data-list="endpoints"></div>
				<section class="library-api__part" data-row="properties" hidden>
					<h4 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Response', 'library' ); ?></h4>
					<ul class="library-api__list" data-list="properties"></ul>
				</section>
			</div>
		</details>
	</template>

	<template id="library-api-endpoint">
		<section class="library-api__part">
			<?php // Its methods, when its route has several endpoints: the summary of the route says them otherwise. ?>
			<h4 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" data-field="methods" hidden></h4>
			<ul class="library-api__list" data-list="args"></ul>
			<p class="app-text-s app-text-muted app-font-ui" data-row="none" hidden><?php esc_html_e( 'No argument.', 'library' ); ?></p>
			<form class="library-api__try" data-row="try" hidden>
				<div class="library-api__fields" data-list="fields"></div>
				<p><button class="app-pill" type="submit"><?php esc_html_e( 'Try', 'library' ); ?></button></p>
				<div class="library-api__answer" data-row="answer" hidden>
					<p class="app-text-s app-text-muted app-font-ui" role="status" data-field="summary"></p>
					<?php // Scrolled sideways with the keyboard too. ?>
					<pre tabindex="0" data-field="body"></pre>
				</div>
			</form>
		</section>
	</template>

	<template id="library-api-item">
		<li class="library-api__item">
			<p>
				<code data-field="name"></code>
				<span class="app-text-s app-text-muted app-font-ui" data-field="type"></span>
				<span class="app-text-s app-font-ui library-api__flag" data-field="flag"></span>
			</p>
			<p class="app-text-s app-text-muted app-font-ui" data-field="description"></p>
			<p class="app-text-s app-text-muted app-font-ui" data-field="rules"></p>
			<ul class="library-api__list" data-list="items" hidden></ul>
		</li>
	</template>

	<template id="library-api-field">
		<p class="library-field">
			<?php // The name of an argument as the API takes it, in its case. ?>
			<label class="app-text-s app-text-muted app-font-ui"></label>
			<input type="text" autocomplete="off" spellcheck="false">
		</p>
	</template>
</library-api>
