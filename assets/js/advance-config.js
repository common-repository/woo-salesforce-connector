$ = jQuery.noConflict();

window.AccountJSON = '[]';
window.AccountTypeJSON = '[]';
window.AccountWooJSON = '[]';
window.contactJSON = '[]';
window.contactTypeJSON = '[]';
window.productJSON = '[]';
window.productWooJSON = '[]';
window.productTypeJSON = '[]';

window.accountSelect = '';
window.accountWooSelect = '';
window.accountTypes = '';
window.contactSelect = '';
window.contactTypes = '';
window.productSelect = '';
window.productWooSelect = '';
window.productTypes = '';
window.allTypes = '';

function addRow(tableId) {
    tableId = tableId.trim();
    var len = $('#' + tableId + ' tbody').find('tr').length;
    if (len == 0) {
        getTableRow(tableId);
    }
    if (len == 5)
        return;
    var maxId = $('#' + tableId + ' tbody').find('tr').last().find('td').first().text();
    maxId = parseInt(maxId);
    if (isNaN(maxId)) {
        maxId = 0;
    }
    var accRow = '<tr>' +
        '<td>' + (maxId + 1) + '</td>' +
        getSelectSf(tableId) +
        '<td>' + getMatchingTag(tableId) + '</td>' +
        getSelectWoo(tableId) +
        '<td><button type="button" onClick = "addRow(\'' + tableId + '\')"><span class="dashicons dashicons-plus"></span></button>' +
        '<button type="button" onClick = "removeRow(this)"><span class="dashicons dashicons-no"></span></button></td>' +
        '</tr>';
    $('#' + tableId + ' tbody').append(accRow);
}

function getTableRow(tableId) {
    if (tableId == 'account-matching-table') {
        var accountFields = JSON.parse(window.AccountJSON);
        var accountWooFields = JSON.parse(window.AccountWooJSON);
        window.accountSelect = '<td><select>';
        for (field in accountFields) {
            window.accountSelect += '<option value ="' + field + '">' + accountFields[field] + ' [' + field + ']' + ' (' + window.accountTypes[field] + ')' + '</option>';
        }
        window.accountSelect += '<select></td>';

        window.accountWooSelect = '<td><select>';
        for (field in accountWooFields) {
            window.accountWooSelect += '<option value ="' + field + '">' + accountWooFields[field] + '</option>';
        }
        window.accountWooSelect += '</td>';
    }
    if (tableId == 'contact-matching-table') {
        var contactFields = JSON.parse(window.contactJSON);
        window.contactSelect = '<td><select>';
        for (field in contactFields) {
            window.contactSelect += '<option value ="' + field + '">' + contactFields[field] + ' [' + field + ']' + ' (' + window.contactTypes[field] + ')' + '</option>';
        }
        window.contactSelect += '<select></td>';
    }
    if (tableId == 'product-matching-table') {
        var accountFields = JSON.parse(window.productJSON);
        var accountWooFields = JSON.parse(window.productWooJSON);
        window.productSelect = '<td><select>';
        for (field in accountFields) {
            window.productSelect += '<option value ="' + field + '">' + accountFields[field] + ' [' + field + ']' + ' (' + window.productTypes[field] + ')' + '</option>';
        }
        window.productSelect += '<select></td>';

        window.productWooSelect = '<td><select>';
        for (field in accountWooFields) {
            window.productWooSelect += '<option value ="' + field + '">' + accountWooFields[field] + '</option>';
        }
        window.productWooSelect += '</td>';
    }
}

function getSelectSf(tableId) {
    if (tableId == 'account-matching-table')
        return window.accountSelect;
    if (tableId == 'contact-matching-table')
        return window.contactSelect;
    if (tableId == 'product-matching-table')
        return window.productSelect;
}

function getSelectWoo(tableId) {
    if (tableId == 'account-matching-table' || tableId == 'contact-matching-table')
        return window.accountWooSelect;
    if (tableId == 'product-matching-table')
        return window.productWooSelect;
}

function removeRow(elem) {
    if ($(elem).parent().parent().siblings().length > 0)
        $(elem).parent().parent().remove();
}

function getMatchingTag(tableId) {
    return '<select><option value= "like">Like</option><option value= "exact">Exact</option></select>';
}

function setJSONString() {
    var mappingObj = {};
    $('.mappingTable').each(function () {
        var thisVar = {};
        var tableId = $(this).attr('id');
        $(this).find('tbody').find('tr').each(function () {
            var children = $(this).children('td');
            var fieldVal = {};
            fieldVal['criteriaId'] = $(children[0]).text();
            fieldVal['sfField'] = $(children[1]).find('select').val();
            fieldVal['joinCondition'] = $(children[2]).find('select').val();
            fieldVal['wooField'] = $(children[3]).find('select').val();
            fieldVal['sfType'] = window.allTypes[tableId][fieldVal['sfField']];
            thisVar[fieldVal['criteriaId']] = fieldVal;
        });
        mappingObj[tableId + '-enableMatching'] = $(this).parent().find('.enable-matching').val();
        if ($('#disable_def_criteria').is(':checked'))
            mappingObj['disable_def_criteria'] = 1;
        else
            mappingObj['disable_def_criteria'] = 0;
        // Account matching 
        if ($('#woosfrest_account_matching').is(':checked'))
            mappingObj['account-matching-table-enableMatching'] = 'yes';
        else
            mappingObj['account-matching-table-enableMatching'] = 'no';

        // Contact matching 
        if ($('#enable-contact-matching').is(':checked'))
            mappingObj['contact-matching-table-enableMatching'] = 'yes';
        else
            mappingObj['contact-matching-table-enableMatching'] = 'no';

        // Product matching 
        if ($('#enable-product-matching').is(':checked'))
            mappingObj['product-matching-table-enableMatching'] = 'yes';
        else
            mappingObj['product-matching-table-enableMatching'] = 'no';


        if (tableId == 'contact-matching-table') {
            mappingObj[tableId + '-enableContactAccount'] = $(this).parent().find('.enable-contact-account').val();
        }
        mappingObj[tableId + '-filterCondition'] = $(this).find('tfoot').find('input').val();
        mappingObj[tableId] = thisVar;
    });
    $('#matching_criteria_mapping').val(JSON.stringify(mappingObj));
    return true;
}