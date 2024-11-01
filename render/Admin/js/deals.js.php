<script>
'use strict';

function ucfw_deals_add_target_item(itemId = '', itemName = '<?php echo ucfw_esc_js( 'Search...', 'ultimate-coupon-for-woocommerce' ); ?>', itemQuantity = 1)
{
    var i = jQuery('.ucfw-deals-ts-group').length;
        i = (i < 0) ? 0 : i;

    var _ucfw_deals_item_type = (jQuery('#ucfw-deals-target-item-types').val() === 'categories') ? 'product-categories' : 'products';

    var html = `<tr id="ucfw-target-group-` + i + `" class="ucfw-deals-ts-group" data-target-group-id="` + i + `">
        <td class="ucfw-deals-ts-products">
            <select class="ucfw-deals-target-item-search ui fluid search dropdown" name="_ucfw_deals[0][target][items][` + i + `][item]" data-search="` + _ucfw_deals_item_type + `">
                <option value="` + itemId + `">` + itemName + `</option>
            </select>
        </td>
        <td class="ucfw-deals-quantity">
            <div class="ui fluid input">
                <input type="number" placeholder="<?php echo ucfw_esc_js( 'Quantity', 'ultimate-coupon-for-woocommerce' ); ?>" name="_ucfw_deals[0][target][items][` + i + `][quantity][value]" value="` + itemQuantity + `">
            </div>
        </td>
        <td class="ucfw-deals-action">
            <div class="ui mini buttons">
                <button class="ui red button" data-target-group-id="` + i + `" data-remove-group="yes"><?php echo ucfw_esc_js( 'Remove', 'ultimate-coupon-for-woocommerce' ); ?></button>
            </div>
        </td>
    </tr>`;

    jQuery('#ucfw-deals-target-items').append(html);
    jQuery('#ucfw-target-group-' + i).find('.dropdown').each(function()
    {
        var select = jQuery(this);
        if (select[0].hasAttribute('data-search'))
        {
            var searchUrl = '<?php echo esc_js( $this->getSearchUrl() ); ?>/' + select.attr('data-search') + '/{query}';
            select.dropdown({
                apiSettings: {
                    url: searchUrl
                }
            });
        }
        else
            select.dropdown();
    });
}

