
import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';

import ContentComponent from './component';
import { getSellixServerData } from './utils';

const defaultLabel = __(
	'Sellix Pay',
	'woo-gutenberg-products-block'
);

const label = decodeEntities( getSellixServerData().title ) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
	return decodeEntities( getSellixServerData().description || '' );
};
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

const SellixComponent = ( { RenderedComponent, ...props } ) => {
	return <RenderedComponent { ...props } />;
};

/**
 * Sellix Pay payment method config object.
 */
const SellixPay = {
	name: "sellix",
	label: <Label />,
	content: <SellixComponent RenderedComponent={ ContentComponent }/>,
	edit: <SellixComponent RenderedComponent={ ContentComponent }/>,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: getSellixServerData().supports,
	},
};

registerPaymentMethod( SellixPay );
