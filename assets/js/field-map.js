jQuery(function($) {
    $(document).on('click', '.sf-field-remove', function() {
        //put jquery this context into a var
        var btn = $(this);
        var del_cur_sf_row = $(this).closest('td').siblings('.sf-td-col');
        var del_text = del_cur_sf_row.find('label').html();
        var del_id = del_cur_sf_row.find('label').prop('for').substring(4);
        var out_cur_row = btn.closest('tr').siblings('#sf-tr-row').find('.wp-sf-field').append($("<option></option>").attr("value",del_id).text(del_text));
        btn.closest('tr').siblings('#sf-tr-row').show();
        //use .closest() to navigate from the buttno to the closest row
        btn.closest('tr').remove();
    });

    $(document).on('click', '.sf-field-add', function() {
        var btn = $(this);
        var cur_sf_row = $(this).closest('td').siblings('.sf-td-col');
        var cur_woo_row = $(this).closest('td').siblings('.woo-td-col');
        var clonedRow = btn.closest('tr').clone();
        var selected_sf_option_val = cur_sf_row.children('.wp-sf-field').find(":selected").val();
        var selected_sf_option_text = cur_sf_row.children('.wp-sf-field').find(":selected").text();
        clonedRow.find('*').addBack().filter('[id]').each(function() {
            //clear id or change to something else
            rnd = getRandomInt(1, 5000);
            this.id += '_clone_' + rnd;
        });
        var selected_woo_option_val = cur_woo_row.children('.woo-sf-field').find(":selected").val();
        clonedRow.children('td:nth-of-type(1)').html('<b><label for="elm_' + selected_sf_option_val + '">' + selected_sf_option_text + '</label></b>');
        clonedRow.children('td:nth-of-type(2)').children('select').attr('name', 'product_multiple_data[' + selected_sf_option_val + ']');
        clonedRow.children('td:nth-of-type(2)').children('select').val(selected_woo_option_val);
        clonedRow.children('td:nth-of-type(2)').children('select').attr('id', 'elm_' + selected_sf_option_val);
        clonedRow.children('td:nth-of-type(3)').html('-');
        clonedRow.children('td:nth-of-type(4)').html('<a href="javascript:void(0);" class="sf-field-remove"><span class="dashicons dashicons-minus"></span>Remove</a>');
        btn.closest('tbody').append(clonedRow);
        // Remove used option from select
        cur_sf_row.children('.wp-sf-field').find(":selected").remove();
        var new_selected_option_val = cur_sf_row.children('.wp-sf-field').find(":selected").val();
        if (typeof(new_selected_option_val) == "undefined") {
            cur_sf_row.closest('tr').hide();
        }
    });
});

/**
 * Returns a random number between min (inclusive) and max (exclusive)
 */
function getRandomArbitrary(min, max) {
    return Math.random() * (max - min) + min;
}
/**
 * Returns a random integer between min (inclusive) and max (inclusive)
 * Using Math.round() will give you a non-uniform distribution!
 */
function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}