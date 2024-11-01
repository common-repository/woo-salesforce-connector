$ = jQuery.noConflict();
jQuery(function ($) {
    window.stop_clock = 'yes';
    window.formSubmitting = true;
    window.saleforceConnection = '';
    window.locator = '';
    window.counter_block_id = '';
    window.disableprod = false;
    window.disablecat = false;
    window.disableuser = false;
    window.disableorder = false;
    window.allowAlert = 'Importing products might create duplicates in woocommerce if post id is not populated in salesforce. Continue?';
    window.item_ids = new Array;
    window.sync_all = '';
    window.onload = function () {
        window.addEventListener("beforeunload", function (e) {
            if (window.formSubmitting) {
                return undefined;
            }
            var confirmationMessage = 'It looks like you have been syncronizing. ' + 'If you leave before completion, some of the data might not be saved.';
            (e || window.event).returnValue = confirmationMessage; //Gecko + IE
            return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
        });
    };

    $('.cm-confirm').on('click', function (e) {
        var result = confirm('Want to Continue?');
        if (!result) {
            return false;
            //Logic to delete the item
        }
    });
    $('.resync-prod').on('click', function () {
        if (window.disableprod == false) {
            var prod_id = $(this).prop('id');
            prod_id = prod_id.replace('resync_', '');
            real_prod_id = $('#real_pid_' + prod_id).html();
            var prod_name = $(this).parent().siblings('.productname').children('a').text();
            var result = confirm('Do you want to synchronize Product ' + prod_name + ' ?');
            if (result) {
                // Start order syncing
                window.item_ids = new Array;
                window.item_ids.push(prod_id);
                msgBlock = getProcessBlock("clock_block_export_prod");
                myAdminNoticesAppend('updated', 'Products(s) Export Started..', msgBlock);
                window.stop_clock = 'no';
                timer_clock1('clock_block_export_prod');
                if (window.item_ids.length > 0) {
                    window.disableprod = true;
                    wooSfExportProduct(window.item_ids.length, window.item_ids.length, 1, 0, 0, 0, 0);
                }
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('.resync-prod-imp').on('click', function () {
        if (window.disableprod == false) {
            var prod_id = $(this).prop('id');
            var prod_name = $(this).parent().siblings('.productname').children('a').text();
            prod_id = prod_id.replace('resync_', '');
            real_prod_id = $('#real_pid_' + prod_id).html();
            var result = confirm('Do you want to import ' + prod_name + ' ?');
            if (result) {
                // Start order syncing
                window.item_ids = new Array;
                window.item_ids.push(prod_id);
                msgBlock = getProcessBlock("clock_block3");
                myAdminNoticesAppend('updated', 'Product(s) Import Started..', msgBlock);
                window.stop_clock = 'no';
                timer_clock1('clock_block3');
                if (window.item_ids.length > 0) {
                    window.disableprod = true;
                    importSfProducts(window.item_ids.length, window.item_ids.length, 2000, 0, 0, 0);
                }
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('.resync-cat-imp').on('click', function () {
        if (window.disablecat == false) {
            var prod_id = $(this).prop('id');
            var prod_name = $(this).parent().siblings('.categoryname').children('a').text();
            prod_id = prod_id.replace('resync_', '');
            real_prod_id = $('#real_pid_' + prod_id).html();
            var result = confirm('Do you want to import ' + prod_name + ' ?');
            if (result) {
                // Start order syncing
                window.item_ids = new Array;
                window.item_ids.push(prod_id);
                msgBlock = getProcessBlock("clock_block_import_category");
                myAdminNoticesAppend('updated', 'Category(ies) Import Started..', msgBlock);
                window.stop_clock = 'no';
                timer_clock1('clock_block_import_category');
                if (window.item_ids.length > 0) {
                    window.disableprod = true;
                    importSfCategories(window.item_ids.length, window.item_ids.length, 1, 0, 0, 0);
                }
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('.resync-user').on('click', function () {
        if (window.disableuser == false) {
            var user_id = $(this).prop('id');
            user_id = user_id.replace('resync_', '');
            real_user_id = $('#real_uid_' + user_id).html();
            var result = confirm('Do you want to synchronize User ' + user_id + ' ?');
            if (result) {
                // Start order syncing
                window.user_item_ids = new Array;
                window.user_item_ids.push(user_id);
                msgBlock = getProcessBlock("clock_block_export_user");
                myAdminNoticesAppend('updated', 'User(s) Export Started..', msgBlock);
                window.stop_clock = 'no';
                timer_clock1('clock_block_export_user');
                window.disableuser = true;
                if (window.user_item_ids.length > 0) {
                    wooSfExportUsers(window.user_item_ids.length, window.user_item_ids.length, 1, 1, 0, 0, 0, 0);
                }
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('.resync-cat').on('click', function () {
        if (window.disablecat == false) {
            var category_id = $(this).prop('id');
            category_id = category_id.replace('resync_', '');
            real_category_id = $('#real_cid_' + category_id).html();
            var prod_name = $(this).parent().siblings('.categoryname').children('a').text();
            var result = confirm('Do you want to synchronize category ' + prod_name + ' ?');
            if (result) {
                // Start order syncing
                window.cat_item_ids = new Array;
                window.cat_item_ids.push(category_id);
                msgBlock = getProcessBlock("clock_block_export_category");
                myAdminNoticesAppend('updated', 'Category(ies) Export Started..', msgBlock);
                window.stop_clock = 'no';
                timer_clock1('clock_block_export_category');
                if (window.cat_item_ids.length > 0) {
                    window.disablecat = true;
                    wooSfExportCategory(window.cat_item_ids.length, window.cat_item_ids.length, 1, 0, 0, 0, 0);
                    //window.disablecat=false;
                }
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('.resync').on('click', function () {
        if (!window.disableorder) {
            var order_id = $(this).prop('id');
            order_id = order_id.replace('resync_', '');
            real_order_id = $('#real_oid_' + order_id).html();
            var result = confirm('Do you want to synchronize order ' + real_order_id + ' ?');
            if (result) {
                // Start order syncing
                window.order_item_ids = new Array;
                window.order_item_ids.push(order_id);
                msgBlock = getProcessBlock("clock_block_export_orders");
                myAdminNoticesAppend('updated', 'Order(s) Export Started..', msgBlock);
                window.stop_clock = 'no';
                timer_clock1('clock_block_export_orders');
                if (window.order_item_ids.length > 0) {
                    window.disableorder = true;
                    wooSfExportOrders(window.order_item_ids.length, window.order_item_ids.length, 1, 1, 0, 0, 0, 0);
                }
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('#synchronizeOrder_button').on('click', function () {
        if (window.disableorder == false) {
            if (confirm('Are you sure you want to Sync Orders ?')) {
                if (checkOtherClock()) {
                    return true;
                }
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('.spinner').addClass('is-active');
                    var spin = $('.spinner').html();
                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }


                var synced_ticked = $("#elm_synced_items").prop("checked");
                var unsynced_ticked = $("#elm_unsynced_items").prop("checked");
                var error_ticked = $("#elm_error_items").prop("checked");

                if (synced_ticked && unsynced_ticked) {
                    sync = 'A';
                } else if (synced_ticked) {
                    sync = 'S';
                } else if (unsynced_ticked) {
                    sync = 'U';
                } else if (error_ticked) {
                    sync = 'E';
                } else {
                    sync = 'N';
                }

                msgBlock = getProcessBlock("clock_block_sync_order");
                myAdminNoticesAppend('updated', 'Order(s) Syncronization Started..', msgBlock);
                window.stop_clock = 'no';
                timer_clock1('clock_block_sync_order');
                window.disableorder = true;
                getWooOrderIds(sync);
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('#synchronizeusers_button').on('click', function () {
        if (confirm('Are you sure you want to perform this action')) {
            var allowSyncAll = false;
            if (!window.disableuser) {
                if (window.stop_clock == 'no') {
                    if (confirm('Are you sure you want to perform this action')) {
                        allowSyncAll = true;
                    }
                } else {
                    allowSyncAll = true;
                }
                if (allowSyncAll) {
                    var wrapper = $('#my_wrap');
                    if (!wrapper.is('.processing')) {
                        $('.spinner').addClass('is-active');
                        var spin = $('.spinner').html();
                        wrapper.addClass('processing').block({
                            message: spin,
                            overlayCSS: {
                                background: '#fff',
                                opacity: 0.6
                            }
                        });
                    }
                    var synced_ticked = $("#elm_synced_items").prop("checked");
                    var unsynced_ticked = $("#elm_unsynced_items").prop("checked");
                    var error_ticked = $("#elm_error_items").prop("checked");

                    if (synced_ticked && unsynced_ticked) {
                        sync = 'A';
                    } else if (synced_ticked) {
                        sync = 'S';
                    } else if (unsynced_ticked) {
                        sync = 'U';
                    } else if (error_ticked) {
                        sync = 'E';
                    } else {
                        sync = 'N';
                    }
                    window.sync_all = 'yes';
                    jQuery('#error_response').remove();
                    msgBlock = getProcessBlock("clock_block_sync_users");
                    myAdminNoticesAppend('updated', 'User(s) Export Started..', msgBlock);
                    window.stop_clock = 'no';
                    timer_clock1('clock_block_sync_users');
                    window.disableuser = true;
                    getWooUserIds(sync);
                }
            } else {
                alert('Cannot start new Sync process while another is going on');
            }
        }
    });
    $('#wwsexportuser_button').on('click', function () {
        var allow_export = false;
        if (!window.disableuser) {
            if (window.stop_clock == 'no') {
                if (confirm('Are you sure you want to perform this action')) {
                    allow_export = true;
                }
            } else {
                allow_export = true;
            }
            if (allow_export) {
                jQuery('#error_response').remove();
                msgBlock = getProcessBlock("clock_block_sync_users");
                myAdminNoticesAppend('updated', 'User(s) Export Started..', msgBlock);
                window.stop_clock = 'no';
                timer_clock1('clock_block_sync_users');
                getWooUserIds('A');
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('#synchronizeproduct_button').on('click', function () {
        if (!window.allowImport) {
            if (!confirm(window.allowAlert)) {
                return;
            }
            window.allowImport = true;
        }
        if (disableprod == false) {
            if (window.stop_clock == 'no') {
                if (confirm('Are you sure you want to perform this action')) {
                    window.sync_all = 'yes';
                    $('#woosfrest-exportproduct-button').trigger('click');
                }
            } else {
                window.sync_all = 'yes';
                $('#woosfrest-exportproduct-button').trigger('click');
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('#woosfrest-exportproduct-button').on('click', function () {
        if (window.disableprod == false) {
            if (confirm('Are you sure you want to perform Export ?')) {
                window.disableprod = true;
                var wrapper = $('#my_wrap');

                if (!wrapper.is('.processing')) {
                    $('#woosfrest-importproduct-id').addClass('is-active');
                    var spin = $('#woosfrest-importproduct-id').html();

                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
                var synced_ticked = $("#elm_synced_items").prop("checked");
                var unsynced_ticked = $("#elm_unsynced_items").prop("checked");
                var error_ticked = $("#elm_error_items").prop("checked");

                if (synced_ticked && unsynced_ticked) {
                    sync = 'A';
                } else if (synced_ticked) {
                    sync = 'S';
                } else if (unsynced_ticked) {
                    sync = 'U';
                } else if (error_ticked) {
                    sync = 'E';
                } else {
                    sync = 'N';
                }
                if (window.stop_clock == 'no') {
                    if (confirm('Are you sure you want to perform this action')) {
                        msgBlock = getProcessBlock("clock_block2");
                        myAdminNoticesAppend('updated', 'Product(s) Export Started..', msgBlock);
                        window.stop_clock = 'no';
                        timer_clock1('clock_block2');
                        getWooProductIds(sync);
                    }
                } else {
                    msgBlock = getProcessBlock("clock_block2");
                    myAdminNoticesAppend('updated', 'Product(s) Export Started..', msgBlock);
                    window.stop_clock = 'no';
                    timer_clock1('clock_block2');
                    getWooProductIds(sync);
                }
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('#woosfrest-sync-limit-check').on('click', function () {
        if ($(this).is(":checked")) {
            $('#woosfrest-sync-limit').prop('disabled', false);
        } else {
            $('#woosfrest-sync-limit').prop('disabled', true);
        }
    });
    $('#woosfrest-importproduct-button').on('click', function (event) {
        if (!window.allowImport) {
            if (!confirm(window.allowAlert)) {
                return;
            }
            window.allowImport = true;
        }
        if (window.disableprod == false) {
            var limit = jQuery('#sfwp_product_limit').val();
            if(!jQuery.isNumeric(limit)){
                alert('Only numeric value are allowed in batch limit.');
                location.reload();
            }else if(limit>2000){
                alert('Batch limit cannot exceed 2000.');
                location.reload();
            }else if(limit<=0){
                alert('Batch limit cannot be neagtive or 0.');
                location.reload();
            }else{
                if (confirm('Are you Sure you want to Proceed With Import?')) {
                    var wrapper = $('#my_wrap');

                    if (!wrapper.is('.processing')) {
                        $('.spinner').addClass('is-active');
                        var spin = $('.spinner').html();
                        wrapper.addClass('processing').block({
                            message: spin,
                            overlayCSS: {
                                background: '#fff',
                                opacity: 0.6
                            }
                        });
                    }


                    window.disableprod = true;
                    if (window.stop_clock == 'no') {
                        if (confirm('Are you sure you want to perform this action. There is already import/export going on..')) {
                            getSObjectCount('Product2');
                        }
                    } else {
                        getSObjectCount('Product2');
                    }
                } else {
                    location.reload();
                }
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('#woosfrest-import-option').on('click', function () {
        $('#woosfrest-import-options').slideToggle();
    });

    $('.target').click(function () {
        $.post(ajax_object.ajaxurl, {
            action: 'ajax_action',
            post_id: $(this).find('input.post_id').attr('value')
        }, function (data) {
            alert(data); // alerts 'ajax submitted'
        });
    });
    
    
    

    $(document).on('click', '.notice-dismiss', function () {
        if (window.stop_clock == 'no') {
            if (confirm('Are you sure you want to dismiss this block. Import/export is going on..')) {
                $(this).parent().remove();
            }
        } else {
            $(this).parent().remove();
        }
    });
    $(document).on('click', '#doaction, #doaction2', function (event) {
        var selected_value_top = $('#bulk-action-selector-top').val();
        var selected_value_bot = $('#bulk-action-selector-bottom').val();
        if ($(this).prop('id') == 'doaction') {
            if (selected_value_top == '-1') {
                event.preventDefault();
                return false;
            }
            if (selected_value_top == 'importCategory-dis') {
                event.preventDefault();
                alert('There is some issue with your connector setting. Please resolve issues first');
            }
            if (selected_value_top == 'exportCategory-dis') {
                event.preventDefault();
                alert('There is some issue with your connector setting. Please resolve issues first');
            }
            if (selected_value_top == 'exportProduct-dis') {
                event.preventDefault();
                alert('There is some issue with your connector setting. Please resolve issues first');
            }
            if (selected_value_top == 'importProduct-dis') {
                event.preventDefault();
                alert('There is some issue with your connector setting. Please resolve issues first');
            }
            if (selected_value_top == 'exportUsers-dis') {
                event.preventDefault();
                alert('There is some issue with your connector setting. Please resolve issues first');
            }
            if (selected_value_top == 'exportOrders-dis') {
                event.preventDefault();
                alert('There is some issue with your connector setting. Please resolve issues first');
            }
            if (selected_value_top == 'exportCategory') {
                event.preventDefault();
                get_checked_cat_ids();
            }
            if (selected_value_top == 'exportOrders') {
                event.preventDefault();
                get_checked_order_ids();
            }
            if (selected_value_top == 'exportUsers') {
                event.preventDefault();
                get_checked_user_ids();
            }
            if (selected_value_top == 'exportProduct') {
                event.preventDefault();
                if (window.stop_clock == 'no') {
                    if (confirm('Are you sure you want to perform this action. There is already import/export going on..')) {
                        window.item_ids = new Array;
                        $("input[name='mergedproduct[]']:checkbox").each(function () {
                            if ($(this).prop('checked') == true) {
                                item_id = $(this).val();
                                window.item_ids.push(item_id);
                            }
                        });
                        if (window.item_ids.length > 0) {
                            jQuery('#error_response').remove();
                            msgBlock = getProcessBlock("clock_block1");
                            myAdminNoticesAppend('updated', 'Product(s) Export Started..', msgBlock);
                            window.stop_clock = 'no';
                            timer_clock1('clock_block1');
                            window.formSubmitting = false;
                            wooSfExportProduct(window.item_ids.length, window.item_ids.length, 1, 0, 0, 0, 0);
                        } else {
                            alert('Please select products');
                        }
                    }
                } else {
                    window.item_ids = new Array;
                    $("input[name='mergedproduct[]']:checkbox").each(function () {
                        if ($(this).prop('checked') == true) {
                            item_id = $(this).val();
                            window.item_ids.push(item_id);
                        }
                    });
                    if (window.item_ids.length > 0) {
                        jQuery('#error_response').remove();
                        msgBlock = getProcessBlock("clock_block1");
                        myAdminNoticesAppend('updated', 'Product(s) Export Started..', msgBlock);
                        window.stop_clock = 'no';
                        timer_clock1('clock_block1');
                        window.formSubmitting = false;
                        wooSfExportProduct(window.item_ids.length, window.item_ids.length, 1, 0, 0, 0, 0);
                    } else {
                        alert('Please select products');
                    }
                }
            }
            if (selected_value_top == 'importProduct') {
                event.preventDefault();
                if (window.stop_clock == 'no') {
                    if (confirm('Are you sure you want to perform this action. There is already import/export going on..')) {
                        window.item_ids = new Array;
                        $("input[name='mergedproduct[]']:checkbox").each(function () {
                            if ($(this).prop('checked') == true) {
                                item_id = $(this).val();
                                window.item_ids.push(item_id);
                            }
                        });
                        if (window.item_ids.length > 0) {
                            jQuery('#error_response').remove();
                            msgBlock = getProcessBlock("clock_block3");
                            myAdminNoticesAppend('updated', 'Product(s) Import Started..', msgBlock);
                            window.stop_clock = 'no';
                            timer_clock1('clock_block3');
                            window.formSubmitting = false;
                            importSfProducts(window.item_ids.length, window.item_ids.length, 2000, 0, 0, 0);
                        } else {
                            alert('Please select products');
                        }
                    }
                } else {
                    window.item_ids = new Array;
                    $("input[name='mergedproduct[]']:checkbox").each(function () {
                        if ($(this).prop('checked') == true) {
                            item_id = $(this).val();
                            window.item_ids.push(item_id);
                        }
                    });
                    if (window.item_ids.length > 0) {
                        jQuery('#error_response').remove();
                        msgBlock = getProcessBlock("clock_block3");
                        myAdminNoticesAppend('updated', 'Product(s) Import Started..', msgBlock);
                        window.stop_clock = 'no';
                        timer_clock1('clock_block3');
                        window.formSubmitting = false;
                        importSfProducts(window.item_ids.length, window.item_ids.length, 2000, 0, 0, 0);
                    } else {
                        alert('Please select products');
                    }
                }
            }
            if (selected_value_top == 'importCategory') {
                event.preventDefault();
                if (window.stop_clock == 'no') {
                    if (confirm('Are you sure you want to perform this action. There is already import/export going on..')) {
                        window.item_ids = new Array;
                        $("input[name='mergedproduct[]']:checkbox").each(function () {
                            if ($(this).prop('checked') == true) {
                                item_id = $(this).val();
                                window.item_ids.push(item_id);
                            }
                        });
                        if (window.item_ids.length > 0) {
                            jQuery('#error_response').remove();
                            msgBlock = getProcessBlock("clock_block_import_category");
                            myAdminNoticesAppend('updated', 'Product(s) Import Started..', msgBlock);
                            window.stop_clock = 'no';
                            timer_clock1('clock_block_import_category');
                            window.formSubmitting = false;
                            importSfCategories(window.item_ids.length, window.item_ids.length, 2000, 0, 0, 0);
                        } else {
                            alert('Please select categories');
                        }
                    }
                } else {
                    window.item_ids = new Array;
                    $("input[name='mergecategory[]']:checkbox").each(function () {
                        if ($(this).prop('checked') == true) {
                            item_id = $(this).val();
                            window.item_ids.push(item_id);
                        }
                    });
                    if (window.item_ids.length > 0) {
                        jQuery('#error_response').remove();
                        msgBlock = getProcessBlock("clock_block_import_category");
                        myAdminNoticesAppend('updated', 'Product(s) Import Started..', msgBlock);
                        window.stop_clock = 'no';
                        timer_clock1('clock_block_import_category');
                        window.formSubmitting = false;
                        importSfCategories(window.item_ids.length, window.item_ids.length, 2000, 0, 0, 0);
                    } else {
                        alert('Please select categories');
                    }
                }
            }
        } else if ($(this).prop('id') == 'doaction2') {
            if (selected_value_bot == '-1') {
                event.preventDefault();
            }
            if (selected_value_bot == 'exportOrders') {
                event.preventDefault();
                get_checked_order_ids();
            }
            if (selected_value_bot == 'exportUsers') {
                event.preventDefault();
                get_checked_user_ids();
            }
            if (selected_value_bot == 'exportCategory') {
                event.preventDefault();
                get_checked_cat_ids();
            }
            if (selected_value_bot == 'exportProduct') {
                event.preventDefault();
                if (window.stop_clock == 'no') {
                    if (confirm('Are you sure you want to perform this action. There is already import/export going on..')) {
                        window.item_ids = new Array;
                        $("input[name='mergedproduct[]']:checkbox").each(function () {
                            if ($(this).prop('checked') == true) {
                                item_id = $(this).val();
                                window.item_ids.push(item_id);
                            }
                        });
                        if (window.item_ids.length > 0) {
                            jQuery('#error_response').remove();
                            msgBlock = getProcessBlock("clock_block1");
                            myAdminNoticesAppend('updated', 'Product(s) Export Started..', msgBlock);
                            window.stop_clock = 'no';
                            timer_clock1('clock_block1');
                            window.formSubmitting = false;
                            wooSfExportProduct(window.item_ids.length, window.item_ids.length, 1, 0, 0, 0, 0);
                        } else {
                            alert('Please select products');
                        }
                    }
                } else {
                    window.item_ids = new Array;
                    $("input[name='mergedproduct[]']:checkbox").each(function () {
                        if ($(this).prop('checked') == true) {
                            item_id = $(this).val();
                            window.item_ids.push(item_id);
                        }
                    });
                    if (window.item_ids.length > 0) {
                        jQuery('#error_response').remove();
                        msgBlock = getProcessBlock("clock_block1");
                        myAdminNoticesAppend('updated', 'Product(s) Export Started..', msgBlock);
                        window.stop_clock = 'no';
                        timer_clock1('clock_block1');
                        window.formSubmitting = false;
                        wooSfExportProduct(window.item_ids.length, window.item_ids.length, 1, 0, 0, 0, 0);
                    } else {
                        alert('Please select products');
                    }
                }
            }
        }
    });

    $('#woosfrest-export-category-button').on('click', function () {
        if (!window.disablecat) {
            if (confirm('Are you sure you want to Export categories ?')) {
                if (checkOtherClock()) {
                    return true;
                }
                var wrapper = $('#my_wrap');

                if (!wrapper.is('.processing')) {
                    $('.spinner').addClass('is-active');
                    var spin = $('.spinner').html();
                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }

                var synced_ticked = $("#elm_synced_items").prop("checked");
                var unsynced_ticked = $("#elm_unsynced_items").prop("checked");
                var error_ticked = $("#elm_error_items").prop("checked");

                if (synced_ticked && unsynced_ticked) {
                    sync = 'A';
                } else if (synced_ticked) {
                    sync = 'S';
                } else if (unsynced_ticked) {
                    sync = 'U';
                } else if (error_ticked) {
                    sync = 'E';
                } else {
                    sync = 'N';
                }
                msgBlock = getProcessBlock("clock_block_export_category");
                myAdminNoticesAppend('updated', 'Category(ies) Export Started..', msgBlock);
                window.stop_clock = 'no';
                timer_clock1('clock_block_export_category');
                window.disablecat = true;
                getWooCategoryIds(sync);
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('#woosfrest-import-category-button').on('click', function () {
        if (!window.disablecat) {
            var limit = jQuery('#woosfrest-sync-limit').val();
            if(!jQuery.isNumeric(limit)){
                alert('Only numeric value are allowed in batch limit.');
                location.reload();
            }else if(limit>2000){
                alert('Batch limit cannot exceed 2000.');
                location.reload();
            }else if(limit<=0){
                alert('Batch limit cannot be neagtive or 0.');
                location.reload();
            }else{
           
                if (confirm('Are you Sure you want to proceed with Import ?')) {
                    if (checkOtherClock()) {
                        return true;
                    }
                    var wrapper = $('#my_wrap');

                    if (!wrapper.is('.processing')) {
                        $('.spinner').addClass('is-active');
                        var spin = $('.spinner').html();
                        wrapper.addClass('processing').block({
                            message: spin,
                            overlayCSS: {
                                background: '#fff',
                                opacity: 0.6
                            }
                        });
                    }
                    window.disablecat = true;
                    getSObjectCount('webkul_wws__woo_commerce_categories__c');
                } else {
                    location.reload();
                }
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });
    $('#woosfrest-synchronize-category-button').on('click', function () {
        if (!window.disablecat) {
            if (checkOtherClock()) {
                return true;
            }
            window.sync_all = 'yes';
            $('#woosfrest-export-category-button').trigger('click');
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    });

    $('#deletelog').on('click', function () {
        if (confirm('Are you Sure you want to Delete logs older than last seven days?')) {
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: 'getLogFileforDeletion',
                },
                success: function (response) {
                    window.location.search += "&action=logsdeleted"
                }
            })
        }
    });
    $('#datamigration').on('click', function () {
        if (confirm('Are you Sure you want to Migrate data?')) {
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: 'migrateTableData',
                },
                success: function (response) {
                    window.location.search += "&action=datamigration"
                }
            })
        }
    });
});

function getSObjectCount(sObject = 'webkul_wws__woo_commerce_categories__c') {
    if (sObject == 'webkul_wws__woo_commerce_categories__c') {
        timer_clock1('clock_block_import_category');
        msgBlock = getProcessBlock("clock_block_import_category");
        myAdminNoticesAppend('updated', 'Category(ies) Import Started..', msgBlock);
    } else if (sObject == 'Product2') {
        timer_clock1('clock_block3');
        msg_block = getProcessBlock("clock_block3");
        myAdminNoticesAppend('updated', 'Product(s) Import Started..', msgBlock);
    }
    window.stop_clock = 'no';
    window.formSubmitting = true;
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'getSObjectCount',
            sObject: sObject,
        },
        beforeSend: function () {
            if (sObject == 'webkul_wws__woo_commerce_categories__c') {
                myAdminNoticesAppend('updated', 'Category(ies) Import Started..', msgBlock);
            } else if (sObject == 'Product2') {
                myAdminNoticesAppend('updated', 'Product(s) Import Started..', msgBlock);
            }
            jQuery('#error_response').remove();
            jQuery('#woosfrest-import-category-id').addClass('is-active');
        },
        success: function (output) {

            var count = jQuery.parseJSON(output);

            window.formSubmitting = true;
            if (sObject == 'webkul_wws__woo_commerce_categories__c') {
                importSfCategories(count, count, 200, 0, 0, 0);
            } else if (sObject == 'Product2') {
                var limit = 200;
                if ($('#sfwp_limit_import_ch').is(':checked') == true)
                    limit = $('#sfwp_product_limit').val();
                importSfProducts(count, count, limit, 0, 0, 0);
            }
        },
        error: function (xhr) { // if error occured
            window.formSubmitting = false;
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            window.formSubmitting = false;
        }
    });
}

function getWooUserIds(sync) {
    window.user_item_ids = new Array;
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'getWooUserIds',
            sync: sync
        },
        beforeSend: function () {
            jQuery('#synchronizeusers_id').addClass('is-active');
        },
        success: function (output) {
            var obj = jQuery.parseJSON(output);
            for (var i = 0; i < obj.length; i++) {
                item_id = obj[i];
                window.user_item_ids.push(item_id);
            }
            wooSfExportUsers(window.user_item_ids.length, window.user_item_ids.length, 1, 1, 0, 0, 0, 0);
        },
        error: function (xhr) { // if error occured
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            //jQuery('#synchronizeusers_id').removeClass('is-active');
        }
    });
}

function get_checked_order_ids() {
    if (window.stop_clock == 'no') {
        if (!confirm('Import/Export going on.Are you sure you want to perform this action.?')) {
            return true;
        }
    }
    window.order_item_ids = new Array;
    jQuery("input[name='mergedorder[]']:checkbox").each(function () {
        if (jQuery(this).prop('checked') == true) {
            item_id = jQuery(this).val();
            window.order_item_ids.push(item_id);
        }
    });
    if (window.order_item_ids.length > 0) {
        msgBlock = getProcessBlock("clock_block_export_orders");
        myAdminNoticesAppend('updated', 'Order(s) Export Started..', msgBlock);
        window.stop_clock = 'no';
        timer_clock1('clock_block_export_orders');
        wooSfExportOrders(window.order_item_ids.length, window.order_item_ids.length, 1, 1, 0, 0, 0, 0);
    } else {
        alert('Please select orders');
    }
}

function getWooOrderIds(sync) {
    window.order_item_ids = new Array;
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'getWooOrderIds',
            sync: sync
        },
        beforeSend: function () {
            jQuery('#synchronizeorders_id').addClass('is-active');
        },
        success: function (output) {
            var obj = jQuery.parseJSON(output);
            for (var i = 0; i < obj.length; i++) {
                item_id = obj[i];
                window.order_item_ids.push(item_id);
            }
            wooSfExportOrders(window.order_item_ids.length, window.order_item_ids.length, 1, 1, 0, 0, 0, 0);
        },
        error: function (xhr) { // if error occured
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            jQuery('#synchronizeorders_id').removeClass('is-active');
        }
    });
}

function wooSfExportOrders(goal, totalCount, limit, offset, index, updatedValue, addedValue, errorsValue) {
    operationBlock =
        '<span id="current_option_going">\
		<b id="b_added">' + addedValue + ' </b> added / \
        <b id="b_updated">' + updatedValue + '</b>  updated / \
        <b id="b_errors">' + errorsValue + '</b>  errors of \
		<b id="b_total_records">' + goal + '</b> records\
	</span>';
    jQuery('#current_option_going').html(operationBlock);
    if (window.order_item_ids.length > 0) {
        window.formSubmitting = false;
        index += 1;
        order_id = window.order_item_ids[0];
        data = {
            action: 'wooSfExportOrders',
            order_id: order_id,
           
        };
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
            beforeSend: function () {
                jQuery('#synchronizeOrder_id').addClass('is-active');
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('.spinner').addClass('is-active');
                    var spin = $('.spinner').html();
                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
            },
            success: function (output) {
                try {
                    var obj = jQuery.parseJSON(output);
                    if (obj.error != '') {
                        jQuery('#sync_status_icon_' + order_id).attr('title', obj.error);
                        jQuery('#sync_status_icon_' + order_id).css('color', 'red');
                        jQuery('#sync_status_icon_' + order_id).removeClass('dashicons-yes');
                        jQuery('#sync_status_icon_' + order_id).addClass('dashicons-welcome-comments');
                    } else {
                        jQuery('#sync_status_icon_' + order_id).css('color', 'green');
                        jQuery('#sync_status_icon_' + order_id).addClass('dashicons-yes');
                        jQuery('#sync_status_icon_' + order_id).removeClass('dashicons-welcome-comments');
                        jQuery('#sync_status_icon_' + order_id).attr('title', '');
                    }

                    if (obj.total) {
                        cat_id = window.order_item_ids[0];
                        if (obj.updated) {
                            jQuery('#add_sf_sync_time_' + cat_id).html(obj.syncc_time);
                        } else if (obj.added) {
                            jQuery('#add_sf_order_id_' + cat_id).html(obj.sf_order_id);
                            jQuery('#add_sf_opp_id_' + cat_id).html(obj.sf_opp_id);
                            jQuery('#add_sf_sync_time_' + cat_id).html(obj.syncc_time);
                        }
                        totalCount = totalCount - limit;
                        offset = index * limit;
                        percentage = (offset / goal) * 100;
                        if (percentage > 100) {
                            percentage = 100;
                        }
                        jQuery('#completed_b').text(parseInt(percentage));
                        updatedValue = obj.updated + parseInt(jQuery("#b_updated").text());
                        addedValue = obj.added + parseInt(jQuery("#b_added").text());
                        errorsValue = obj.errorValue + parseInt(jQuery("#b_errors").text());
                        window.order_item_ids.shift();
                        wooSfExportOrders(goal, totalCount, limit, offset, index, updatedValue, addedValue, errorsValue);
                    }
                } catch (err) {
                    jQuery('#sync_status_icon_' + order_id).css('color', 'red');
                    jQuery('#sync_status_icon_' + order_id).removeClass('dashicons-yes');
                    jQuery('#sync_status_icon_' + order_id).addClass('dashicons-welcome-comments');
                    jQuery('#sync_status_icon_' + order_id).attr('title', output);
                    jQuery('#synchronizeOrder_id').removeClass('is-active');
                    jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                    window.stop_clock = 'yes';
                    myAdminNoticesAppend('error', 'Error: ', output);
                }
            },
            error: function (xhr) { // if error occured
                window.formSubmitting = true;
                window.stop_clock = 'yes';
                jQuery('#sync_status_icon_' + order_id).css('color', 'red');
                jQuery('#sync_status_icon_' + order_id).removeClass('dashicons-yes');
                jQuery('#sync_status_icon_' + order_id).addClass('dashicons-welcome-comments');
                jQuery('#sync_status_icon_' + order_id).attr('title', xhr.responseText);
                jQuery('.spinner').removeClass('is-active');
                myAdminNoticesAppend('error', 'Error', xhr.responseText);
            },
            complete: function () {
                //jQuery('#woosfrest-export-category-id').removeClass('is-active');
            }
        });
    } else {
        window.formSubmitting = true;
        jQuery('.spinner').removeClass('is-active');
        jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
        window.disableorder = false;
        window.stop_clock = 'yes';
        location.reload();
    }
}

function get_checked_user_ids() {
    if (window.stop_clock == 'no') {
        if (!confirm('Import/Export going on.Are you sure you want to perform this action.?')) {
            return true;
        }
    }
    window.user_item_ids = new Array;
    jQuery("input[name='mergeduser[]']:checkbox").each(function () {
        if (jQuery(this).prop('checked') == true) {
            item_id = jQuery(this).val();
            window.user_item_ids.push(item_id);
        }
    });
    if (window.user_item_ids.length > 0) {
        msgBlock = getProcessBlock("clock_block_export_users");
        myAdminNoticesAppend('updated', 'Users(s) Export Started..', msgBlock);
        window.stop_clock = 'no';
        timer_clock1('clock_block_export_users');
        wooSfExportUsers(window.user_item_ids.length, window.user_item_ids.length, 1, 1, 0, 0, 0, 0);
    } else {
        alert('Please select user');
    }
}

function wooSfExportUsers(goal, totalCount, limit, offset, index, updatedValue, addedValue, errorsValue) {
    operationBlock =
        '<span id="current_option_going">\
		<b id="b_added">' + addedValue + ' </b> added / \
		<b id="b_updated">' + updatedValue + '</b>  updated / \
        <b id="b_errors">' + errorsValue + '</b>  errors of \
		<b id="b_total_records">' + goal + '</b> records\
	</span>';
    jQuery('#current_option_going').html(operationBlock);
    if (window.user_item_ids.length > 0) {
        window.formSubmitting = false;
        index += 1;
        user_id = window.user_item_ids[0];
        data = {
            action: 'wooSfExportUsers',
            user_id: user_id
        };
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
            beforeSend: function () {
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('.spinner').addClass('is-active');
                    var spin = $('.spinner').html();
                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
            },
            success: function (output) {
                try {
                    var obj = jQuery.parseJSON(output);
                    if (obj.error != '') {
                        jQuery('#sync_status_icon_' + user_id).attr('title', obj.error);
                        jQuery('#sync_status_icon_' + user_id).css('color', 'red');
                        jQuery('#sync_status_icon_' + user_id).removeClass('dashicons-yes');
                        jQuery('#sync_status_icon_' + user_id).addClass('dashicons-welcome-comments');
                    } else {
                        jQuery('#sync_status_icon_' + user_id).css('color', 'green');
                        jQuery('#sync_status_icon_' + user_id).addClass('dashicons-yes');
                        jQuery('#sync_status_icon_' + user_id).removeClass('dashicons-welcome-comments');
                        jQuery('#sync_status_icon_' + user_id).attr('title', '');
                    }
                    if (obj.total) {
                        cat_id = window.user_item_ids[0];
                        if (obj.updated) {
                            jQuery('#add_sf_sync_time_' + cat_id).html(obj.syncc_time);
                        } else if (obj.added) {
                            jQuery('#add_sf_user_id_' + cat_id).html(obj.sf_user_id);
                            jQuery('#add_sf_sync_time_' + cat_id).html(obj.syncc_time);
                        }
                        totalCount = totalCount - limit;
                        offset = index * limit;
                        percentage = (offset / goal) * 100;
                        if (percentage > 100) {
                            percentage = 100;
                        }
                        jQuery('#completed_b').text(parseInt(percentage));
                        updatedValue = obj.updated + parseInt(jQuery("#b_updated").text());
                        addedValue = obj.added + parseInt(jQuery("#b_added").text());
                        errorsValue = obj.errorsValue + parseInt(jQuery("#b_errors").text());
                        window.user_item_ids.shift();
                        wooSfExportUsers(goal, totalCount, limit, offset, index, updatedValue, addedValue, errorsValue);
                    }
                } catch (err) {
                    jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                    window.stop_clock = 'yes';
                    myAdminNoticesAppend('error', 'Error', output);
                }
            },
            error: function (xhr) { // if error occured
                window.formSubmitting = true;
                jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                window.stop_clock = 'yes';
                myAdminNoticesAppend('error', 'Error', xhr.responseText);
            },
            complete: function () {
                // jQuery('#woosfrest-export-category-id').removeClass('is-active');
            }
        });
    } else {
        window.formSubmitting = true;
        jQuery('#synchronizeusers_id').removeClass('is-active');
        jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
        window.stop_clock = 'yes';
        window.disableuser = false;
        clearInterval(window.counter_block_id);
        if (window.sync_all == 'yes') {
            window.sync_all = 'no';
        }
        location.reload();
    }
}

function get_checked_cat_ids() {
    if (window.stop_clock == 'no') {
        if (!confirm('Import/Export going on.Are you sure you want to perform this action.?')) {
            return true;
        }
    }
    window.cat_item_ids = new Array;
    jQuery("input[name='mergecategory[]']:checkbox").each(function () {
        if (jQuery(this).prop('checked') == true) {
            item_id = jQuery(this).val();
            window.cat_item_ids.push(item_id);
        }
    });
    if (window.cat_item_ids.length > 0) {
        msgBlock = getProcessBlock("clock_block_export_category");
        myAdminNoticesAppend('updated', 'Category(ies) Export Started..', msgBlock);
        window.stop_clock = 'no';
        timer_clock1('clock_block_export_category');
        wooSfExportCategory(window.cat_item_ids.length, window.cat_item_ids.length, 1, 0, 0, 0, 0);
    } else {
        alert('Please select category');
    }
}

function checkOtherClock() {
    if (window.stop_clock == 'no') {
        if (!confirm('Import/Export going on. Are you sure you want to perform this action?')) {
            return true;
        }
    }
    return false;
}

function getWooCategoryIds(sync) {
    window.cat_item_ids = new Array;
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'getWooCategoryIds',
            sync: sync
        },
        beforeSend: function () {
            // jQuery('#error_response').remove();
            // jQuery('#synchronizeproduct_id').addClass('is-active');
        },
        success: function (output) {
            var obj = jQuery.parseJSON(output);
            for (var i = 0; i < obj.length; i++) {
                item_id = obj[i];
                window.cat_item_ids.push(item_id);
            }
            wooSfExportCategory(window.cat_item_ids.length, window.cat_item_ids.length, 1, 0, 0, 0, 0);
        },
        error: function (xhr) { // if error occured
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            jQuery('#synchronizeproduct_id').removeClass('is-active');
        }
    });
}

function getWooProductIds(sync) {
    window.item_ids = new Array;
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'getWooProductIds',
            sync: sync
        },
        beforeSend: function () {
            // jQuery('#error_response').remove();
            // jQuery('#synchronizeproduct_id').addClass('is-active');
        },
        success: function (output) {
            var obj = jQuery.parseJSON(output);
            for (var i = 0; i < obj.length; i++) {
                item_id = obj[i];
                window.item_ids.push(item_id);
            }
            wooSfExportProduct(window.item_ids.length, window.item_ids.length, 1, 0, 0, 0, 0);
        },
        error: function (xhr) { // if error occured
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            //jQuery('#synchronizeproduct_id').removeClass('is-active');
        }
    });
}

function getProcessBlock(clockBlockId) {
    msgBlock = '<span id="wpsf_notice_block">';
    msgBlock += '<br>';
    msgBlock += '<span id="completed_b">0</span>% completed';
    msgBlock += '<br>';
    msgBlock += '<span id="current_option_going">Fetching total records...</span>';
    msgBlock += '<hr>';
    msgBlock += 'Time Elapsed: <span id="' + clockBlockId + '">00:00:00</span>';
    msgBlock += '<br>';
    msgBlock += '<span id="wpsf_end_notice"></span>';
    msgBlock += '</span>';
    return msgBlock;
}

function wooSfExportProduct(goal, totalCount, limit, index, updatedValue, addedValue, errorsValue) {
    operationBlock =
        '<span id="current_option_going">\
		<b id="b_added">' + addedValue + ' </b> added / \
        <b id="b_updated">' + updatedValue + '</b>  updated / \
        <b id="b_errors">' + errorsValue + '</b>  errors of \
		<b id="b_total_records">' + goal + '</b> records\
	</span>';
    jQuery('#current_option_going').html(operationBlock);
    if (window.item_ids.length > 0) {
        jQuery('#woosfrest-exportproduct-id').addClass('is-active');
        index += 1;
        product_id = window.item_ids[0];
        wp_ajax = jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: 'wooSfExportProduct',
                product_id: product_id
            },
            beforeSend: function () {
                window.formSubmitting = false;
                if (jQuery('#wpsf_notice_block li').length > 5) {
                    jQuery('#wpsf_notice_block li').first().slideUp('slow');
                }
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('#woosfrest-importproduct-id').addClass('is-active');
                    var spin = $('#woosfrest-importproduct-id').html();

                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }

            },
            success: function (output) {
                try {
                    var obj = jQuery.parseJSON(output);
                    if (obj.error != '') {
                        jQuery('#sync_status_icon_' + product_id).attr('title', obj.error);
                        jQuery('#sync_status_icon_' + product_id).css('color', 'red');
                        jQuery('#sync_status_icon_' + product_id).removeClass('dashicons-yes');
                        jQuery('#sync_status_icon_' + product_id).addClass('dashicons-welcome-comments');
                    } else {
                        jQuery('#sync_status_icon_' + product_id).css('color', 'green');
                        jQuery('#sync_status_icon_' + product_id).addClass('dashicons-yes');
                        jQuery('#sync_status_icon_' + product_id).removeClass('dashicons-welcome-comments');
                        jQuery('#sync_status_icon_' + product_id).attr('title', '');
                    }
                    if (obj.total) {
                        product_id = window.item_ids[0];
                        if (obj.updated) {
                            jQuery('#add_sf_sync_time_' + product_id).html(obj.syncc_time);
                        } else if (obj.added) {
                            jQuery('#add_sf_product_id_' + product_id).html(obj.sf_product_id);
                            jQuery('#add_sf_sync_time_' + product_id).html(obj.syncc_time);
                        }
                        totalCount = totalCount - limit;
                        percentage = ((index * limit) / goal) * 100;
                        if (percentage > 100) {
                            percentage = 100;
                        }
                        jQuery('#completed_b').text(parseInt(percentage));
                        updatedValue = obj.updated + parseInt(jQuery("#b_updated").text());
                        addedValue = obj.added + parseInt(jQuery("#b_added").text());
                        errorsValue = obj.errorsValue + parseInt(jQuery("#b_errors").text());
                        window.item_ids.shift();
                        wooSfExportProduct(goal, totalCount, limit, index, updatedValue, addedValue, errorsValue);
                    }
                } catch (err) {
                    jQuery('#woosfrest-importproduct-id').removeClass('is-active');
                    window.formSubmitting = true;
                    jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                    window.stop_clock = 'yes';
                    myAdminNoticesAppend('error', 'Error', output);
                    // wp_ajax.abort();
                }
            },
            error: function (xhr) { // if error occured
                jQuery('#woosfrest-importproduct-id').removeClass('is-active');
                window.formSubmitting = true;
                jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                window.stop_clock = 'yes';
                myAdminNoticesAppend('error', 'Error', xhr.responseText);
            },
            complete: function () {
                // jQuery('#woosfrest-importproduct-id').removeClass('is-active');
                // wooSfExportProduct();
            }
        });
    } else {
        window.formSubmitting = true;
        jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
        window.stop_clock = 'yes';
        clearInterval(window.counter_block_id);
        window.disableprod = false;
        if (window.sync_all == 'yes') {
            window.sync_all = 'no';
            jQuery('#woosfrest-importproduct-button').trigger('click');
        } else {
            jQuery('.spinner').removeClass('is-active');
            location.reload();
        }
    }
}

function importSfCategories(goal, totalCount, limit, index, updatedVal, addedVal) {
    operationBlock =
        '<span id="current_option_going">\
		<b id="b_added">' + addedVal + ' </b> added / \
		<b id="b_updated">' + updatedVal + '</b>  updated of \
		<b id="b_total_records">' + goal + '</b> records\
	</span>';
    jQuery('#current_option_going').html(operationBlock);
    if (totalCount > 0) {
        window.formSubmitting = false;
        index += 1;
        var data = {
            action: 'importSfCategories',
            limit: limit,
            locator: window.locator,
        };
        if (window.item_ids != undefined) {
            data['cat_id'] = window.item_ids;
        }
        if (jQuery('#woosfrest-sync-limit-check').is(":checked")) {
            if (jQuery.isNumeric(jQuery('#woosfrest-sync-limit').val())) {
                data["limit"] = jQuery('#woosfrest-sync-limit').val();
                limit = data["limit"];
            }
        }
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
            beforeSend: function () {
                // jQuery('#woosfrest-import-category-id').addClass('is-active');
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('.spinner').addClass('is-active');
                    var spin = $('.spinner').html();
                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
            },
            success: function (output) {
                try {
                    obj = jQuery.parseJSON(output);
                    var tempCategoryId = obj.cat_id;
                    if (obj.error != '') {
                        window.locator = obj.locator;
                        jQuery('#sync_status_icon_' + tempCategoryId).attr('title', obj.error);
                        jQuery('#sync_status_icon_' + tempCategoryId).css('color', 'red');
                        jQuery('#sync_status_icon_' + tempCategoryId).removeClass('dashicons-yes');
                        jQuery('#sync_status_icon_' + tempCategoryId).addClass('dashicons-welcome-comments');
                    } else {
                        window.locator = obj.locator;
                        jQuery('#sync_status_icon_' + tempCategoryId).css('color', 'green');
                        jQuery('#sync_status_icon_' + tempCategoryId).addClass('dashicons-yes');
                        jQuery('#sync_status_icon_' + tempCategoryId).removeClass('dashicons-welcome-comments');
                        jQuery('#sync_status_icon_' + tempCategoryId).attr('title', '');
                        jQuery("#add_sf_cat_id_" + tempCategoryId).html(obj.sf_cat_id);
                    }
                    totalCount = totalCount - limit;
                    percentage = ((index * limit) / goal) * 100;
                    if (percentage > 100) {
                        percentage = 100;
                    }
                    jQuery('#completed_b').text(parseInt(percentage));
                    updatedVal = obj.updated + parseInt(jQuery("#b_updated").text());
                    addedVal = obj.added + parseInt(jQuery("#b_added").text());
                    importSfCategories(goal, totalCount, limit, index, updatedVal, addedVal);
                } catch (e) {
                    myAdminNoticesAppend('error', 'Error', output);
                }
            },
            error: function (xhr) { // if error occured
                window.formSubmitting = true;
                myAdminNoticesAppend('error', 'Error', xhr.responseText);
            },
            complete: function () {
                window.formSubmitting = true;
                //jQuery('#woosfrest-import-category-id').removeClass('is-active');
            }
        });
    } else {
        window.formSubmitting = true;
        jQuery('.spinner').removeClass('is-active');
        window.disablecat = false;
        jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
        window.stop_clock = 'yes';
        location.reload();
    }
}

function importSfProducts(goal, totalCount, limit, index, updatedValue, addedValue) {

    operationBlock =
        '<span id="current_option_going">\
		<b id="b_added">' + addedValue + ' </b> added /\
		<b id="b_updated">' + updatedValue + '</b>  updated of \
		<b id="b_total_records">' + goal + '</b> records\
	</span>';
    jQuery('#current_option_going').html(operationBlock);
    if (totalCount > -1) {
        window.formSubmitting = false;
        index += 1;
        var data = {
            action: 'importSfProducts',
            limit: limit,
            locator: window.locator,
        };
        if (window.item_ids != undefined) {
            data['product_id'] = window.item_ids;
        }
        if (jQuery('#sfwp_limit_import_ch').is(":checked")) {
            if (jQuery.isNumeric(jQuery('#sfwp_product_limit').val())) {
                data["limit"] = jQuery('#sfwp_product_limit').val();
                limit = data["limit"];
            }
        }

        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
            beforeSend: function () {
                window.formSubmitting = false;
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('#woosfrest-importproduct-id').addClass('is-active');
                    var spin = $('#woosfrest-importproduct-id').html();

                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
            },
            success: function (output) {
                try {
                    obj = jQuery.parseJSON(output);
                    window.locator = obj.locator;
                    window.saleforceConnection = obj.saleforceConnection;
                    totalCount = totalCount - limit;
                    percentage = ((index * limit) / goal) * 100;
                    if (percentage > 100) {
                        percentage = 100;
                    }
                    jQuery('#completed_b').text(parseInt(percentage));
                    updatedValue = obj.updated + parseInt(jQuery("#b_updated").text());
                    addedValue = obj.added + parseInt(jQuery("#b_added").text());
                    // window.formSubmitting = true;
                    importSfProducts(goal, totalCount, limit, index, updatedValue, addedValue);
                } catch (e) {
                    myAdminNoticesAppend('error', 'Error', output);
                }
            },
            error: function (xhr) { // if error occured
                // window.formSubmitting = true;
                myAdminNoticesAppend('error', 'Error', xhr.responseText);
                window.formSubmitting = true;
                jQuery('#woosfrest-importproduct-id').removeClass('is-active');
                jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
                window.stop_clock = 'yes';
            },
            complete: function () {

            }
        });
    } else {
        window.formSubmitting = true;
        jQuery('.spinner').removeClass('is-active');
        jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
        window.stop_clock = 'yes';
        window.disableprod = false;
        location.reload();
    }
}

function timer_clock1(block_id) {
    var count = 0;
    window.counter_block_id = setInterval(timer_clock, 1000);

    function timer_clock() {
        count = count + 1;
        if (window.stop_clock == 'yes') {
            clearInterval(window.counter_block_id);
            return;
        }
        var seconds = count % 60;
        var minutes = Math.floor(count / 60);
        var hours = Math.floor(minutes / 60);
        minutes %= 60;
        hours %= 60;
        if (hours < 10) {
            hours = '0' + hours;
        }
        if (minutes < 10) {
            minutes = '0' + minutes;
        }
        if (seconds < 10) {
            seconds = '0' + seconds;
        }
        jQuery('#' + block_id).html(hours + ":" + minutes + ":" + seconds);
    }
}

function synchronizeCategory() {
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'synchronizeCategory'
        },
        beforeSend: function () {
            jQuery('#error_response').remove();
            jQuery('#woosfrest-synchronize-category-id').addClass('is-active');
        },
        success: function (output) {
            myAdminNoticesAppend('updated', 'Notice', '<br>Category Syncronization successfully completed');
        },
        error: function (xhr) { // if error occured
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            jQuery('#woosfrest-synchronize-category-id').removeClass('is-active');
        }
    });
}

function wooSfExportCategory(goal, totalCount, limit, index, updatedVal, addedVal, errorsValue) {
    operationBlock =
        '<span id="current_option_going">\
		<b id="b_added">' + addedVal + '</b> added / <b id="b_updated">' + updatedVal + '</b>  updated / <b id="b_errors">' + errorsValue + '</b>  errors of \<b id="b_total_records">' + goal + '</b> records\
	</span>';
    jQuery('#current_option_going').html(operationBlock);
    if (window.cat_item_ids.length > 0) {
        window.formSubmitting = false;
        index += 1;
        cat_id = window.cat_item_ids[0];
        data = {
            action: 'wooSfExportCategory',
            cat_id: cat_id
        };
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
            beforeSend: function () {
                jQuery('#woosfrest-export-category-id').addClass('is-active');
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('.spinner').addClass('is-active');
                    var spin = $('.spinner').html();
                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
            },
            success: function (output) {
                try {
                    var obj = jQuery.parseJSON(output);
                    if (obj.error != '') {
                        jQuery('#sync_status_icon_' + cat_id).attr('title', obj.error);
                        jQuery('#sync_status_icon_' + cat_id).css('color', 'red');
                        jQuery('#sync_status_icon_' + cat_id).removeClass('dashicons-yes');
                        jQuery('#sync_status_icon_' + cat_id).addClass('dashicons-welcome-comments');
                    } else {
                        jQuery('#sync_status_icon_' + cat_id).css('color', 'green');
                        jQuery('#sync_status_icon_' + cat_id).addClass('dashicons-yes');
                        jQuery('#sync_status_icon_' + cat_id).removeClass('dashicons-welcome-comments');
                        jQuery('#sync_status_icon_' + cat_id).attr('title', '');
                    }
                    if (obj.total) {
                        cat_id = window.cat_item_ids[0];
                        if (obj.updated) {
                            jQuery('#add_sf_sync_time_' + cat_id).html(obj.syncc_time);
                        } else if (obj.added) {
                            jQuery('#add_sf_cat_id_' + cat_id).html(obj.sf_category_id);
                            jQuery('#add_sf_sync_time_' + cat_id).html(obj.syncc_time);
                        }
                        totalCount = totalCount - limit;
                        percentage = ((index * limit) / goal) * 100;
                        if (percentage > 100) {
                            percentage = 100;
                        }
                        jQuery('#completed_b').text(parseInt(percentage));
                        updatedVal = obj.updated + parseInt(jQuery("#b_updated").text());
                        addedVal = obj.added + parseInt(jQuery("#b_added").text());
                        errorsValue = obj.errorsValue + parseInt(jQuery("#b_errors").text());
                        window.cat_item_ids.shift();
                        wooSfExportCategory(goal, totalCount, limit, index, updatedVal, addedVal, errorsValue);
                    }
                } catch (err) {
                    jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                    window.stop_clock = 'yes';
                    myAdminNoticesAppend('error', 'Error', output);
                }
            },
            error: function (xhr) { // if error occured
                myAdminNoticesAppend('error', 'Error', xhr.responseText);
            },
            complete: function () {
                // jQuery('#woosfrest-export-category-id').removeClass('is-active');
            }
        });
    } else {
        window.formSubmitting = true;
        jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
        window.stop_clock = 'yes';
        clearInterval(window.counter_block_id);
        window.disablecat = false;
        if (window.sync_all == 'yes') {
            window.sync_all = 'no';
            jQuery('#woosfrest-import-category-button').trigger('click');
        } else {
            jQuery('.spinner').removeClass('is-active');
            location.reload();
        }
    }
}

function demoSync() {
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'demoSync'
        },
        beforeSend: function () {
            jQuery('#error_response').remove();
            jQuery('#demoSync_id').addClass('is-active');
        },
        success: function (output) {
            var obj = jQuery.parseJSON(output);
        },
        error: function (xhr) { // if error occured
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            jQuery('#demoSync_id').removeClass('is-active');
        }
    });
}

function synchronizeusers() {
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'synchronizeUsers'
        },
        beforeSend: function () {
            jQuery('#error_response').remove();
            jQuery('#synchronizeusers_id').addClass('is-active');
        },
        success: function (output) {
            var obj = jQuery.parseJSON(output);
            var st_notice = '';
            if (obj.total) {
                st_notice += '<br>Users Synchronized Successfully';
                st_notice += '<br>Items addedd = ' + obj.added;
                st_notice += '<br>Items updated = ' + obj.updated;
                st_notice += '<br>Total items processed = ' + obj.total;
            }
            if (st_notice) {
                myAdminNoticesAppend('updated', 'Notice', st_notice);
            }
        },
        error: function (xhr) { // if error occured
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            jQuery('#synchronizeusers_id').removeClass('is-active');
        }
    });
}

function myAdminNoticesAppend(type, title, message) {
    var a =
        '<div class="' + type + ' notice is-dismissible" id="error_response">\
		<p> <strong>' + title + ' </strong> ' + message + '</p>\
		<button class="notice-dismiss" type="button">\
			<span class="screen-reader-text">Dismiss this notice.</span>\
		</button>\
	</div>';
    jQuery('#my_wrap').prepend(a);
}

//Bulk API Code Start

$('#woosfrest-bulksynchronize-category-button').on('click', function () {
    if (!window.disablecat) {
        if (checkOtherClock()) {
            return true;
        }
        window.sync_all = 'yes';
        if (confirm("Are you sure you want to bulk Export categories?")) {
            if (checkOtherClock()) {
                return true;
            }
            var wrapper = $('#my_wrap');
            if (!wrapper.is('.processing')) {
                $('.spinner').addClass('is-active');
                var spin = $('.spinner').html();
                wrapper.addClass('processing').block({
                    message: spin,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            }
            msgBlock = getProcessBlock("clock_block_export_category");
            myAdminNoticesAppend('updated', 'Category(ies) Export Started..', msgBlock);
            window.stop_clock = 'no';
            timer_clock1('clock_block_export_category');
            window.disablecat = true;
            getWooBulkCategoryIds();
        }
    } else {
        alert('Cannot start new Sync process while another is going on');
    }
});

$('#bulksynchronizeproduct_button').on('click', function () {
    if (!window.disablecat) {
        if (checkOtherClock()) {
            return true;
        }
        window.sync_all = 'yes';
        if (confirm("Are you sure you want to perform bulk Export ?")) {
            if (checkOtherClock()) {
                return true;
            }
            var wrapper = $('#my_wrap');
            if (!wrapper.is('.processing')) {
                $('.spinner').addClass('is-active');
                var spin = $('.spinner').html();
                wrapper.addClass('processing').block({
                    message: spin,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            }
            msgBlock = getProcessBlock("clock_block2");
            myAdminNoticesAppend('updated', 'Product(s) Export Started..', msgBlock);
            window.stop_clock = 'no';
            timer_clock1('clock_block2');
            getWooBulkProductIds();
        } else {
            location.reload();
        }
    } else {
        alert('Cannot start new Sync process while another is going on');
    }
});

$('#bulksynchronizeusers_button').on('click', function () {
    if (confirm('Are you sure you want to perform  bulk Export ?')) {
        var allowSyncAll = false;
        if (!window.disableuser) {
            if (window.stop_clock == 'no') {
                if (confirm("Are you sure you want to perform this action")) {
                    allowSyncAll = true;
                }
            } else {
                allowSyncAll = true;
            }
            if (allowSyncAll) {
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('.spinner').addClass('is-active');
                    var spin = $('.spinner').html();
                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
                jQuery('#error_response').remove();
                msgBlock = getProcessBlock("clock_block_sync_users");
                myAdminNoticesAppend('updated', 'User(s) Export Started..', msgBlock);
                window.stop_clock = 'no';
                timer_clock1('clock_block_sync_users');
                window.disableuser = true;
                getWooBulkUserIds();
                //$('#wwsexportuser_button').trigger('click');
            }
        } else {
            alert('Cannot start new Sync process while another is going on');
        }
    }
});

function getWooBulkUserIds() {
    //var selected_role = document.getElementById('selected_role').value;
    window.user_item_ids = new Array;
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'getWooUserIds',
            sync: 'A',
        },
        beforeSend: function () {
            jQuery('#synchronizeusers_id').addClass('is-active');
        },
        success: function (output) {
            var obj = jQuery.parseJSON(output);
            for (var i = 0; i < obj.length; i++) {
                item_id = obj[i];
                window.user_item_ids.push(item_id);
            }
            wooSfExportBulkUsers(window.user_item_ids.length, window.user_item_ids.length, 0, 0, 0);
        },
        error: function (xhr) { // if error occured
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            //jQuery('#synchronizeusers_id').removeClass('is-active');
        }
    });
}

function getWooBulkCategoryIds() {
    window.cat_item_ids = new Array;
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'getBulkWooCategoryIds',
        },
        success: function (output) {
            var obj = jQuery.parseJSON(output);
            for (var i = 0; i < obj.length; i++) {
                item_data = obj[i];
                window.cat_item_ids.push(item_data);
            }
            wooSfExportBulkCategory(window.cat_item_ids.length, window.cat_item_ids.length, 0, 0, 0, 0);
        },
        error: function (xhr) { // if error occured
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            //window.stop_clock = 'yes';
            jQuery('#synchronizeproduct_id').removeClass('is-active');
        }
    });
}

function getWooBulkProductIds() {
    window.item_ids = new Array;
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'getWooBulkProductIds',
        },
        beforeSend: function () {
            // jQuery('#error_response').remove();
            // jQuery('#synchronizeproduct_id').addClass('is-active');
        },
        success: function (output) {
            var obj = jQuery.parseJSON(output);
            for (var i = 0; i < obj.length; i++) {
                item_id = obj[i];
                window.item_ids.push(item_id);
            }
            wooSfExportBulkProduct(window.item_ids.length, window.item_ids.length, 0, 0, 0);
        },
        error: function (xhr) { // if error occured
            myAdminNoticesAppend('error', 'Error', xhr.responseText);
        },
        complete: function () {
            //jQuery('#synchronizeproduct_id').removeClass('is-active');
        }
    });
}

function wooSfExportBulkProduct(goal, totalCount, updatedValue, addedValue, errorsValue) {
    operationBlock =
        '<span id="current_option_going">\
		<b id="b_added">' + addedValue + ' </b> added / \
        <b id="b_updated">' + updatedValue + '</b>  updated / \
        <b id="b_errors">' + errorsValue + '</b>  errors of \
		<b id="b_total_records">' + goal + '</b> records\
	</span>';
    jQuery('#current_option_going').html(operationBlock);
    if (totalCount > 0) {
        jQuery('#woosfrest-exportproduct-id').addClass('is-active');
        wp_ajax = jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: 'wooSfExportBulkProduct',
                product_id: window.item_ids
            },
            beforeSend: function () {
                window.formSubmitting = false;
                if (jQuery('#wpsf_notice_block li').length > 5) {
                    jQuery('#wpsf_notice_block li').first().slideUp('slow');
                }
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('#woosfrest-importproduct-id').addClass('is-active');
                    var spin = $('#woosfrest-importproduct-id').html();

                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }

            },
            success: function (output) {
                try {
                    var obj = jQuery.parseJSON(output);
                    if (obj.total) {
                        totalCount = -1;
                        percentage = 100;
                        jQuery('#completed_b').text(parseInt(percentage));
                        updatedValue = obj.updated + parseInt(jQuery("#b_updated").text());
                        addedValue = obj.added + parseInt(jQuery("#b_added").text());
                        errorsValue = obj.errorsValue + parseInt(jQuery("#b_errors").text());
                        window.item_ids.shift();
                        wooSfExportBulkProduct(goal, totalCount, updatedValue, addedValue, errorsValue);
                    }
                } catch (err) {
                    jQuery('#woosfrest-importproduct-id').removeClass('is-active');
                    window.formSubmitting = true;
                    jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                    window.stop_clock = 'yes';
                    myAdminNoticesAppend('error', 'Error', output);
                    // wp_ajax.abort();
                }
            },
            error: function (xhr) { // if error occured
                jQuery('#woosfrest-importproduct-id').removeClass('is-active');
                window.formSubmitting = true;
                jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                window.stop_clock = 'yes';
                myAdminNoticesAppend('error', 'Error', xhr.responseText);
            },
            complete: function () {
                // jQuery('#woosfrest-importproduct-id').removeClass('is-active');
                // wooSfExportProduct();
            }
        });
    } else {
        window.formSubmitting = true;
        jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
        window.stop_clock = 'yes';
        clearInterval(window.counter_block_id);
        window.disableprod = false;
        jQuery('.spinner').removeClass('is-active');
        location.reload();
    }
}

