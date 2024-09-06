const settings = window.wc.wcSettings.getSetting( 'orangepill_gateway_data', {} );

const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Orangepill', 'wc-phonepe' );
const Content = () => {
    console.log(settings.html);
    return settings.html;
};
const Orangepill_Block_Gateway = {
    name: 'orangepill_gateway',
    label: label,
    content: Object( window.wp.element.RawHTML )( {children: settings.html} ),
    edit: Object( window.wp.element.RawHTML )(  {children: settings.html} ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Orangepill_Block_Gateway );