<script>
'use strict';

var conditionalConfigurations = {};
<?php
foreach ($this->getConfigurations() as $i => $configuration)
{
    if ( !isset($configuration['label']) )
        $configuration['label'] = $configuration['name'];
    
    $premiumHolder = (isset($configuration['premiumholder'])) ? $configuration['premiumholder'] : false;
?>
conditionalConfigurations.<?php echo esc_js( $i ); ?> = {
    name: '<?php echo esc_js( $configuration['name'] ); ?>',
    <?php if ( $premiumHolder ) { ?>
    premiumholder: true
    <?php } else { ?>
    add: function(groupId, values = null, logic = 'and') {
        addCondition(groupId, '<?php echo esc_js( $i ); ?>', '<?php echo esc_js( $configuration['label'] ); ?>', <?php echo wp_json_encode($configuration['inputs']); ?>, values, logic);
    }
    <?php } ?>
};
<?php } ?>

function addGroup(logic = 'and')
{
    var i = jQuery('.ucfw-single-group').length;
        i = (i < 0) ? 0 : i;
    
    var html = '';

        if (i > 0)
            html += groupLogic(logic, i);

        html += `<div id="ucfw-group-` + i + `" class="ucfw-single-group ui segment" data-group-id="` + i + `">
        <div class="group-conditions"></div>
        <div class="ucfw-add-condition">
            <select class="ui fluid search normal dropdown">`;

        html += '<option value=""><?php echo ucfw_esc_js( 'Add condition', 'ultimate-coupon-for-woocommerce' ); ?></option>';
        for (var key in conditionalConfigurations)
        {
            var name    = conditionalConfigurations[key].name;

            if (conditionalConfigurations[key].hasOwnProperty('premiumholder') && conditionalConfigurations[key].premiumholder == true)
                name   += ' (<?php echo ucfw_esc_js( 'Premium', 'ultimate-coupon-for-woocommerce' ); ?>)';

            html += '<option value="' + key + '">' + name + '</option>';
        }

        html += `</select>
        </div>
        <span class="ucfw-remove-group" data-group-id="` + i + `" data-remove-group="yes">
            <span class="dashicons dashicons-no-alt"></span> <?php echo ucfw_esc_js( 'Remove', 'ultimate-coupon-for-woocommerce' ); ?>
        </span>
    </div>`;

    jQuery('#ucfw-groups').append(html);
    jQuery('#ucfw-group-' + i).find('.ucfw-add-condition select').dropdown();
}

function addCondition(groupid, type, label, inputs, data = null, logic = 'and')
{
    var groupEl  = jQuery('#ucfw-group-' + groupid);
    var condNum  = groupEl.find('.ucfw-single-condition').length;
    var inputNum = inputs.length;

    var condid = condNum;
        condid = (condid < 0) ? 0 : condid;
    
    var html  = '';

        if (condNum > 0)
            html += conditionLogic(logic, groupid, condid);

        html += `<div id="ucfw-group-` + groupid + `-condition-` + condid + `" class="ucfw-single-condition" data-condition-id="` + condid + `" data-group-id="` + groupid + `">
        <label>`+ label +`</label>
        <input type="hidden" name="_ucfw_groups[` + groupid + `][conditions][` + condid + `][type]" value="` + type + `">
        <div class="ucfw-condition-inputs">`;

        for (var i in inputs)
        {
            if ( typeof inputs[i] === 'undefined' )
                continue;

            var input         = inputs[i];
            input.groupId     = groupid;
            input.conditionId = condid;

            if (data === null)
                input.data = null;
            else
            {
                if (typeof input.id === 'string')
                    input.data = data[input.id];
                else
                    input.data = data;
            }

            if (input.tag === 'select')
                html += conditionSelect(input, condid, groupid);
            else if (input.tag == 'input')
                html += conditionInput(input, condid, groupid);
        }

        html += `</div>
        <span class="remove" data-condition-id="` + condid + `" data-group-id="` + groupid + `" data-remove-condition="yes"><?php echo ucfw_esc_js( 'Remove', 'ultimate-coupon-for-woocommerce' ); ?></span>
    </div>`;

    groupEl.find('.group-conditions').append(html);
    
    jQuery('#ucfw-group-' + groupid + '-condition-' + condid).find('.ucfw-condition-inputs select:not([data-search])').dropdown();
    jQuery('#ucfw-group-' + groupid + '-condition-' + condid).find('.ucfw-condition-inputs select[data-search]').each(function()
    {
        var select    = jQuery(this);
        var search    = select.data('search');

        var searchUrl = '<?php echo esc_js( $this->getSearchUrl() ); ?>/' + search + '/{query}';
        select.dropdown({
            apiSettings: {
                url: searchUrl
            }
        });
    });

    return true;
}

