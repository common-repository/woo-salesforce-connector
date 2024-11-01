<?php
class WooSfRestCategoriesView
{
    /**
     * Return a singleton instance of WooSfRestCategoriesView class
     *
     * @brief Singleton
     *
     * @return WooSfRestCategoriesView
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
     * Create an instance of WooSfRestCategoriesView class
     *
     * @brief Construct
     *
     * @return WooSfRestCategoriesView
     */
    public function __construct()
    {
        $this->enqueueScriptsStylesInit();
        global $showButtonSync;
        $GLOBALS['showButtonSync'] = true;
    }

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
        if (!class_exists('wooSfRestCategoryTable')) {
            require_once WOOSFREST_ABSPATH . 'includes/table/woosfrest-CategoryTable.php';
        }

        $wooSfRestProductConfig = get_option('wooSfRestProductConfig');
        if (!$wooSfRestProductConfig) {
            $wooSfRestProductConfig = new stdclass;
        }

        $myListTable = new wooSfRestCategoryTable();
        echo
            '<div class="wrap" id="my_wrap">
            <div class="wpdk-view wrap clearfix" id="first-view-controller-view-root" data-type="wpdk-view">
                <div class="wpdk-view clearfix wpdk-header-view clearfix" id="first-view-controller-header-view" data-type="wpdk-view">
                    <div class="wpdk-vc-header-icon" id="first-view-controller-header-view" data-type="wpdk-header-view"></div>
                    <h1>Synchronize Categories</h1>
                    <div class="wpdk-vc-header-after-title"></div>
                    <br>';
        if (!isset($wooSfRestProductConfig->sync_data) || !$wooSfRestProductConfig->sync_data) {
            WooSfRest::showNotice('error', 'Sync Error:', "Category Sync has been disabled in Settings");
            exit;
        }
        $documentFolder = '';
        if (isset($wooSfRestProductConfig->imagefolder)) {
            $documentFolder = esc_attr($wooSfRestProductConfig->imagefolder);
        }
        if (!$wooSfRestProductConfig->sync_to_files && empty($documentFolder)) {
            WooSfRest::showNotice('error', '<b><i>Document Error:</i></b>', "No Document folder selected in options");
            $GLOBALS['showButtonSync'] = false;
        }

        if (get_option('category_total')) {
            $total_jobs = get_option('category_total');
        } else {
            $total_jobs = 0;
        }
        if (get_option('category_processed')) {
            $processed_jobs = get_option('category_processed');
        } else {
            $processed_jobs = 0;
        }
        if ($_GET['page'] == 'sync_cat' && (isset($_GET['export']) && $_GET['export'] == 'category')) {

            if (wp_next_scheduled('wp_synchronized_process_category_cron')) {

                WooSfRest::showNotice('updated', 'Background process for categories is running ', 'Job processed ' . $processed_jobs . '  outof  ' . $total_jobs . '&nbsp&nbsp<a href="admin.php?page=sync_cat&amp;action=cancel" onclick="return cancelBackgroundJob(\'Categories\')">Cancel</a>');
            } else {
                $wooSfrest = new WooSfRest();
                $wooSfrest->createBackgroundJob('category');
                wp_redirect(admin_url('admin.php?page=sync_cat'));

            }
        }
        if ($_GET['page'] == 'sync_cat' && !(isset($_GET['export']) && $_GET['export'] == 'category')) {
            if (wp_next_scheduled('wp_synchronized_process_category_cron')) {

                if (!get_option('delete_category')) {
                    WooSfRest::showNotice('updated', 'Background process for categories is running ', 'Job processed ' . $processed_jobs . '  outof  ' . $total_jobs . '&nbsp&nbsp<a href="admin.php?page=sync_cat&amp;action=cancel" onclick="return cancelBackgroundJob(\'Categories\')">Cancel</a>');
                } else {
                    WooSfRest::showNotice('updated', 'Background process for categories is ', 'Cancelled');
                }

            }
        }

        if (isset($_GET['action']) && $_GET['action'] == 'cancel') {

            update_option('delete_category', true);

            wp_redirect(admin_url('admin.php?page=sync_cat'));

        }
        if ($GLOBALS['showButtonSync']) {
            if (isset($_GET['item_type'])) {
                $href = wp_nonce_url(admin_url('admin.php?page=sync_cat&amp;item_type=' . $_GET['item_type'] . '&amp;export=category'), 'category');
            } else {
                $href = wp_nonce_url(admin_url('admin.php?page=sync_cat&amp;item_type=A' . '&amp;export=category'), 'category');
            }
            echo
                '
                        <!--<a href="javascript:void(0);" id="removewoosfrest-synchronize-category-button" class="page-title-action" style="margin-right: 25px;">Synchronize Categories-->

                        <a href="' . $href . '" id="export-category-button" class="page-title-action" style="margin-right: 25px;">Export Categories

                        </a>
                        </a>
                        <!--<a href="javascript:void(0);" id="woosfrest-export-category-button" class="page-title-action" style="margin-right: 25px;">Export Categories

                        </a>-->
                        <a href="javascript:void(0);" id="woosfrest-import-category-button" class="page-title-action" style="margin-right: 25px;">Import  Categories

                        </a>';

        } else {
            echo
                '
                        <!--<a href="javascript:void(0);" id="removewoosfrest-synchronize-category-button" class="page-title-action button-disabled" style="margin-right: 25px; pointer-events: none">Synchronize Categories-->

                        </a>
                        <a href="javascript:void(0);" id="woosfrest-export-category-button" class="page-title-action button-disabled" style="margin-right: 25px; pointer-events: none">Export Categories

                        </a>
                        <a href="javascript:void(0);" id="woosfrest-import-category-button" class="page-title-action button-disabled" style="margin-right: 25px; pointer-events: none">Import  Categories

                        </a>';
        }
        echo
            '<a href="javascript:void(0);" id="woosfrest-import-option">Import Option</a>
                    <span class="spinner" id="woosfrest-import-category-id" style="position: fixed; right: 45%; top: 45%;"></span>
                    <br>
                    <div class="hidden" id="woosfrest-import-options">
                        <br>
                        <label><input type="checkbox" name="woosfrest-sync-limit-check" id="woosfrest-sync-limit-check" value="Y">Batch Size</label>
                        <input type="text" value="100" name="woosfrest-sync-limit" onfocusout = "myfunction()"size="6" class="input-text wc_input_decimal" placeholder="Limit" id="woosfrest-sync-limit" title="Value must be between 1-2000" disabled>
                    </div>
                    <br>
                </div>
            </div>
            '

        ;
        if (!wp_next_scheduled('wp_synchronized_process_category_cron') && get_option('category_total')) {
            echo '<span>The last Export job has run for ' . get_option('category_total') . ' category(s).</span>&nbsp&nbsp';
        }
        echo
            '<form method="post">
                <input type="hidden" name="page" value="sync_cat" />';
        $myListTable->searchBox('Search', 'categorysearch');
        $myListTable->prepareItems();
        $myListTable->display();
        echo
            '</form>
        </div>';
    }
}