function wooSfExportBulkCategory(goal, totalCount, index, updatedVal, addedVal, errorsValue) {
    operationBlock =
        '<span id="current_option_going">\
		<b id="b_added">' + addedVal + '</b> added / <b id="b_updated">' + updatedVal + '</b>  updated / <b id="b_errors">' + errorsValue + '</b>  errors of \<b id="b_total_records">' + goal + '</b> records\
	</span>';
    jQuery('#current_option_going').html(operationBlock);
    if (totalCount > 0) {
        window.formSubmitting = false;
        index += 1;
        cat_id = window.cat_item_ids;
        data = {
            action: 'wooSfExportBulkCategory',
            cat_id: cat_id
        };
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
            beforeSend: function () {
                jQuery('#woosfrest-export-category-id').addClass('is-active');
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('.spinner').addClass('is-active');
                    var spin = $('.spinner').html();
                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
            },
            success: function (output) {
                try {
                    var obj = jQuery.parseJSON(output);
                    if (obj.total) {
                        totalCount = -1;
                        percentage = 100;
                        jQuery('#completed_b').text(parseInt(percentage));
                        updatedVal = obj.updated + parseInt(jQuery("#b_updated").text());
                        addedVal = obj.added + parseInt(jQuery("#b_added").text());
                        errorsValue = obj.errorsValue + parseInt(jQuery("#b_errors").text());
                        failedRecords = obj.failed + parseInt(jQuery("#b_failed").text());

                        wooSfExportBulkCategory(goal, totalCount, index, updatedVal, addedVal, errorsValue);
                    }
                } catch (err) {
                    jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                    window.stop_clock = 'yes';
                    myAdminNoticesAppend('error', 'Error', output);
                }
            },
            error: function (xhr) { // if error occured
                myAdminNoticesAppend('error', 'Error', xhr.responseText);
            },
            complete: function () {
                // jQuery('#woosfrest-export-category-id').removeClass('is-active');
            }
        });
    } else {
        window.formSubmitting = true;
        jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
        window.stop_clock = 'yes';
        clearInterval(window.counter_block_id);
        window.disablecat = false;
        jQuery('.spinner').removeClass('is-active');
        location.reload();
    }
}

