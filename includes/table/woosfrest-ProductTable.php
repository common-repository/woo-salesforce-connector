<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WooSfRestProductTable extends WP_List_Table
{
    private function tableData()
    {
        global $wpdb;
        $currentPage = $this->get_pagenum();
        $configObj = get_option('wooSfRestConfig');
        $itemType = isset($_REQUEST['item_type']) ? $_REQUEST['item_type'] : 'A';
        $data  = $wooproductsArray = array();
        $user = get_current_user_id();
        $screen = get_current_screen();
        $option = $screen->get_option('per_page', 'option');
        $postsPerPage = $this->get_items_per_page($option, 5);
        $vars = $_REQUEST;
        $args = array(
            'posts_per_page' => $postsPerPage,
            'post_type'      => 'product',
            'post_status'    => array('publish', 'draft'),
            'paged'          => $currentPage,
            's'              => !empty($_REQUEST['s']) ? $_REQUEST['s'] : ''
        );
        $itemType = isset($_REQUEST['item_type']) ? $_REQUEST['item_type'] : 'A';
        if (isset($itemType)) {
            if ($itemType == 'U') {
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'wk_sf_product_id',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key'     => 'wk_sf_product_err',
                        'compare' => 'NOT EXISTS'
                    ),
                );
            }
            if ($itemType == 'E') {
                $args['meta_key']          = 'wk_sf_product_err';
            }
            if ($itemType == 'S') {
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'wk_sf_product_id',
                    ),
                    array(
                        'key'     => 'wk_sf_product_err',
                        'compare' => 'NOT EXISTS'
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
        $args['itemType'] = $itemType;
        $allProductsArray = new WP_Query($args);
        $count = $allProductsArray->found_posts;

        $this->set_pagination_args(array(
            'total_items' => $count,
            'per_page'    => $postsPerPage,
            'total_pages' => ceil($count / $postsPerPage),
        ));
        if (isset($allProductsArray) && !empty($allProductsArray)) {
            foreach ($allProductsArray->posts as $wooProducts) {
                $sf_product_id = get_post_meta($wooProducts->ID, 'wk_sf_product_id', true);
                $wk_sf_product_err = get_post_meta($wooProducts->ID, 'wk_sf_product_err', true);
                $imgSrc = wp_get_attachment_url(get_post_thumbnail_id($wooProducts->ID));
                if (!$imgSrc) {
                    $imgSrc = wc_placeholder_img_src();
                }
                $actions = array();
                if ($wk_sf_product_err && !empty($wk_sf_product_err)) {
                    $title = 'Error';
                    if (!empty($wk_sf_product_err)) {
                        $title = strip_tags($wk_sf_product_err);
                    }
                    $actions['incomplete'] = array(
                        'title' => $title,
                        'action' => "incomplete",
                        'spl_class' => "dashicons-welcome-comments",
                        'color_code' => 'red',
                        'id' => 'sync_status_icon_' . $wooProducts->ID
                    );
                } elseif ($sf_product_id && !empty($sf_product_id)) {
                    $actions['complete'] = array(
                        'title' => 'Successfully Synchronized',
                        'action' => "complete",
                        'spl_class' => "dashicons-yes",
                        'color_code' => 'green',
                        'id' => 'sync_status_icon_' . $wooProducts->ID
                    );
                } else {
                    $actions['complete'] = array(
                        'title' => 'Now Synchronized',
                        'action' => "",
                        'spl_class' => "",
                        'color_code' => '',
                        'id' => 'sync_status_icon_' . $wooProducts->ID
                    );
                }
                $product_action = '';
                foreach ($actions as $action) {
                    if ($GLOBALS['showButtonSync']) {
                        $product_action .= '<span id="resync_' . $wooProducts->ID . '" class="dashicons resync-prod-imp dashicons-download" title="Import Now" style="color: gray;"></span>';
                        $product_action .= '<span id="resync_' . $wooProducts->ID . '" class="dashicons resync-prod dashicons-upload" title="Export Now" style="color: gray;"></span>';
                    } else {
                        $product_action .= '<span id="resync_' . $wooProducts->ID . '" class="dashicons dashicons-download" title="Import Now" style="color: gray;"></span>';
                        $product_action .= '<span id="resync_' . $wooProducts->ID . '" class="dashicons dashicons-upload" title="Export Now" style="color: gray;"></span>';
                    }
                    $product_action .= ' <span id="' . $action['id'] . '" class="dashicons ' . $action['spl_class'] . '" title="' . $action['title'] . '" style="color: ' . $action['color_code'] . ';"></span> ';
                }

                $data[] = array(
                    'id' => $wooProducts->Id,
                    'productimage' => '<a href="' . get_edit_post_link($wooProducts->ID) . '" ><img src="' . $imgSrc . '" width="50" height="50" /></a>',
                    'productname' => '<a href="' . get_edit_post_link($wooProducts->ID) . '" > ' . $wooProducts->post_title . '</a>',
                    'wooproductid' => $wooProducts->ID,
                    'salesproductid' => isset($sf_product_id) && !empty($sf_product_id) ? '<a target="_blank" href="' . $configObj->instance_url . '/' . $sf_product_id . '" > ' . $sf_product_id . '</a>' : '--',
                    'woosfmergeid' => $wooProducts->id,
                    'actionsync' => $product_action,
                );
            }
        }

        return $data;
    }
    public function get_columns()
    {
        $columns = array(
            'cb'             => '<input type="checkbox" />',
            'productimage'   => 'Image',
            'productname'    => 'Product Name',
            'wooproductid'   => 'woocommerce Product Id',
            'salesproductid' => 'Salesforce Product Id',
            'actionsync'     => 'Action',
        );
        return $columns;
    }

    public function prepareItems()
    {
        global $wpdb;
        $this->processBulkAction();
        $productsData          = $this->tableData();
        $columns               = $this->get_columns();
        $hidden                = $this->getHiddenColumns();
        $sortable              = $this->getSortableColumns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items     = $productsData;
        $this->items = $productsData;
    }

    public function getHiddenColumns()
    {
        return array();
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'productimage':
            case 'productname':
            case 'wooproductid':
            case 'salesproductid':
            case 'actionsync':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }
    public function getSortableColumns()
    {
        $sortable_columns = array(
            'productname'    => array('title', false),
            'wooproductid'   => array('ID', false),
            'salesproductid' => array('salesproductid', false),
        );
        return $sortable_columns;
    }

    public function usort_reorder($a, $b)
    {
        // If no sort, default to title
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'productname';
        // If no order, default to asc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
        // Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);
        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }

    public function column_productname($item)
    {
        return $item['productname'];
    }

    public function get_bulk_actions()
    {
        if ($GLOBALS['showButtonSync']) {
            $actions = array(
                'exportProduct' => 'Export Selected Product(s)',
                'importProduct' => 'Import Selected Product(s)',
                'delete' => 'Unlink',
                'delete-all' => 'Unlink All Mapping'
            );
        } else {
            $actions = array(
                'exportProduct-dis' => 'Export Selected Product(s)',
                'importProduct-dis' => 'Import Selected Product(s)',
                'delete' => 'Unlink',
                'delete-all'    => 'Unlink All Mapping'
            );
        }
        return $actions;
    }

    public function processBulkAction()
    {
        global $wpdb;
        $action       = $this->current_action();
        if ('delete' === $action) {
            if (isset($_REQUEST['mergedproduct'])) {
                $delList = $_REQUEST['mergedproduct'];
            } else {
                if (!empty($_REQUEST['pmid'])) {
                    $delList = array($_GET['pmid']);
                }
            }
            $prefix = $wpdb->prefix;
            if (!empty($delList)) {
                foreach ($delList as $id) {
                    delete_post_meta($id, 'wk_sf_product_id');
                    delete_post_meta($id, 'wk_sf_product_err');
                }
                wp_safe_redirect(admin_url('admin.php?page=sync_prod'));
                exit;
            }
        } else if ($action == 'exportProduct' || $action == 'importProduct') {
            wp_safe_redirect(admin_url('admin.php?page=sync_prod'));
            exit;
        }
        if ($action == 'delete-all') {
            $prefix = $wpdb->prefix;
            delete_metadata('post', 0, 'wk_sf_product_id', false, true);
            delete_metadata('post', 0, 'wk_sf_product_err', false, true);

            wp_safe_redirect(admin_url('admin.php?page=sync_prod'));
            exit;
        }
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="mergedproduct[]" value="%s" />',
            $item['wooproductid']
        );
    }

    public function searchBox($text, $input_id)
    { ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
            <?php submit_button($text, 'button', false, false, array('id' => 'search-submit')); ?>
        </p>
<?php
    }
}
