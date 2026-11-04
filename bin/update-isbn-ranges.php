<?php
/**
 * Update the ranges of the ISBN, includes/isbn-ranges.json, from the range message of the International ISBN Agency.
 *
 * Run by PHP alone, before a release: php bin/update-isbn-ranges.php. The agency adds ranges as it gives prefixes
 * to new publishers: an ISBN of a range missing from the file is kept as typed.
 *
 * The file holds the date of the message, then the ranges of each prefix, one prefix per line: the EAN prefixes, such
 * as 978, whose ranges give the length of the group, then the groups, such as 978-2, whose ranges give the length
 * of the registrant. A range is its first and last values of the seven digits that follow the prefix, and that length.
 *
 * The agency dates its message as it sends it: the file is written again only when the ranges change, with the date
 * of the message that changed them.
 *
 * @link       https://www.isbn-international.org/range_file_generation
 * @since      6.14.0
 *
 * @package    Library
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.Security.EscapeOutput -- Run by PHP alone, outside WordPress.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- The elements of the message keep their names.

$file    = dirname( __DIR__ ) . '/includes/isbn-ranges.json';
$message = simplexml_load_string( file_get_contents( 'https://www.isbn-international.org/export_rangemessage.xml' ) );

if ( ! $message ) {
	fwrite( STDERR, "The range message could not be read.\n" );
	exit( 1 );
}

$ranges = array();

foreach ( $message->xpath( '/ISBNRangeMessage/EAN.UCCPrefixes/EAN.UCC | /ISBNRangeMessage/RegistrationGroups/Group' ) as $prefix ) {
	$ranges[ (string) $prefix->Prefix ] = array();

	foreach ( $prefix->Rules->Rule as $rule ) {
		$length = (int) $rule->Length;

		// A length of 0 is a range not given yet.
		if ( $length ) {
			$ranges[ (string) $prefix->Prefix ][] = array( ...array_map( 'intval', explode( '-', (string) $rule->Range ) ), $length );
		}
	}
}

$saved = is_file( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;

if ( ( $saved['ranges'] ?? null ) === $ranges ) {
	echo count( $ranges ) . ' prefixes, the same as from the message of ' . $saved['date'] . "\n";
	exit;
}

$lines = array_map( fn( string $prefix ) => json_encode( $prefix ) . ':' . json_encode( $ranges[ $prefix ] ), array_keys( $ranges ) );

file_put_contents( $file, "{\n\"date\":" . json_encode( (string) $message->MessageDate ) . ",\n\"ranges\":{\n" . implode( ",\n", $lines ) . "\n}\n}\n" );

echo count( $ranges ) . ' prefixes, from the message of ' . $message->MessageDate . "\n";
