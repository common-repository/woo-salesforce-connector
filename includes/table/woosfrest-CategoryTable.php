<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class wooSfRestCategoryTable extends WP_List_Table
{
    private function tableData()
    {
        global $wpdb;
        $tableName1 = $wpdb->prefix . 'termmeta';
        $tableName2 = $wpdb->prefix . 'terms';
        $data   = array();
        $search = '';
        $itemType = isset($_REQUEST['item_type']) ? $_REQUEST['item_type'] : 'A';
        $currentPage = $this->get_pagenum();
        $user = get_current_user_id();
        $screen = get_current_screen();
        $option = $screen->get_option('per_page', 'option');

        $postsPerPage = $this->get_items_per_page($option, 5);
        $start = ($currentPage - 1) * $postsPerPage;

        $vars = $_REQUEST;
        if (isset($_REQUEST['s'])) {
            $search = ($_REQUEST['s']);
        }
        $args = array(
            'taxonomy'   => "product_cat",
            'offset'    => $start,
            'number'    => $postsPerPage,
            'hide_empty' => false,
            'search' => !empty($_REQUEST['s']) ? $_REQUEST['s'] : ''
        );

        $itemType = isset($_REQUEST['item_type']) ? $_REQUEST['item_type'] : 'A';
        if (isset($itemType)) {
            if ($itemType == 'U') {
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'wk_sf_category_id',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key'     => 'wk_sf_category_error',
                        'compare' => 'NOT EXISTS'
                    ),
                );
            }
            if ($itemType == 'E') {
                $args['meta_query'] = array(
                    'key'     => 'wk_sf_category_error',
                    'compare' => 'EXISTS'
                );
            }
            if ($itemType == 'S') {
                $args['meta_query'] = array(
                    array(
                        'key' => 'wk_sf_category_id',
                        'compare' => 'EXISTS'
                    ),

                );
            }
        }

        // Sorting
        if (isset($vars['orderby'])) {
            $args['orderby'] = $vars['orderby'];
        }
        if (isset($vars['order'])) {
            if ('asc' == $vars['order'])
                $args['order'] = 'ASC';

            if ('desc' == $vars['order'])
                $args['order'] = 'DESC';
        }
        $totalCategoryCount = count(get_terms([
            'taxonomy'   => "product_cat", 'hide_empty' => false,
            'search' => !empty($_REQUEST['s']) ? $_REQUEST['s'] : ''
        ]));

        $syncedItemsArg = array(
            'taxonomy'   => "product_cat",
            'hide_empty' => false,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key' => 'wk_sf_category_id',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'wk_sf_category_id',
                    'compare' => '!=',
                    'value' => NULL
                ),
            )
        );
        $syncedItems = count(get_terms($syncedItemsArg));

        $ErrorItemsArg = array(
            'taxonomy'   => "product_cat",
            'hide_empty' => false,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'wk_sf_category_error',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'wk_sf_category_error',
                    'compare' => '!=',
                    'value' => NULL
                )
            )
        );
        $errorItems = count(get_terms($ErrorItemsArg));

        $unsyncedItemsArgs = array(
            'taxonomy'   => "product_cat",
            'hide_empty' => false,
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'wk_sf_category_id',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key'     => 'wk_sf_category_error',
                        'compare' => 'NOT EXISTS'
                    ),
                ),
                array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'wk_sf_category_id',
                        'compare' => '=',
                        'value' => NULL
                    ),
                    array(
                        'key'     => 'wk_sf_category_error',
                        'compare' => '=',
                        'value' => NULL
                    ),
                )
            )
        );
        $unsyncedItems = count(get_terms($unsyncedItemsArgs));

        //Check if uncategorized category is missing from the list: Add 'order' meta according to the generated SQL condition
        if ($totalCategoryCount != ($syncedItems + $unsyncedItems + $errorItems)) {
            $cat = get_term_by('name', 'uncategorized', 'product_cat');
            update_term_meta($cat->term_id, 'order', 0);

            //Recalculate the totals
            $totalCategoryCount = count(get_terms([
                'taxonomy'   => "product_cat", 'hide_empty' => false,
                'search' => !empty($_REQUEST['s']) ? $_REQUEST['s'] : ''
            ]));
            $unsyncedItems = count(get_terms($unsyncedItemsArgs));
            $errorItems = count(get_terms($ErrorItemsArg));
            $syncedItems = count(get_terms($syncedItemsArg));
        }
        $categoriesData = get_terms($args);
        $count = 0;
        $syncedInfo = array('total_items' => $totalCategoryCount, 'synced_items' => $syncedItems, 'unsynced_items' => $unsyncedItems, 'error_items' => $errorItems);
        
        echo
        '<p>
            <ul class="subsubsub">
                <li class="all">
                    <a class="" href="' . admin_url("admin.php?page=sync_cat&item_type=A") . '">
                        <input type="checkbox" name="total_items" id="elm_total_items" value="Y" ';
        if ($itemType == 'A') {
            echo 'checked="checked"';
        }
        echo
        ' disabled>
                        All
                        <span class="count">(' . $syncedInfo['total_items'] . ')</span>
                    </a> |
                </li>
                <li class="mine">
                    <a class="" href="' . admin_url("admin.php?page=sync_cat&item_type=S") . '">
                        <input type="checkbox" name="synced_items" id="elm_synced_items" value="Y" ';
        if ($itemType == 'A' || $itemType == 'S') {
            echo 'checked="checked"';
        }
        echo ' disabled>
                        Synced items
                        <span class="count">(' .  $syncedInfo['synced_items'] . ')</span>
                    </a> |
                </li>
                <li class="publish">
                    <a class="" href="' . admin_url("admin.php?page=sync_cat&item_type=U") . '">
                        <input type="checkbox" name="unsynced_items" id="elm_unsynced_items" value="Y"';
        if ($itemType == 'A' || $itemType == 'U') {
            echo 'checked="checked"';
        }
        echo ' disabled>
                        Unsynced items
                        <span class="count">(' .  $syncedInfo['unsynced_items'] . ')</span>
                    </a> |
                </li>
                <li class="error_type">
                    <a class="" href="' . admin_url("admin.php?page=sync_cat&item_type=E") . '">
                        <input type="checkbox" name="error_items" id="elm_error_items" value="Y"';
        if ($itemType == 'A' || $itemType == 'E') {
            echo 'checked="checked"';
        }
        echo ' disabled>
                        Error in Category Sync
                        <span class="count">(' . $syncedInfo['error_items'] . ')</span>
                    </a>
                </li>
            </ul>
        </p>';
        
        if($itemType == 'A'){
            $count= $syncedInfo['total_items'];
        }elseif($itemType == 'E'){
            $count= $syncedInfo['error_items'];
        }elseif($itemType == 'U'){
            $count= $syncedInfo['unsynced_items'];
        }elseif($itemType == 'S'){
            $count= $syncedInfo['synced_items'];
        }
        $this->set_pagination_args(array(
            'total_items' =>  $count,
            'per_page'    => $postsPerPage,
            'total_pages' => ceil( $count / $postsPerPage),
        ));
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');

        foreach ($categoriesData as $wooCat) {
            $img_src = wp_get_attachment_url(get_term_meta($wooCat->term_id, 'thumbnail_id', true));
            if (!$img_src) {
                $img_src = wc_placeholder_img_src();
            }
            $sfCategoryId = get_term_meta($wooCat->term_id, 'wk_sf_category_id', true);
            $sfCategoryError =  get_term_meta($wooCat->term_id, 'wk_sf_category_error', true);

            $actions = array();
            if (!empty($sfCategoryError)) {
                $title = 'Error:' . $sfCategoryError;
                if (!empty($sfCategoryError)) {
                    $title      = strip_tags($sfCategoryError);
                }
                $actions['incomplete'] = array(
                    'title'      => $title,
                    'action'    => "incomplete",
                    'spl_class'     => "dashicons-welcome-comments",
                    'color_code'     => 'red',
                    'id'            => 'sync_status_icon_' . $wooCat->term_id
                );
            } else if (!empty($sfCategoryId) && empty($sfCategoryError)) {
                $actions['complete'] = array(
                    'title'      => 'Successfully Synchronized',
                    'action'    => "complete",
                    'spl_class'     => "dashicons-yes",
                    'color_code'     => 'green',
                    'id'            => 'sync_status_icon_' . $wooCat->term_id
                );
            } else {
                $actions['complete'] = array(
                    'title'      => 'Now Synchronized',
                    'action'    => "",
                    'spl_class'     => "",
                    'color_code'     => '',
                    'id'            => 'sync_status_icon_' . $wooCat->term_id
                );
            }
            $categoryAction = '';
            $configObj = get_option('wooSfRestConfig');
            foreach ($actions as $action) {
                if ($GLOBALS['showButtonSync']) {
                    $categoryAction .= '<span id="resync_' . $wooCat->term_id . '" class="dashicons resync-cat-imp dashicons-download" title="Import Now" style="color: gray;"></span>';
                    $categoryAction .= '<span id="resync_' . $wooCat->term_id . '" class="dashicons resync-cat dashicons-upload" title="Export Now" style="color: gray;"></span>';
                } else {
                    $categoryAction .= '<span id="resync_' . $wooCat->term_id . '" class="dashicons dashicons-download" title="Import Now" style="color: gray;"></span>';
                    $categoryAction .= '<span id="resync_' . $wooCat->term_id . '" class="dashicons dashicons-upload" title="Export Now" style="color: gray;"></span>';
                }
                $categoryAction .= ' <span id="' . $action['id'] . '" class="dashicons ' . $action['spl_class'] . '" title="' . $action['title'] . '" style="color: ' . $action['color_code'] . ';"></span> ';
            }
            $categoryLink = '<a href="term.php?taxonomy=product_cat&tag_ID=' . $wooCat->term_id . '">' . $wooCat->name . '</a>';
            $data[] = array(
                'id'              => $wooCat->id,
                'categoryimage'   => '<img src="' . $img_src . '" width="50" height="50" />',
                'categoryname'    => $categoryLink,
                'woocategoryid'   => $wooCat->term_id,
                'salescategoryid' => isset($sfCategoryId) && !empty($sfCategoryId) ? '<a target="_blank" href="' . $configObj->instance_url . '/' . $sfCategoryId . '" > ' . $sfCategoryId . '</a>' : '--',
                'actionsync' => $categoryAction,
                'woosfmergeid'    => $wooCat->id,
            );
        }

        return $data;
    }

    public function get_columns()
    {
        $columns = array(
            'cb'              => '<input type="checkbox" />',
            'categoryimage'   => 'Image',
            'categoryname'    => 'Category Name',
            'woocategoryid'   => 'woocommerce Category Id',
            'salescategoryid' => 'Salesforce Category Id',
            'actionsync'      => 'Action',
        );
        return $columns;
    }

    public function prepareItems()
    {
        $productsData          = $this->tableData();
        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = $this->getSortableColumns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->processBulkAction();

        $this->items     = $productsData;
        $user            = get_current_user_id();
    }

    public function get_bulk_actions()
    {
        if ($GLOBALS['showButtonSync']) {
            $actions = array(
                'exportCategory'        => 'Export Selected Category',
                'importCategory'           => 'Import Selected Category',
                'delete'                 => 'Unlink',
                'delete-all'             => 'Unlink All Mapping'
            );
        } else {
            $actions = array(
                'exportCategory-dis'    => 'Export Selected Category',
                'importCategory-dis'       => 'Import Selected Category',
                'delete'                 => 'Unlink',
                'delete-all'             => 'Unlink All Mapping'
            );
        }
        return $actions;
    }

    public function processBulkAction()
    {
        global $wpdb;
        $current_user = wp_get_current_user();
        $action       = $this->current_action();

        if ('delete' === $action) {
            if (isset($_REQUEST['mergecategory'])) {
                $delList = $_REQUEST['mergecategory'];
            } else {
                $delList = !empty($_REQUEST['cid']) ? array($_REQUEST['cid']) : array();
            }

            $prefix = $wpdb->prefix;
            if (!empty($delList)) {
                foreach ($delList as $id) {
                    delete_term_meta($id, 'wk_sf_category_id');
                    delete_term_meta($id, 'wk_sf_category_error');
                }
                wp_safe_redirect(admin_url('admin.php?page=sync_cat'));
                exit;
            }
        }
        if ($action == 'delete-all') {
            delete_metadata('term', 0, 'wk_sf_category_id', false, true);
            delete_metadata('term', 0, 'wk_sf_category_error', false, true);
            wp_safe_redirect(admin_url('admin.php?page=sync_cat'));
            exit;
        }
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'categoryimage':
            case 'categoryname':
            case 'woocategoryid':
            case 'salescategoryid':
            case 'woosync_time':
            case 'actionsync':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }
    public function getSortableColumns()
    {
        $sortable_columns = array(
            'categoryname'    => array('name', false),
            'woocategoryid'   => array('term_id', false),
            'salescategoryid' => array('sf_category_id', false),
            'woosync_time'    => array('sync_time', false),

        );
        return $sortable_columns;
    }
    public function column_categoryname($item)
    {
        return $item['categoryname'];
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="mergecategory[]" value="%s" />',
            $item['woocategoryid']
        );
    }
    public function searchBox($text, $inputId)
    { ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo $inputId ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo $inputId ?>" name="s" value="<?php echo isset($_REQUEST['s']) ? $_REQUEST['s'] : ''; ?>" />
            <?php submit_button($text, 'button', false, false, array('id' => 'search-submit')); ?>
        </p>
<?php
    }
}
