import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

import { getSellixServerData } from './utils';

const ContentComponent = ( { emitResponse, eventRegistration } ) => {
	const {
        description = '',
	} = getSellixServerData();

	return (
		<>
            { decodeEntities( description ) }
		</>
	);
};

export default ContentComponent;