function ucfw_deals_add_apply_item(itemId = '', itemName = '<?php echo ucfw_esc_js( 'Search...', 'ultimate-coupon-for-woocommerce' ); ?>', itemQuantity = 1, discountType = 'override', discountValue = 0)
{
    var i = jQuery('.ucfw-deals-as-group').length;
        i = (i < 0) ? 0 : i;
    
    var _ucfw_deals_item_type = (jQuery('#ucfw-deals-apply-item-types').val() === 'categories') ? 'product-categories' : 'products';
    
    var discountTypes = {
        override: '<?php echo ucfw_esc_js( 'Override Price', 'ultimate-coupon-for-woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?>',
        percent: '<?php echo ucfw_esc_js( 'Percentage', 'ultimate-coupon-for-woocommerce' ) . ' (%)'; ?>',
        fixed: '<?php echo ucfw_esc_js( 'Fixed Discount', 'ultimate-coupon-for-woocommerce' ) . ' (-' . get_woocommerce_currency_symbol() . ')'; ?>'
    };

    var discountTypeHtml  = '';
    var discountTypeText  = '';
    var discountTypeValue = 'override';
    for (var di in discountTypes)
    {
        if (di === discountType)
        {
            discountTypeHtml  += '<div class="item active selected" data-value="' + di + '">' + discountTypes[di] + '</div>';
            discountTypeText  += discountTypes[di];
            discountTypeValue  = di;
        }
        else
            discountTypeHtml += '<div class="item" data-value="' + di + '">' + discountTypes[di] + '</div>';
    }

    var html = `<tr id="ucfw-apply-group-` + i + `" class="ucfw-deals-as-group" data-apply-group-id="` + i + `">
        <td class="ucfw-deals-as-products">
            <select class="ucfw-deals-apply-item-search ui fluid search dropdown" name="_ucfw_deals[0][apply][items][` + i + `][item]" data-search="` + _ucfw_deals_item_type + `">
                <option value="` + itemId + `">` + itemName + `</option>
            </select>
        </td>
        <td class="ucfw-deals-quantity">
            <div class="ui fluid input">
                <input type="number" placeholder="<?php echo ucfw_esc_js( 'Quantity', 'ultimate-coupon-for-woocommerce' ); ?>" name="_ucfw_deals[0][apply][items][` + i + `][quantity][value]" value="` + itemQuantity + `">
            </div>
        </td>
        <td class="ucfw-deals-discount">
            <div class="ui fluid left labeled input">
                <div class="ui dropdown label">
                    <input type="hidden" name="_ucfw_deals[0][apply][items][` + i + `][discount][type]" value="` + discountTypeValue + `">
                    <div class="text">` + discountTypeText + `</div>
                    <i class="dropdown icon"></i>
                    <div class="menu">` + discountTypeHtml + `</div>
                </div>
                <input type="number" placeholder="<?php echo ucfw_esc_js( 'Amount', 'ultimate-coupon-for-woocommerce' ); ?>" name="_ucfw_deals[0][apply][items][` + i + `][discount][value]" value="` + discountValue + `">
            </div>
        </td>
        <td class="ucfw-deals-action">
            <div class="ui mini buttons">
                <button class="ui red button" data-apply-group-id="` + i + `" data-remove-group="yes"><?php echo ucfw_esc_js( 'Remove', 'ultimate-coupon-for-woocommerce' ); ?></button>
            </div>
        </td>
    </tr>`;

    jQuery('#ucfw-deals-apply-items').append(html);
    jQuery('#ucfw-apply-group-' + i).find('.dropdown').each(function()
    {
        var select = jQuery(this);
        if (select[0].hasAttribute('data-search'))
        {
            var searchUrl = '<?php echo esc_js( $this->getSearchUrl() ); ?>/' + select.attr('data-search') + '/{query}';
            select.dropdown({
                apiSettings: {
                    url: searchUrl
                }
            });
        }
        else
            select.dropdown();
    });
}

