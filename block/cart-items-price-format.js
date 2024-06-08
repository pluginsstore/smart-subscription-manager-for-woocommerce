document.addEventListener('DOMContentLoaded', function() {
   
    if(  window?.wc?.blocksCheckout ){
    
        const { registerCheckoutFilters } = window.wc.blocksCheckout;

        const modifySubtotalPriceFormat = (
            defaultValue,
            extensions,
            args,
            validation
        ) => {
            const isCartContext = args?.context === 'cart';
            const isOrderSummaryContext = args?.context === 'summary';
            const cartItem = args?.cartItem.item_data;
            const swsPrice = cartItem.find( item => item.name === 'ssmfw-subsrcription-price-html');
            if ( isCartContext || isOrderSummaryContext ) {
               
                    
                if ( swsPrice ) {
                    val = swsPrice?.value;
                    if ( val != '' ) {
                        return defaultValue + ' ' + val;
                    }
                }
            }
            return defaultValue;
        };
        const modifyPlaceOrderButtonLabel = ( defaultValue, extensions, args ) => {
            const placeOrderBtnLbl = cart_obj.place_order_label;
            if ( placeOrderBtnLbl != '' ) {
                return placeOrderBtnLbl;
            }
            return defaultValue
        };
        
        
        registerCheckoutFilters( 'ssmfw-cart-checkout-filter', {
            subtotalPriceFormat: modifySubtotalPriceFormat,
            placeOrderButtonLabel: modifyPlaceOrderButtonLabel,
        } );
    }
});