/**
 * The theme of the reading app before the first paint: the one chosen over the system one, which the store keeps, as
 * <html data-theme>; else the styles follow the system. The store, a module run once the page is read, takes it over.
 */
( () => {
	try {
		const { theme, themeChosen } =
			JSON.parse( localStorage.getItem( 'library-app' ) ) ?? {};

		if ( true === themeChosen && [ 'light', 'dark' ].includes( theme ) ) {
			document.documentElement.dataset.theme = theme;
		}
	} catch {}
} )();
