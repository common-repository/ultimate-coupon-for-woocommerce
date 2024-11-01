jQuery(document).ready( function(){
    'use strict';

    var ucfw_template_popup_container = jQuery('#ucfw-template-popup-container');

    ucfw_template_popup_container
        .find('.copy-coupon').on('mouseover', function()
        {
            jQuery(this).text( jQuery(this).data('copy-text') );
        })
    .end()
        .find('.copy-coupon').on('mouseout', function()
        {
            jQuery(this).text( jQuery(this).data('coupon-code') ).removeClass('copied');
        })
    .end()
        .find('.copy-coupon').on('click', function()
        {
            navigator.clipboard.writeText( jQuery(this).data('coupon-code') );
            jQuery(this).text( jQuery(this).data('copied-text') ).addClass('copied');
        })
    .end()
        .find('.ucfw-template-popup-overlay').on('click', function ()
        {
            if ( ucfw_template_popup_container.css('visibility', 'visible') ) 
                ucfw_template_popup_container.hide('slow');
        })
    .end()
        .find('.close').on('click', function () 
        {
            if ( ucfw_template_popup_container.css('visibility') == 'visible' ) 
            {
                ucfw_template_popup_container.hide('slow');
                setCookie('popup-bar', 'popup-seen', '3');
            }
        });

    ucfw_template_popup_container.find('.ucfw-template-popup').on('click', function ( event ) {
        event.stopPropagation();
    });
});

function setCookie(cname, cvalue, exdays) 
{   
    var domainName = window.location.hostname;
    const d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    let expires = 'expires=' + d.toUTCString();
    document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/;SameSite=lax;domain=' + domainName;
}
