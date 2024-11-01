$ = jQuery.noConflict();
var Obj = {};
var val = false;

if($('#woosfrest_account_syncing').length){
    $('#woosfrest_account_recordtype').on('change', function() {
        var val = $('option:selected', this).attr('type');
        if(val == 'B'){
        Obj['recordType'] = 'B';

            $('#woosfrest_account_syncing').show();
            $('#woosfrest_update_contact').show();
            if($('#woosfrest_account_sync').is(':checked'))
            $('#woosfrest_main_account_id').hide();
            else
            $('#woosfrest_main_account_id').show();
            $('#woosfrest_guest_tr_id').show();
        }else{
            Obj['recordType'] = 'P';

            $('#woosfrest_account_syncing').hide();
            $('#woosfrest_update_contact').hide();
            $('#woosfrest_main_account_id').hide();
            $('#woosfrest_guest_tr_id').hide(); 
        }
    });

    var orgType = $('option:selected', '#woosfrest_account_recordtype').attr('type');
    if(orgType == 'B'){
        Obj['recordType'] = 'B';
        $('#woosfrest_account_syncing').show();
        $('#woosfrest_update_contact').show();
        if($('#woosfrest_account_sync').is(':checked'))
        $('#woosfrest_main_account_id').hide();
        else
        $('#woosfrest_main_account_id').show();
        $('#woosfrest_guest_tr_id').show();
    }else{
        Obj['recordType'] = 'P';

        $('#woosfrest_account_syncing').hide();
        $('#woosfrest_update_contact').hide();
        $('#woosfrest_main_account_id').hide();
        $('#woosfrest_guest_tr_id').hide(); 
    }
}

function setJSONString() {
   
    $('#acc-setting-table').find('td').each(function () {
        $(this).find('input, select').each(function () {
            var val = '';
            if ($(this).attr('type') == 'checkbox') {
                val = $(this).is(':checked');
            } else {
                val = $(this).val();
            }
            Obj[$(this).attr('data-name')] = val;
            Obj[$(this).attr('data-account')] = $(this).attr('account_name');
        });
    });

    $('#account_data_json').val(JSON.stringify(Obj));
    return true;
}