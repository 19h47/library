/**
 * A progress bar, as the styleguide shows it: its count says how far it is, its bar says it in words, named by what its
 * place points it to; its count follows a value changed.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { openApp } from './library.js';

test.describe( 'Progress', () => {
	test( 'a progress bar counts how far it is, and its bar says it in words, under its name', async ( {
		page,
	} ) => {
		await openApp( page, '&view=styleguide' );

		const sample = page.locator( '[data-sample="progress-bar"]' );
		const bar = sample.getByRole( 'progressbar', {
			name: 'Read the books of a series',
		} );

		await expect( sample.locator( '[data-part="count"]' ) ).toHaveText(
			'9 / 14'
		);
		await expect( bar ).toHaveAttribute( 'aria-valuetext', '9 of 14' );

		await sample
			.locator( 'progress-bar' )
			.evaluate( ( $bar ) => ( $bar.value = 10 ) );

		await expect( sample.locator( '[data-part="count"]' ) ).toHaveText(
			'10 / 14'
		);
		await expect( bar ).toHaveAttribute( 'aria-valuetext', '10 of 14' );
	} );
} );
