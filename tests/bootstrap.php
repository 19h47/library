<?php
/**
 * Bootstrap of the tests: the test suite of WordPress, on the database of tests/wp-tests-config.php, with the plugin
 * loaded as a must-use plugin.
 *
 * @package Library
 */

// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- The suite reads its configuration from the environment.
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

require dirname( __DIR__ ) . '/vendor/autoload.php';
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', fn() => require dirname( __DIR__ ) . '/library.php' );

require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
require __DIR__ . '/class-library-testcase.php';