function wooSfExportBulkUsers(goal, totalCount, updatedValue, addedValue, errorsValue) {
    operationBlock =
        '<span id="current_option_going">\
		<b id="b_added">' + addedValue + ' </b> added / \
		<b id="b_updated">' + updatedValue + '</b>  updated / \
        <b id="b_errors">' + errorsValue + '</b>  errors of \
		<b id="b_total_records">' + goal + '</b> records\
	</span>';
    jQuery('#current_option_going').html(operationBlock);
    if (totalCount > 0) {
        window.formSubmitting = false;
        user_ids = window.user_item_ids;
        data = {
            action: 'wooSfExportBulkUsers',
            user_id: user_ids
        };
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
            beforeSend: function () {
                //jQuery('#synchronizeusers_id').addClass('is-active');
                var wrapper = $('#my_wrap');
                if (!wrapper.is('.processing')) {
                    $('.spinner').addClass('is-active');
                    var spin = $('.spinner').html();
                    wrapper.addClass('processing').block({
                        message: spin,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
            },
            success: function (output) {
                try {
                    var obj = jQuery.parseJSON(output);
                    if (obj.total) {
                        totalCount = -1;
                        percentage = 100;
                        jQuery('#completed_b').text(parseInt(percentage));
                        updatedVal = obj.updated + parseInt(jQuery("#b_updated").text());
                        addedVal = obj.added + parseInt(jQuery("#b_added").text());
                        errorsValue = obj.errorsValue + parseInt(jQuery("#b_errors").text());

                        wooSfExportBulkUsers(goal, totalCount, updatedVal, addedVal, errorsValue);
                    }
                } catch (err) {
                    jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                    window.stop_clock = 'yes';
                    myAdminNoticesAppend('error', 'Error', output);
                }
            },
            error: function (xhr) { // if error occured
                window.formSubmitting = true;
                jQuery('#wpsf_end_notice').append("<mark class='incomplete tips' style='background-color:red;color:white;'>Error Occured</mark>");
                window.stop_clock = 'yes';
                myAdminNoticesAppend('error', 'Error', xhr.responseText);
            },
            complete: function () {
                // jQuery('#woosfrest-export-category-id').removeClass('is-active');
            }
        });
    } else {
        window.formSubmitting = true;
        jQuery('#synchronizeusers_id').removeClass('is-active');
        jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
        window.stop_clock = 'yes';
        window.disableuser = false;
        clearInterval(window.counter_block_id);
        if (window.sync_all == 'yes') {
            window.sync_all = 'no';
        }
        location.reload();
    }
}

function cancelBackgroundJob(objectType){

    if(confirm('Are you sure you want to cancel the bakground job for '+objectType+' ?')){
        
        return true;                
    }else{
        return false; 
    }
}