function conditionSelect(select, conditionId, groupId)
{
    var selectInp = select;
    var id        = ('id' in select && select.id !== null) ? select.id : null;
    var multiple  = ('multiple' in select && select.multiple == true) ? 'multiple' : '';
    var search    = ('search' in select && select.search !== null) ? 'data-search="' + select.search + '"' : '';
    var searchId  = ('searchid' in select && select.searchid !== null) ? 'data-search-id="' + select.searchid + '"' : '';
    var classes   = ('class' in select && select.class !== null) ? select.class : '';
    var uid       = (id !== null) ? 'g' + groupId + '-c' + conditionId + '-' + select.id : '';
    var name      = (id !== null) ? '[' + select.id + ']' : '';
        name     += (multiple == 'multiple') ? '[]' : '';

    var html = '<select ' + multiple + ' id="' + uid + '" name="_ucfw_groups[' + select.groupId + '][conditions][' + select.conditionId + '][data]' + name + '" class="ui fluid search dropdown ' + classes + '" ' + search + ' ' + searchId + '>';

    if ('placeholder' in select && select.placeholder !== null)
        html += '<option value="">' + select.placeholder + '</option>';

    for (var key in select.options)
    {
        var label = select.options[key];

        var selected = '';
        if (select.data !== null)
        {
            if (Array.isArray(select.data))
            {
                for (var i in select.data)
                {
                    if (key === select.data[i].value)
                    {
                        selected = 'selected';
                        break;
                    }
                }
            }
            else if (typeof select.data === 'object')
            {
                if (key === select.data.value)
                    selected = 'selected';
            }
            else if (typeof select.data === 'string')
            {
                if (key === select.data)
                    selected = 'selected';
            }
        }

        html += '<option value="' + key + '|' + label + '" ' + selected + '>' + label + '</option>';
    }

    if (select.data !== null && select.options.length == 0)
    {
        if (Array.isArray(select.data))
        {
            for (var key in select.data)
                html += '<option value="' + select.data[key].value + '|' + select.data[key].text + '" selected>' + select.data[key].text + '</option>';
        }
        else if (typeof select.data === 'object')
        {
            html += '<option value="' + select.data.value + '|' + select.data.text + '" selected>' + select.data.text + '</option>';
        }
    }

    html += '</select>';

    jQuery(document).on('change', '#g' + groupId + '-c' + conditionId + '-' + select.id, function()
    {
        var updateQueryId = ('updateQuery' in selectInp && selectInp.updateQuery !== null) ? selectInp.updateQuery : null;
        
        if (updateQueryId == null)
            return;
        
        var updateSelect = jQuery('#g' + groupId + '-c' + conditionId + '-' + updateQueryId);
        updateSelect.attr('data-search-id', jQuery(this).val() );

        var search = updateSelect.data('search');

        if ( typeof( updateSelect.data('search-id') ) )
            search += '/' + updateSelect.data('search-id');

        var searchUrl = '<?php echo esc_js( $this->getSearchUrl() ); ?>/' + search + '/{query}';
        updateSelect.dropdown({
            apiSettings: {
                url: searchUrl
            }
        });
    });

    return html;
}

