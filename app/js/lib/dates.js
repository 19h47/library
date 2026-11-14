/**
 * Dates with nothing of the app, which bricks and components share: it imports nothing.
 */

/**
 * Today on the device, as a read date: Y-m-d.
 *
 * @return {string} Today.
 */
export function today() {
	const now = new Date();

	return [ now.getFullYear(), now.getMonth() + 1, now.getDate() ]
		.map( ( part ) => part.toString().padStart( 2, '0' ) )
		.join( '-' );
}
