jQuery(document).ready(function()
{
    "use strict";
    
    // Max discount on percentage
    var generalSetting = jQuery('#general_coupon_data'),
        discountType = generalSetting.find('#discount_type'),
        maxDicount   = generalSetting.find('.form-field.ucfw_max_discount_field');

        discountType.on( 'change', function() {

            if ( jQuery(this).val( ) === 'percent' )
                maxDicount.show();
            else
                maxDicount.hide();
        });
    discountType.trigger('change')
    
    jQuery(document).on('click', '.ucfw-buy-premium', function(){
        ucfw_buy_premium_popup();
    });

    jQuery('.ucfw-loader').fadeOut();

    if (typeof _ucfwp === 'undefined')
    {
        jQuery(document).on('change', 'select[name="carbon_fields_compact_input[_ucfw_template_type]"]', function()
        {
            if ( 'header-free' === jQuery(this).val()  || 'footer-free' === jQuery(this).val() )
            {
                jQuery(this).val('popup');
                ucfw_buy_premium_popup();
            }
        });
    }

    jQuery(document).on('click', 'input[name="carbon_fields_compact_input[_ucfw_template_popupdesign]"]', function()
    {
        if ( jQuery(this).val().includes('premium') )
        {
            ucfw_buy_premium_popup();
            return;
        }
    });

    jQuery(document).on('click', 'input[name="carbon_fields_compact_input[_ucfw_template_bardesign]"]', function()
    {
        if ( jQuery(this).val().includes('premium') )
        {
            ucfw_buy_premium_popup();
            return;
        }
    });
});

function ucfw_buy_premium_popup()
{
    jQuery('.ui.modal.ucfw-buy-premium-popup').modal('show');
}
