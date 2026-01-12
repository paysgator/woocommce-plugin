const settings = window.wc.wcSettings.getSetting('paymentMethodData').paysgator || {};
const label = window.wp.i18n.__('Paysgator', 'paysgator-woocommerce-payment');
const Content = () => {
    return window.wp.element.createElement(
        'div',
        null,
        settings.description || window.wp.i18n.__('Pay secured with Paysgator.', 'paysgator-woocommerce-payment')
    );
};

if (window.wc.wcBlocksRegistry) {
    window.wc.wcBlocksRegistry.registerPaymentMethod({
        name: 'paysgator',
        label: settings.title || label,
        ariaLabel: label,
        content: window.wp.element.createElement(Content, null),
        edit: window.wp.element.createElement(Content, null),
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    });
}
