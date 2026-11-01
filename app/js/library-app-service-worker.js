/**
 * Service worker of the reading app: the app and what it loaded stay at hand without network, as in a bookshop.
 *
 * Served from the URL of the app, which is its scope: it controls the app only, and every request of the app, its
 * page, its scripts, the REST API and the covers. A request goes to the network first, and what comes back is kept;
 * without network, what was kept answers. Opening the app waits a few seconds at most for the network, then shows the
 * app kept, while the network fills the cache for the next time. Only reading goes through it: a book saved needs
 * the network.
 *
 * @package    Library
 * @subpackage Library/app
 */

const CACHE = 'library-app';
// The responses kept, the oldest dropped beyond: the searches typed add theirs.
const MAX_ENTRIES = 500;
// How long opening the app waits for the network before showing the app kept.
const NAVIGATION_TIMEOUT_MS = 4000;

self.addEventListener( 'install', () => self.skipWaiting() );
self.addEventListener( 'activate', ( event ) => event.waitUntil( self.clients.claim() ) );

self.addEventListener( 'fetch', ( event ) => {
	const { request } = event;

	if ( 'GET' === request.method && new URL( request.url ).origin === self.location.origin ) {
		event.respondWith( respond( event ) );
	}
} );

/** The response of the network, kept, or else the one kept; a view of the app not kept yet takes the app kept. */
async function respond( event ) {
	const { request } = event;
	const cache = await caches.open( CACHE );
	const network = fetch( request );
	// Kept before the page reads it: its clone comes first. A redirect, such as to the login, is not the app.
	const keeping = network.then( ( response ) => response.ok && ! response.redirected && keep( cache, request, response.clone() ) );
	const kept = async () => ( await cache.match( request ) ) ?? ( 'navigate' === request.mode ? cache.match( self.registration.scope ) : undefined );

	event.waitUntil( keeping.catch( () => {} ) );

	try {
		if ( 'navigate' !== request.mode ) {
			return await network;
		}

		const late = new Promise( ( resolve ) => setTimeout( resolve, NAVIGATION_TIMEOUT_MS ) ).then( async () => ( await kept() ) ?? network );

		return await Promise.race( [ network, late ] );
	} catch {
		return ( await kept() ) ?? Response.error();
	}
}

/** Keeps a response, and drops the oldest ones beyond the most kept. */
async function keep( cache, request, response ) {
	await cache.put( request, response );

	const keys = await cache.keys();

	await Promise.all( keys.slice( 0, Math.max( 0, keys.length - MAX_ENTRIES ) ).map( ( key ) => cache.delete( key ) ) );
}