jQuery(function()
{
    var _ucfw_deals_target_item_types = <?php echo wp_json_encode($this->getItemTypes('target')); ?>;
    for (var i in _ucfw_deals_target_item_types)
    {
        if (_ucfw_deals_target_item_types[i].selected)
        {
            jQuery('select#ucfw-deals-target-item-types').attr('data-item-type', _ucfw_deals_target_item_types[i].value).append('<option value="' + _ucfw_deals_target_item_types[i].value + '" selected>' + _ucfw_deals_target_item_types[i].text + '</option>').attr({'data-current-value': _ucfw_deals_target_item_types[i].value, 'data-current-text': _ucfw_deals_target_item_types[i].text});

            if ( _ucfw_deals_target_item_types[i].value === 'categories' )
            {
                jQuery('.ucfw-deals-target-item-type').text('<?php echo ucfw_esc_js( 'Category', 'ultimate-coupon-for-woocommerce' ); ?>');
                jQuery('.ucfw-deals-target-items-type').text('<?php echo ucfw_esc_js( 'Categories', 'ultimate-coupon-for-woocommerce' ); ?>');
            }
        }
        else
            jQuery('select#ucfw-deals-target-item-types').attr('data-item-type', _ucfw_deals_target_item_types[i].value).append('<option value="' + _ucfw_deals_target_item_types[i].value + '">' + _ucfw_deals_target_item_types[i].text + '</option>');
    }

    var _ucfw_deals_apply_item_types = <?php echo wp_json_encode($this->getItemTypes('apply')); ?>;
    for (var i in _ucfw_deals_apply_item_types)
    {
        if (_ucfw_deals_apply_item_types[i].selected)
        {
            jQuery('select#ucfw-deals-apply-item-types').attr('data-item-type', _ucfw_deals_apply_item_types[i].value).append('<option value="' + _ucfw_deals_apply_item_types[i].value + '" selected>' + _ucfw_deals_apply_item_types[i].text + '</option>').attr({'data-current-value': _ucfw_deals_apply_item_types[i].value, 'data-current-text': _ucfw_deals_apply_item_types[i].text});

            if (_ucfw_deals_apply_item_types[i].value === 'categories')
            {
                jQuery('.ucfw-deals-apply-item-type').text('<?php echo ucfw_esc_js( 'Category', 'ultimate-coupon-for-woocommerce' ); ?>');
                jQuery('.ucfw-deals-apply-items-type').text('<?php echo ucfw_esc_js( 'Categories', 'ultimate-coupon-for-woocommerce' ); ?>');
            }
        }
        else
            jQuery('select#ucfw-deals-apply-item-types').attr('data-item-type', _ucfw_deals_apply_item_types[i].value).append('<option value="' + _ucfw_deals_apply_item_types[i].value + '">' + _ucfw_deals_apply_item_types[i].text + '</option>');
    }

    var _ucfw_deals_target_matches = <?php echo wp_json_encode($this->getTargetMatches()); ?>;
    for (var i in _ucfw_deals_target_matches)
    {
        if (_ucfw_deals_target_matches[i].selected)
            jQuery('select#ucfw-deals-target-match').append('<option value="' + _ucfw_deals_target_matches[i].value + '" selected>' + _ucfw_deals_target_matches[i].text + '</option>').attr({'data-current-value': _ucfw_deals_target_matches[i].value, 'data-current-text': _ucfw_deals_target_matches[i].text});
        else
            jQuery('select#ucfw-deals-target-match').append('<option value="' + _ucfw_deals_target_matches[i].value + '">' + _ucfw_deals_target_matches[i].text + '</option>');
    }
  
    jQuery('select#ucfw-deals-target-match').dropdown({
        onChange: function(value, text)
        {
            if (value === 'premium')
            {
                ucfw_buy_premium_popup();
                jQuery('select#ucfw-deals-target-match').dropdown('set exactly', ['all']);
                return;
            }
        }
    });

    var _ucfw_deals_apply_types = <?php echo wp_json_encode($this->getApplyTypes()); ?>;
    for (var i in _ucfw_deals_apply_types)
    {
        if (_ucfw_deals_apply_types[i].selected)
            jQuery('select#ucfw-deals-apply-types').append('<option value="' + _ucfw_deals_apply_types[i].value + '" selected>' + _ucfw_deals_apply_types[i].text + '</option>').attr({'data-current-value': _ucfw_deals_apply_types[i].value, 'data-current-text': _ucfw_deals_apply_types[i].text});
        else
            jQuery('select#ucfw-deals-apply-types').append('<option value="' + _ucfw_deals_apply_types[i].value + '">' + _ucfw_deals_apply_types[i].text + '</option>');
    }
    jQuery('select#ucfw-deals-apply-types').dropdown({
        onChange: function(value, text)
        {
            if (value === 'premium')
            {
                ucfw_buy_premium_popup();
                jQuery('select#ucfw-deals-apply-types').dropdown('set exactly', ['all']);
                return;
            }
        }
    });

    jQuery('select.ucfw-deals-item-types').dropdown({
        onChange: function(value, text)
        {
            var _ucfw_select     = jQuery(this);
            var _ucfw_section    = _ucfw_select.data('section');

            if (value === 'premium')
            {
                ucfw_buy_premium_popup();
                _ucfw_select.dropdown('set exactly', _ucfw_select.attr('data-current-value'));

                return;
            }

            if ( value === 'categories' )
            {
                _ucfw_select.attr('data-item-type', 'categories');
                jQuery('.ucfw-deals-' + _ucfw_section + '-item-type').text('<?php echo ucfw_esc_js( 'Category', 'ultimate-coupon-for-woocommerce' ); ?>');
                jQuery('.ucfw-deals-' + _ucfw_section + '-items-type').text('<?php echo ucfw_esc_js( 'Categories', 'ultimate-coupon-for-woocommerce' ); ?>');
            }
            else
            {
                _ucfw_select.attr('data-item-type', 'products');
                jQuery('.ucfw-deals-' + _ucfw_section + '-item-type').text('<?php echo ucfw_esc_js( 'Product', 'ultimate-coupon-for-woocommerce' ); ?>');
                jQuery('.ucfw-deals-' + _ucfw_section + '-items-type').text('<?php echo ucfw_esc_js( 'Products', 'ultimate-coupon-for-woocommerce' ); ?>');
            }

            jQuery('#ucfw-deals-' + _ucfw_section + '-items').html('');

            _ucfw_select.attr({
                'data-current-value': value,
                'data-current-text': text
            });
        }
    });

    // Add Target Product
    jQuery(document).on('click', '#ucfw-deals-btn-add-target-item', function(e)
    {
        e.preventDefault();
        ucfw_deals_add_target_item();
    });

    // Add Apply Product
    jQuery(document).on('click', '#ucfw-deals-btn-add-apply-item', function(e)
    {
        e.preventDefault();
        ucfw_deals_add_apply_item();
    });

    // Remove Product
    jQuery(document).on('click', '[data-remove-group]', function(e)
    {
        event.preventDefault();

        var targetid = jQuery(this).data('target-group-id');
        var applyid = jQuery(this).data('apply-group-id');

        jQuery('#ucfw-target-group-' + targetid).remove();
        jQuery('#ucfw-apply-group-' + applyid).remove();
    });

    // Save ajax
    jQuery(document).on('click', '#ucfw-btn-save-deals', function(e)
    {
        e.preventDefault();
        jQuery.ajax({
            type: 'POST',
            url: '<?php echo esc_js( $this->getSaveUrl() ); ?>',
            data: jQuery('#ucfw-deals-setting').find('input, textarea, select').serializeJSON(),
            beforeSend: function()
            {
                jQuery('#ucfw-btn-save-deals').attr('disabled', true).text('<?php echo ucfw_esc_js( 'Saving...', 'ultimate-coupon-for-woocommerce' ); ?>');
            },
            success: function(result)
            {
                jQuery('#ucfw-btn-save-deals').attr('disabled', false).removeClass('blue').addClass('positive').text('<?php echo ucfw_esc_js( 'Saved!', 'ultimate-coupon-for-woocommerce' ); ?>');

                setTimeout(function()
                {
                    jQuery('#ucfw-btn-save-deals').removeClass('positive').addClass('blue').text('<?php echo ucfw_esc_js( 'Save Again', 'ultimate-coupon-for-woocommerce' ); ?>');
                }, 1500);
            },
            error: function(result)
            {
                jQuery('#ucfw-btn-save-deals').attr('disabled', false).removeClass('positive blue').addClass('negative').text('<?php echo ucfw_esc_js( 'Failed to save', 'ultimate-coupon-for-woocommerce' ); ?>');

                setTimeout(function()
                {
                    jQuery('#ucfw-btn-save-deals').removeClass('negative positive').addClass('blue').text('<?php echo ucfw_esc_js( 'Try Again', 'ultimate-coupon-for-woocommerce' ); ?>');
                }, 1500);
            }
        });
    });

    // Output existing settings
    <?php
    if ($this->getSettings())
    {
        $n = 0;

        if ($this->getEnabled($n) === 'yes')
        {
        ?>
            jQuery('#ucfw-deals-<?php echo absint($n); ?>-enabled-checkbox').attr('checked', true);
        <?php
        }

        if ($this->getAllowRepeatedUse($n) === 'yes')
        {
        ?>
            jQuery('#ucfw-deals-<?php echo absint($n); ?>-allow-repeated-use-checkbox').attr('checked', true);
        <?php
        }

        if ($target = $this->getTargetSettings($n))
        {
            foreach ($target['items'] as $item)
            {
            ?>
            ucfw_deals_add_target_item('<?php echo absint( $item['item']['id'] ); ?>|<?php echo esc_js( $item['item']['name'] ); ?>', '<?php echo esc_js( $item['item']['name'] ); ?>', <?php echo absint( $item['quantity']['value'] ); ?>);
            <?php
            }
        }

        if ($apply = $this->getApplySettings($n))
        {
            foreach ($apply['items'] as $item) { ?>
            ucfw_deals_add_apply_item('<?php echo absint( $item['item']['id'] ); ?>|<?php echo esc_js( $item['item']['name'] ); ?>', '<?php echo esc_js( $item['item']['name'] ); ?>', <?php echo absint($item['quantity']['value']); ?>, '<?php echo esc_js( $item['discount']['type'] ); ?>', <?php echo absint($item['discount']['value']); ?>);
            <?php }
        }
    }
    ?>
});
</script>