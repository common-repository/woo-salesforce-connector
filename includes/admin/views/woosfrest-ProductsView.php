<?php
class WooSfRestProductsView
{
    /**
     * Return a singleton instance of WooSfRestProductsView class
     *
     * @brief Singleton
     *
     * @return WooSfRestProductsView
     */
    public static function init()
    {
        static $instance = null;
        if (is_null($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Create an instance of WooSfRestProductsView class
     *
     * @brief Construct
     *
     * @return WooSfRestProductsView
     */
    public function __construct()
    {
        // Build the container, with default header
        add_filter('screen_settings', array(&$this, 'add_options'));
        $this->enqueueScriptsStylesInit();
        global $showButtonSync;
        $GLOBALS['showButtonSync'] = true;
    }
    /**
     * Include plugin js and css
     *
     * @return void
     */
    public function enqueueScriptsStylesInit()
    {
        wp_enqueue_script('ajax-script', plugins_url() . '/woo-salesforce-connector/assets/js/js-script.js', array('jquery'), 1.0);
        wp_enqueue_script('wkajax-script', plugins_url() . '/woocommerce/assets/js/jquery-blockui/jquery.blockUI.min.js', array(), 2.7);
        wp_register_style('wws-css', plugins_url() . '/woo-salesforce-connector/assets/css/style.css');
        wp_enqueue_style('wws-css');
        wp_localize_script('ajax-script', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'))); // setting ajaxurl
    }

    /**
     * Display method to create view elements
     *
     * @return void
     */
    public function display()
    {
        global $wpdb;
        $tableName2 = $wpdb->prefix . 'posts';

        if (!class_exists('WooSfRestProductTable')) {
            require_once WOOSFREST_ABSPATH . 'includes/table/woosfrest-ProductTable.php';
        }

        $wooSfRestProductConfig = get_option('wooSfRestProductConfig');
        if (!$wooSfRestProductConfig) {
            $wooSfRestProductConfig = new stdclass;
        }

        $mappings = get_option('wooSfRestAdvancedConfig');
        $mappings = (array) $mappings;
        $myListTable = new WooSfRestProductTable();
        echo
            '<div class="wrap" id="my_wrap">
            <div class="wpdk-view wrap clearfix" id="first-view-controller-view-root" data-type="wpdk-view">
                <div class="wpdk-view clearfix wpdk-header-view clearfix" id="first-view-controller-header-view" data-type="wpdk-view">
                    <div class="wpdk-vc-header-icon" id="first-view-controller-header-view" data-type="wpdk-header-view"></div>
                    <h1>Synchronize Products</h1>
                    <div class="wpdk-vc-header-after-title"></div>
                    <br>';
        $documentFolder = '';
        if (isset($wooSfRestProductConfig->imagefolder)) {
            $documentFolder = esc_attr($wooSfRestProductConfig->imagefolder);
        }
        if (!isset($wooSfRestProductConfig->sync_to_files) && empty($documentFolder)) {
            WooSfRest::showNotice('error', '<b><i>Document Error:</i></b>', "No Document folder selected in options");
            $GLOBALS['showButtonSync'] = false;
        }
        if (isset($wooSfRestProductConfig->sync_to_files) && !$wooSfRestProductConfig->sync_to_files && empty($documentFolder)) {
            WooSfRest::showNotice('error', '<b><i>Document Error:</i></b>', "No Document folder selected in options");
            $GLOBALS['showButtonSync'] = false;
        }
        if (empty($wooSfRestProductConfig->pricebook)) {
            WooSfRest::showNotice('error', 'Pricebook Error:', 'No pricebook set in option');
            $GLOBALS['showButtonSync'] = false;
        }
        if (get_option('products_total')) {
            $total_jobs = get_option('products_total');
        } else {
            $total_jobs = 0;
        }
        if (get_option('products_processed')) {
            $processed_jobs = get_option('products_processed');
        } else {
            $processed_jobs = 0;
        }
        if ($_GET['page'] == 'sync_prod' && (isset($_GET['export']) && $_GET['export'] == 'products')) {
            if (wp_next_scheduled('wp_synchronized_process_product_cron')) {
                WooSfRest::showNotice('updated', 'Background process for products is running ', 'Job processed ' . $processed_jobs . '  outof  ' . $total_jobs . '&nbsp&nbsp<a href="admin.php?page=sync_prod&amp;action=cancel" onclick="return cancelBackgroundJob(\'Products\')">Cancel</a>');
            } else {
                $wooSfrest = new WooSfRest();
                $wooSfrest->createBackgroundJob('products');
                wp_redirect(admin_url('admin.php?page=sync_prod'));

            }
        }
        if ($_GET['page'] == 'sync_prod' && !(isset($_GET['export']) && $_GET['export'] == 'products')) {
            if (wp_next_scheduled('wp_synchronized_process_product_cron')) {
                print_r(get_option('delete_product'));
                if (!get_option('delete_product')) {
                    WooSfRest::showNotice('updated', 'Background process for products is running ', 'Job processed ' . $processed_jobs . '  outof  ' . $total_jobs . '&nbsp&nbsp<a href="admin.php?page=sync_prod&amp;action=cancel" onclick="return cancelBackgroundJob(\'Products\')">Cancel</a>');
                } else {
                    WooSfRest::showNotice('updated', 'Background process for products is  ', 'Cancelled');
                }
            }
        }

        if (isset($_GET['action']) && $_GET['action'] == 'cancel') {

            update_option('delete_product', true);

            wp_redirect(admin_url('admin.php?page=sync_prod'));

        }
        if ($GLOBALS['showButtonSync']) {
            if (isset($_GET['item_type'])) {
                $href = wp_nonce_url(admin_url('admin.php?page=sync_prod&amp;item_type=' . $_GET['item_type'] . '&amp;export=products'), 'products');
            } else {
                $href = wp_nonce_url(admin_url('admin.php?page=sync_prod&amp;item_type=A&amp;export=products'), 'products');
            }
            echo
                '  <!--<a href="javascript:void(0);" id="removesynchronizeproduct_button" class="page-title-action" style="margin-right: 25px;">Synchronize All Products


                        </a>-->
                        <!--Background process Implementation --!>
                        <a href="' . $href . '" id="exportproduct-button" class="page-title-action" style="margin-right: 25px;">Export Products
                        </a>

                       <!-- <a href="javascript:void(0);" id="woosfrest-exportproduct-button" class="page-title-action" style="margin-right: 25px;">Export Products

                        </a>--!>
                        <a href="javascript:void(0);" id="woosfrest-importproduct-button" class="page-title-action" style="margin-right: 8px;">Import  Products

                        </a>';

        } else {
            echo
                '
                          <!--<a href="javascript:void(0);" id="removesynchronizeproduct_button" class="page-title-action button-disabled" style="margin-right: 25px; pointer-events: none">Synchronize All Products

                        </a>-->
                        <a href="javascript:void(0);" id="woosfrest-exportproduct-button" class="page-title-action button-disabled" style="margin-right: 25px; pointer-events: none">Export Products

                        </a>
                        <a href="javascript:void(0);" id="woosfrest-importproduct-button" class="page-title-action button-disabled" style="margin-right: 8px; pointer-events: none">Import  Products

                        </a>';
        }
        echo
            '<a href="javascript:void(0);" id="woosfrest-import-option">Import Option</a>
                    <br>
                     <span class="spinner" id="woosfrest-importproduct-id" style="position: fixed; right: 45%; top: 45%;"></span>
                    <div class="hidden" id="woosfrest-import-options">
                    <br>
                        <label title="The default is 500; the minimum is 200, and the maximum is 2,000."><input type="checkbox" name="sfwp_limit_import_ch" id="sfwp_limit_import_ch" value="Y">Batch Size</label>
                        <input type="text" value="200" name="sfwp_product_limit" size="6" class="input-text wc_input_decimal" placeholder="Limit" id="sfwp_product_limit" title="Value must be between 1-2000" disabled>

                    </div>
                    <br>
                </div>
            </div>';
        if (!wp_next_scheduled('wp_synchronized_process_product_cron') && get_option('products_total')) {
            echo '<span>The last Export job has run for ' . get_option('products_total') . ' product(s).</span>&nbsp&nbsp';
        }

        $args = array(
            'fields' => 'ids',
            'posts_per_page' => -1,
            'post_type' => 'product',
            'post_status' => array('publish', 'draft'),
        );
        $totalProducts = get_posts($args);
        $totalProductCount = count($totalProducts);

        $unsyncArgsTotal = array(
            'fields' => 'ids',
            'posts_per_page' => -1,
            'post_type' => 'product',
            'post_status' => array('publish', 'draft'),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'wk_sf_product_id',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => 'wk_sf_product_err',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );
        $totalUnsyncedItems = get_posts($unsyncArgsTotal);
        $unsyncedItems = count($totalUnsyncedItems);

        $errArgsTotal = array(
            'fields' => 'ids',
            'posts_per_page' => -1,
            'post_type' => 'product',
            'post_status' => array('publish', 'draft'),
            'meta_key' => 'wk_sf_product_err',
        );
        $totalErrArgsTotal = get_posts($errArgsTotal);
        $errorItems = count($totalErrArgsTotal);

        $syncArgsTotal = array(
            'fields' => 'ids',
            'posts_per_page' => -1,
            'post_type' => 'product',
            'post_status' => array('publish', 'draft'),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'wk_sf_product_id',
                ),
                array(
                    'key' => 'wk_sf_product_err',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );
        $syncArgsItems = get_posts($syncArgsTotal);
        $syncedItems = count($syncArgsItems);

        $syncedInfo = array('total_items' => $totalProductCount, 'synced_items' => $syncedItems, 'unsynced_items' => $unsyncedItems, 'error_items' => $errorItems);
        $item_type = isset($_REQUEST['item_type']) ? $_REQUEST['item_type'] : 'A';
        echo
        '<p>
                <ul class="subsubsub">
                    <li class="all">
                        <a class="" href="' . admin_url("admin.php?page=sync_prod&item_type=A") . '">
                            <input type="checkbox" name="total_items" id="elm_total_items" value="Y" ';
        if ($item_type == 'A') {
            echo 'checked="checked"';
        }
        echo ' disabled>
                            All <span class="count">(' . $syncedInfo['total_items'] . ')</span>
                        </a> |
                    </li>
                    <li class="mine">
                        <a class="" href="' . admin_url("admin.php?page=sync_prod&item_type=S") . '">
                            <input type="checkbox" name="synced_items" id="elm_synced_items" value="Y" ';
        if ($item_type == 'A') {
            echo 'checked="checked"';
        } elseif ($item_type == 'S') {
            echo 'checked="checked"';
        }
        echo ' disabled>
                            Synced items <span class="count">(' . $syncedInfo['synced_items'] . ')</span>
                        </a> |
                    </li>
                    <li class="publish">
                        <a class="" href="' . admin_url("admin.php?page=sync_prod&item_type=U") . '">
                            <input type="checkbox" name="unsynced_items" id="elm_unsynced_items" value="Y"';
        if ($item_type == 'A') {
            echo 'checked="checked"';
        } elseif ($item_type == 'U') {
            echo 'checked="checked"';
        }
        echo ' disabled>Unsynced items <span class="count">(' . $syncedInfo['unsynced_items'] . ')</span>
                        </a> |
                    </li>
                    <li class="error_type">
                        <a class="" href="' . admin_url("admin.php?page=sync_prod&item_type=E") . '">
                            <input type="checkbox" name="error_items" id="elm_error_items" value="Y"';
        if ($item_type == 'A') {
            echo 'checked="checked"';
        } elseif ($item_type == 'E') {
            echo 'checked="checked"';
        }
        echo ' disabled>Error in Product Sync <span class="count">(' . $syncedInfo['error_items'] . ')</span>
                        </a>
                    </li>
                </ul>
            </p>
            <form method="get">
                <input type="hidden" name="page" value="sync_prod" />';
        $myListTable->searchBox('Search', 'productsearch');
        $myListTable->prepareItems();
        $myListTable->display();
        echo
            '</form>';
        add_action('restrict_manage_posts', 'restrict_listings_by_business');
        echo
            '</div>';
        if (get_option('allow_import')) {
            ?>
            <script>
                window.allowImport = true;
                $ = jQuery.noConflict();
                $('#sfwp_limit_import_ch').on('click', function() {

                    if ($('#sfwp_limit_import_ch').is(':checked') == false)
                        $('#sfwp_product_limit').prop("disabled", true);
                    if ($('#sfwp_limit_import_ch').is(':checked') == true)
                        $('#sfwp_product_limit').removeAttr('disabled');

                });
            </script>
        <?php
} else {
            ?>
            <script>
                window.allowImport = false;
            </script>
<?php
}
    }
}
