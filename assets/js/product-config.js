$ = jQuery.noConflict();

function showDocFolder(elem) {
    var folderRow = document.getElementById('imagefolder_row');
    if ($(elem).is(':checked') == false) {
        $(folderRow).show();
    } else {
        $(folderRow).hide();
    }
}
var element = document.getElementById('woosfrest_sync_to_files');
showDocFolder(element);

function showUpdateStandard(elem) {
    $(elem).children().each(function() {
        if ($(this).val() == $(elem).val()) {
            if ($(this).attr('data-standard') == 'false') {
                $('#standard-pricebook').show();
            } else {
                $('#standard-pricebook').hide();
            }
        }
    });
}
var element = document.getElementById('woosfrest_pricebook');
showUpdateStandard(element);

function setJSONString() {
    var Obj = {};
    $('#prod-setting-table').find('td').each(function() {
        $(this).find('input, select').each(function() {
            var val = '';
            if ($(this).attr('type') == 'checkbox') {
                val = $(this).is(':checked');
            } else {
                val = $(this).val();
            }
            Obj[$(this).attr('data-name')] = val;
        });
    });
    $('#product_data_json').val(JSON.stringify(Obj));
    return true;
}