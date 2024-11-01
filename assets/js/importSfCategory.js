function importSfCategory(goal, totalCount, limit, offset, index, updatedVal, addedVal) {
	operation_block =
		'<span id="current_option_going">\
		<b id="b_added">' + addedVal + ' </b> added / \
		<b id="b_updated">' + updatedVal + '</b>  updated of \
		<b id="b_total_records">' + goal + '</b> records\
	</span>';
	jQuery('#current_option_going').html(operation_block);
	if (totalCount > 0) {
		window.formSubmitting = false;
		index += 1;
		var data = {
			action: 'importSfCategory',
			limit: limit,
			offset: offset,
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
				jQuery('#woosfrest-import-category-id').addClass('is-active');
			},
			success: function (output) {
				try {
					obj = jQuery.parseJSON(output);
					var cat_idtemp = obj.cat_id;
					if (obj.error != '') {
						window.locator = obj.locator;
						jQuery('#sync_status_icon_' + cat_idtemp).attr('title', obj.error);
						jQuery('#sync_status_icon_' + cat_idtemp).css('color', 'red');
						jQuery('#sync_status_icon_' + cat_idtemp).removeClass('dashicons-yes');
						jQuery('#sync_status_icon_' + cat_idtemp).addClass('dashicons-welcome-comments');
					}
					else {
						jQuery('#sync_status_icon_' + cat_idtemp).css('color', 'green');
						jQuery('#sync_status_icon_' + cat_idtemp).addClass('dashicons-yes');
						jQuery('#sync_status_icon_' + cat_idtemp).removeClass('dashicons-welcome-comments');
						jQuery('#sync_status_icon_' + cat_idtemp).attr('title', '');
						jQuery("#add_sf_cat_id_" + cat_idtemp).html(obj.sf_cat_id);
					}
					totalCount = totalCount - limit;
					offset = index * limit;
					percentage = (offset / goal) * 100;
					if (percentage > 100) {
						percentage = 100;
					}
					jQuery('#completed_b').text(parseInt(percentage));
					updatedVal = obj.updated + parseInt(jQuery("#b_updated").text());
					addedVal = obj.added + parseInt(jQuery("#b_added").text());
					importSfCategory(goal, totalCount, limit, offset, index, updatedVal, addedVal);
				}
				catch (e) {
					myAdminNoticesAppend('error', 'Error', output);
				}
			},
			error: function (xhr) {
				window.formSubmitting = true;
				myAdminNoticesAppend('error', 'Error', xhr.responseText);
			},
			complete: function () {
				window.formSubmitting = true;
			}
		});
	}
	else {
		window.formSubmitting = true;
		jQuery('#woosfrest-import-category-id').removeClass('is-active');
		window.disablecat = false;
		jQuery('#wpsf_end_notice').append("<mark class='completed tips'>Completed</mark>");
		window.stop_clock = 'yes';
	}
}