function conditionInput(input, conditionId, groupId)
{
    var id       = ('id' in input && input.id !== null) ? input.id : null;
    var classes  = ('class' in input && input.class !== null) ? input.class : '';
    var uid      = (id !== null) ? 'g' + groupId + '-c' + conditionId + '-' + input.id : '';
    var name     = (id !== null) ? '[' + input.id + ']' : '';
    var value    = '';

    var placeholder = ('placeholder' in input && input.placeholder !== null) ? 'placeholder="' + input.placeholder + '"' : '';

    if ('data' in input && input.data !== null)
    {
        if (typeof input.data === 'object' && typeof id === 'string')
            value = 'value="' + input.data[id] + '"';
        else
            value = 'value="' + input.data + '"';
    }

    var html = '<input type="' + input.type + '" id="' + uid + '" name="_ucfw_groups[' + input.groupId + '][conditions][' + input.conditionId + '][data]' + name + '" class="' + classes + '" ' + value + ' ' + placeholder + '>';
    return html;
}

function groupLogic(logic, groupid)
{
    var andClass  = 'ucfw-and';
        andClass += (logic === 'and') ? ' ucfw-logic-active' : '';
    
    var orClass  = 'ucfw-or';
        orClass += (logic === 'or') ? ' ucfw-logic-active' : '';

    var html = `<div id="ucfw-logic-` + groupid + `" class="ucfw-logics">
        <input type="hidden" name="_ucfw_groups[` + groupid + `][logic]" value="` + logic + `" data-group-id="` + groupid + `">
        <a class="` + andClass + `" data-logic="and"><?php echo ucfw_esc_js( 'And', 'ultimate-coupon-for-woocommerce' ); ?></a><a class="` + orClass + `" data-logic="or"><?php echo ucfw_esc_js( 'Or', 'ultimate-coupon-for-woocommerce' ); ?></a>
    </div>`;
    return html;
}

function conditionLogic(logic, groupid, condid)
{
    var andClass  = 'ucfw-and';
        andClass += (logic === 'and') ? ' ucfw-logic-active' : '';
    
    var orClass  = 'ucfw-or';
        orClass += (logic === 'or') ? ' ucfw-logic-active' : '';

    var html = `<div id="ucfw-group-` + groupid + `-logic-` + condid + `" class="ucfw-logics">
        <input type="hidden" name="_ucfw_groups[` + groupid + `][conditions][` + condid + `][logic]" value="` + logic + `" data-group-id="` + groupid + `" data-condition-id="` + condid + `">
        <a class="` + andClass + `" data-logic="and"><?php echo ucfw_esc_js( 'And', 'ultimate-coupon-for-woocommerce' ); ?></a><a class="` + orClass + `" data-logic="or"><?php echo ucfw_esc_js( 'Or', 'ultimate-coupon-for-woocommerce' ); ?></a>
    </div>`;
    return html;
}

jQuery(document).on('click', '#ucfw_btn_add_group', function(e)
{
    e.preventDefault();
    addGroup();
});

jQuery(document).on('click', '.ucfw-logics a', function(e)
{
    var logicBtn = jQuery(this);
    var logicEl  = logicBtn.parents('.ucfw-logics');
    var logic    = logicBtn.data('logic');

    if (logicEl.length == 0)
        return false;

    logicEl.find('input').val(logic);

    logicEl.find('a').removeClass('ucfw-logic-active');
    logicBtn.addClass('ucfw-logic-active');
});

jQuery(document).on('change', '.ucfw-add-condition select', function(e)
{
    var select  = jQuery(this);
    var groupid = select.parents('.ucfw-single-group').data('group-id');
    var key     = conditionalConfigurations[select.val()];
    var premiumholder = ('premiumholder' in key && key.premiumholder == true) ? true : false;

    if (premiumholder)
    {
        select.val('');
        ucfw_buy_premium_popup();
        return;
    }

    key.add(groupid);
});

