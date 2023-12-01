/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * Sellix data comes form the server passed on a global object.
 */
export const getSellixServerData = () => {
	const sellixServerData = getSetting( 'sellix_data', null );
	if ( ! sellixServerData || typeof sellixServerData !== 'object' ) {
		throw new Error( 'Sellix initialization data is not available' );
	}
	return sellixServerData;
};