/**
 * Service worker of the reading app: the app and what it loaded stay at hand without network, as in a bookshop.
 *
 * Served from the URL of the app, which is its scope: it controls the app only, and every request of the app, its
 * page, its scripts, the REST API and the covers. A request goes to the network first, and what comes back is kept;
 * without network, what was kept answers. Opening the app waits a few seconds at most for the network, then shows the
 * app kept, while the network fills the cache for the next time. Only reading goes through it: a book saved needs
 * the network.
 *
 * The app itself, its page, scripts and styles, is kept apart from what it loads, the REST API and the covers: the
 * covers of a long scroll never push the app out, so it opens without network.
 */

// What is kept, and how many responses each cache keeps at most, the oldest dropped beyond, each time the app opens:
// the app as many versions of its files as a few releases bring, what it loads as many as the searches typed add.
const CACHES = {
	'library-app-shell': 200,
	'library-app-data': 500,
};
// The requests of the app itself, by what they are for.
const SHELL_DESTINATIONS = [
	'document',
	'script',
	'style',
	'font',
	'manifest',
];
// How long opening the app waits for the network before showing the app kept.
const NAVIGATION_TIMEOUT_MS = 4000;

self.addEventListener( 'install', () => self.skipWaiting() );

// The caches of an earlier version of the worker go.
self.addEventListener( 'activate', ( event ) => {
	const removeOthers = async () => {
		const names = await caches.keys();

		await Promise.all(
			names
				.filter( ( name ) => ! ( name in CACHES ) )
				.map( ( name ) => caches.delete( name ) )
		);
		await self.clients.claim();
	};

	event.waitUntil( removeOthers() );
} );

self.addEventListener( 'fetch', ( event ) => {
	const { request } = event;

	if (
		'GET' === request.method &&
		new URL( request.url ).origin === self.location.origin
	) {
		event.respondWith( respond( event ) );
	}
} );

/**
 * The response of the network, kept, or else the one kept; a view of the app not kept yet takes the app kept.
 *
 * @param {FetchEvent} event The request of the app.
 * @return {Promise<Response>} Its response.
 */
async function respond( event ) {
	const { request } = event;
	const isNavigation = 'navigate' === request.mode;
	const name = SHELL_DESTINATIONS.includes( request.destination )
		? 'library-app-shell'
		: 'library-app-data';
	const cache = await caches.open( name );
	const network = fetch( request );
	// Kept before the page reads it: its clone comes first. A redirect, such as to the login, is not the app.
	const keeping = network.then(
		( response ) =>
			response.ok &&
			! response.redirected &&
			cache.put( request, response.clone() )
	);
	const kept = async () =>
		( await caches.match( request ) ) ??
		( isNavigation ? caches.match( self.registration.scope ) : undefined );

	event.waitUntil( keeping.catch( () => {} ) );

	if ( isNavigation ) {
		event.waitUntil( trim() );
	}

	try {
		if ( ! isNavigation ) {
			return await network;
		}

		const late = new Promise( ( resolve ) =>
			setTimeout( resolve, NAVIGATION_TIMEOUT_MS )
		).then( async () => ( await kept() ) ?? network );

		return await Promise.race( [ network, late ] );
	} catch {
		return ( await kept() ) ?? Response.error();
	}
}

/** Drops the oldest responses of each cache beyond the most it keeps. */
async function trim() {
	await Promise.all(
		Object.entries( CACHES ).map( async ( [ name, max ] ) => {
			const cache = await caches.open( name );
			const keys = await cache.keys();

			await Promise.all(
				keys
					.slice( 0, Math.max( 0, keys.length - max ) )
					.map( ( key ) => cache.delete( key ) )
			);
		} )
	);
}