jQuery(document).on('click', '[data-remove-condition]', function(e)
{
    var groupid = jQuery(this).data('group-id');
    var condid  = jQuery(this).data('condition-id');

    jQuery('#ucfw-group-' + groupid + '-condition-' + condid).remove();
    jQuery('#ucfw-group-' + groupid + '-logic-' + condid).remove();

    var firstChild = jQuery('#ucfw-group-' + groupid).find('.group-conditions').children(':first');
    if (typeof firstChild !== 'undefined' && firstChild.length > 0)
    {
        if (firstChild.hasClass('ucfw-logics'))
            firstChild.remove();
    }
});

jQuery(document).on('click', '[data-remove-group]', function(e)
{
    var groupid = jQuery(this).data('group-id');

    jQuery('#ucfw-group-' + groupid).remove();
    jQuery('#ucfw-logic-' + groupid).remove();

    var firstChild = jQuery('#ucfw-groups').children(':first');
    if (typeof firstChild !== 'undefined' && firstChild.length > 0)
    {
        if (firstChild.hasClass('ucfw-logics'))
            firstChild.remove();
    }
});

jQuery(document).on('click', '#ucfw-btn-save-conditions', function(e)
{
    e.preventDefault();
    jQuery.ajax({
        type: 'POST',
        url: '<?php echo esc_js( $this->getSaveUrl() ); ?>',
        data: jQuery('#ucfw-conditional-settings, #ucfw-conditions-enabled-container').find('input, textarea, select').serializeJSON(),
        beforeSend: function()
        {
            jQuery('#ucfw-btn-save-conditions').attr('disabled', true).text('<?php echo ucfw_esc_js( 'Saving...', 'ultimate-coupon-for-woocommerce' ); ?>');
        },
        success: function(result)
        {
            jQuery('#ucfw-btn-save-conditions').attr('disabled', false).removeClass('blue').addClass('positive').text('<?php echo ucfw_esc_js( 'Saved!', 'ultimate-coupon-for-woocommerce' ); ?>');

            setTimeout(function()
            {
                jQuery('#ucfw-btn-save-conditions').removeClass('positive').addClass('blue').text('<?php echo ucfw_esc_js( 'Save Again', 'ultimate-coupon-for-woocommerce' ); ?>');
            }, 1500);
        },
        error: function(result)
        {
            jQuery('#ucfw-btn-save-conditions').attr('disabled', false).removeClass('positive blue').addClass('negative').text('<?php echo ucfw_esc_js( 'Failed to save', 'ultimate-coupon-for-woocommerce' ); ?>');

            setTimeout(function()
            {
                jQuery('#ucfw-btn-save-conditions').removeClass('negative positive').addClass('blue').text('<?php echo ucfw_esc_js( 'Try Again', 'ultimate-coupon-for-woocommerce' ); ?>');
            }, 1500);
        }
    });
});

jQuery(function(){
<?php
if ( $this->getEnabled() )
{
?>
    jQuery('#ucfw-conditions-enabled-checkbox').attr('checked', true);
<?php
}

if ( $this->getSettings() )
{
    $groups = $this->getSettings();
    $i     = 0;

    foreach ($groups as $gi => $group)
    {
        if ( 'logic' === $group['type'] )
            continue;
        
        $groupLogic = ($gi != 0) ? $groups[($gi - 1)]['logic'] : 'and';
        $conditions = $group['conditions'];
?>
        addGroup('<?php echo esc_js( $groupLogic ); ?>');
<?php
        foreach ($group['conditions'] as $ci => $condition)
        {
            if ( 'logic' === $condition['type'] )
                continue;
            
            $conLogic = ($ci != 0) ? $conditions[($ci - 1)]['logic'] : 'and';
        ?>
        conditionalConfigurations.<?php echo esc_js( $condition['type'] ); ?>.add(<?php echo absint($i); ?>, <?php echo wp_json_encode($condition['data']); ?>, '<?php echo esc_js( $conLogic ); ?>');
<?php   }
        $i++;
    }
}
?>

if (jQuery('#ucfw-groups').is(':empty'))
    addGroup('and');
});
</script>