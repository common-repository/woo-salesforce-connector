$ = jQuery.noConflict();

function toggleElem(elem, tgtId) {
    if ($(elem).is(':checked') == true) {
        $('#' + tgtId).show();
    } else {
        $('#' + tgtId).hide();
    }
}
$('#woosfrest_opp_create').trigger('change');
$('#woosfrest_order_create').trigger('change');
$('#woosfrest_contact_role').trigger('change');
$('#order_create_contract').trigger('change');


$(document).ready(function () {
    if ($('#woosfrest_refund_order').is(':checked')) {
        $('#order_create_contract').attr('checked', true);
        $('#contract_term').show();
    }
    if (!$('#order_create_contract').is(':checked')) {
        $('#woosfrest_refund_order').prop('checked', false);
    }
});
$('#woosfrest_refund_order').click(function () {
    if ($('#woosfrest_refund_order').is(':checked')) {
        $('#order_create_contract').prop('checked', true);
        $('#contract_term').show();
    }
});
$('#order_create_contract').click(function () {
    if (!$(this).is(':checked')) {
        $('#woosfrest_refund_order').prop('checked', false);
    }
});


function setJSONString() {
    var Obj = {};
    $('#order-setting-table').find('td').each(function () {
        $(this).find('input, select').each(function () {
            var val = '';
            if ($(this).attr('type') == 'checkbox') {
                val = $(this).is(':checked');
            } else {
                val = $(this).val();
            }
            Obj[$(this).attr('data-name')] = val;
        });
    });
    $('#order_data_json').val(JSON.stringify(Obj));
    return true;
}