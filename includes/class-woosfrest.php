<?php
defined('ABSPATH') || exit;

/**
 * Main WooCommerce Salesforce Connector REST API Class.
 *
 * @class WooSfRest
 */
final class WooSfRest
{
    /**
     * WooCommerce Salesforce Connector REST API version.
     *
     * @var String
     */
    public $version = '4.0';

    /**
     * The single instance of the class.
     *
     * @var WooSfRest
     * @since 1.0
     */
    protected static $_instance = null;

    protected static $process_category;
    protected static $process_product;

    protected static $delete_user;
    /**
     * WooCommerce Salesforce Connector REST API Constructor.
     */
    public function __construct()
    {

        $this->defineConstants();
        $this->includes();
        $this->initHooks();
        $this->init();
        new SalesforceConnector();
        add_action("wp_ajax_getAccountViaRecordTypeId", array($this, "getAccountViaRecordTypeId"));
        add_action("wp_ajax_nopriv_getAccountViaRecordTypeId", array($this, "getAccountViaRecordTypeId"));
        add_action("wp_ajax_getAccountWithSearchData", array($this, "getAccountWithSearchData"));
        add_action("wp_ajax_getDocumentFolderWithSearchData", array($this, "getDocumentFolderWithSearchData"));
        add_action("wp_ajax_getPricebookWithSearchData", array($this, "getPricebookWithSearchData"));
        add_action("wp_ajax_nopriv_getAccountWithSearchData", array($this, "getAccountWithSearchData"));
        add_action("wp_ajax_nopriv_getPricebookWithSearchData", array($this, "getPricebookWithSearchData"));
        add_action("wp_ajax_nopriv_getDocumentFolderWithSearchData", array($this, "getDocumentFolderWithSearchData"));

        add_filter('woocommerce_duplicate_product_exclude_meta', array($this, 'excludePluginMetaFromDuplicate'), 10, 2);

        $wooSfRestOrderConfig = get_option('wooSfRestOrderConfig');
        if (!$wooSfRestOrderConfig) {
            $wooSfRestOrderConfig = new stdClass();
        }

        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $wooSfRestProductConfig = get_option('wooSfRestProductConfig');
        if (!$wooSfRestProductConfig) {
            $wooSfRestProductConfig = new stdClass();
        }

        //Background Process Action For Bulk  API added by Naincy.

        //Auto Category Sync functionality added by Naincy
        if (isset($wooSfRestProductConfig->auto_category_sync) && $wooSfRestProductConfig->auto_category_sync) {
            /** Call function during create new category/taxonomy */
            add_action('create_product_cat', array($this, 'wp_custom_save_taxonomy'), 10, 4);
            add_action('edit_product_cat', array($this, 'wp_custom_save_taxonomy'), 10, 4);
        }

        //Auto Product Sync functionality added by Naincy
        if (isset($wooSfRestProductConfig->auto_product_sync) && $wooSfRestProductConfig->auto_product_sync) {
            add_action('added_post_meta', array($this, 'wooSfRestNewProduct'), 10, 4);
            add_action('woocommerce_update_product', array($this, 'wooSfUpdateProduct'), 10, 1);
        }

        if (isset($wooSfRestOrderConfig->order_auto_sync) && $wooSfRestOrderConfig->order_auto_sync) {
            add_action('woocommerce_checkout_update_order_meta', array($this, 'wooSfRestNewOrder'), 10, 1);
            add_action('woocommerce_process_shop_order_meta', array($this, 'wooSfRestNewOrder'), 53, 1);
            add_action('woocommerce_order_status_changed', array($this, 'wooSfRestNewOrder'), 10, 1);
        }

        $wooSfRestAccountConfig = get_option('wooSfRestAccountConfig');
        if (!$wooSfRestAccountConfig) {
            $wooSfRestAccountConfig = new stdClass();
        }

        if (isset($wooSfRestAccountConfig->auto_user_sync) && $wooSfRestAccountConfig->auto_user_sync) {
            add_action('user_register', array($this, 'wooSfRestNewUser'), 10, 10);
            add_action('profile_update', array($this, 'wooSfRestNewUser'), 12, 1);
            add_action('woocommerce_customer_save_address', array($this, 'wooSfRestNewUser'), 12, 1);
        }

        // if disconnected then remove hooks
        $wooSfRestConfig = get_option('wooSfRestConfig');
        if (!$wooSfRestConfig) {
            remove_action('woocommerce_checkout_update_order_meta', array($this, 'wooSfRestNewOrder'), 10, 10);
            remove_action('woocommerce_process_shop_order_meta', array($this, 'wooSfRestNewOrder'), 53, 1);
            remove_action('woocommerce_order_status_changed', array($this, 'wooSfRestNewOrder'), 10, 10);
            remove_action('user_register', array($this, 'wooSfRestNewUser'), 10, 10);
            remove_action('profile_update', array($this, 'wooSfRestNewUser'), 10, 10);
            remove_action('save_post_product', array($this, 'wooSfRestNewProduct'), 10, 4);
            remove_action('create_product_cat', array($this, 'wp_custom_save_taxonomy'), 10, 4);
            remove_action('edit_product_cat', array($this, 'wp_custom_save_taxonomy'), 10, 4);
        }
        do_action('woosfrest_loaded');
    }

    public function excludePluginMetaFromDuplicate($exclude_meta, $existing_meta_keys)
    {
        array_push($exclude_meta, 'wk_sf_product_id', 'wk_sf_product_err');
        return $exclude_meta;
    }

    //created custom log file for bulk API result
    public static function custom_logs($message)
    {
        if (is_array($message)) {
            $message = json_encode($message);
        }
        $d = date("j-M-Y H:i:s e");
        error_log("\n[$d] $message", 3, "../wp-content/plugins/woo-salesforce-connector/wooConnector_logs.log");
    }

    /**
     * Define WooCommerce Salesforce Connector REST API Constants.
     */
    private function defineConstants()
    {
        $upload_dir = wp_upload_dir(null, false);
        $this->define('WOOSFREST_ABSPATH', dirname(WOOSFREST_PLUGIN_FILE) . '/');
        $this->define('WOOSFREST_PLUGIN_BASENAME', plugin_basename(WOOSFREST_PLUGIN_FILE));
        $this->define('WOOSFREST_VERSION', $this->version);
        $this->define('WOOSFREST_LOG_DIR', $upload_dir['basedir'] . '/woosfrest-logs/');
    }

    public function init()
    {
        require_once ABSPATH . WPINC . '/pluggable.php';
        require_once WOOSFREST_ABSPATH . 'class-synchronized-process-category.php';
        require_once WOOSFREST_ABSPATH . 'class-synchronized-process-product.php';
        self::$process_category = new WP_Synchronized_Process_Category();
        self::$process_product = new WP_Synchronized_Process_Product();

    }

    /**
     * Define constant if not already set.
     *
     * @param String      $name  Constant name.
     * @param String|Boolean $value Constant value.
     */
    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes()
    {
        /**
         * Class autoloader.
         */

        /**
         * Interfaces.
         */

        /**
         * Abstract classes.
         */

        /**
         * Core classes.
         */
        include_once WOOSFREST_ABSPATH . 'includes/class-woosfrest-install.php';

        include_once WOOSFREST_ABSPATH . 'includes/admin/class-woosfrest-admin.php';

        include_once WOOSFREST_ABSPATH . 'includes/admin/class-sfconnector.php';

        /**
         * Data stores - used to store and retrieve CRUD object data from the database.
         */

        /**
         * REST API.
         */

        /**
         * Libraries
         */
    }

    /**
     * Hook into actions and filters.
     *
     * @since 1.0
     */
    private function initHooks()
    {
        register_activation_hook(WOOSFREST_PLUGIN_FILE, array('WooSfRestInstall', 'install'));
        register_shutdown_function(array($this, 'logErrors'));
    }

    /**
     * Ensures fatal errors are logged so they can be picked up in the status report.
     *
     * @since 1.0
     */
    public function logErrors()
    {
        $error = error_get_last();
        if (is_array($error)) {
            if (in_array($error['type'], array(E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR))) {
                $logger = wc_get_logger();
                $logger->critical(
                    /* translators: 1: error message 2: file name and path 3: line number */
                    sprintf(__('%1$s in %2$s on line %3$s', 'woosfrest'), $error['message'], $error['file'], $error['line']) . PHP_EOL,
                    array(
                        'source' => 'fatal-errors',
                    )
                );
                do_action('woosfrest_shutdown_error', $error);
            }
        }
    }

    /**
     * Create logs for all Error messages, success messages and exceptions
     *
     * @param String $object Name of object for which log is to be generated (Category, User, Product, Order)
     * @param String $message The message to be logged
     * @param Boolean $isError Whether the message is an error or not, Optional, Default False
     *
     * @return Void
     */

    public static function createLog($object, $message, $isError = 0)
    {
        $pluginDir = WP_PLUGIN_DIR . '/woo-salesforce-connector/logs';
        $pluginDirForPermissionCheck = WP_PLUGIN_DIR . '/woo-salesforce-connector';

        if (is_dir($pluginDir)) {
            if ($object == 'Category') {
                $logfile = fopen($pluginDir . '/categoryLog.log', 'a');
            } else if ($object == 'Product') {
                $logfile = fopen($pluginDir . '/productLog.log', 'a');
            } else if ($object == 'Order') {
                $logfile = fopen($pluginDir . '/orderLog.log', 'a');
            } else if ($object == 'User') {
                $logfile = fopen($pluginDir . '/userLog.log', 'a');
            }
            $time = current_time("Y-m-d H:i:sa");
            if ($isError) {
                $message = $time . " ERROR " . json_encode($message) . "\n";
            } else {
                $message = $time . " INFO " . $message . "\n";
            }

            if (!empty($logfile) && is_resource($logfile) && $logfile) {
                fwrite($logfile, $message);
                fclose($logfile);
            }
        } else {
            if (is_writable($pluginDirForPermissionCheck)) {

                mkdir($pluginDir);
                self::createLog($object, $message, $isError);

            }
        }
    }

    /**
     * Open log files in read and write mode and call functions to delete data older than 7 days
     *
     * @param Void
     *
     * @return Void
     **/
    public static function getLogFileforDeletion()
    {
        $pluginDir = WP_PLUGIN_DIR . '/woo-salesforce-connector/logs';
        if (is_dir($pluginDir)) {
            $logfile = fopen($pluginDir . '/categoryLog.log', 'r+');
            $lines = self::getLogsOfPrevSevenDays($logfile);
            fclose($logfile);

            $logfile = fopen($pluginDir . '/categoryLog.log', 'w');
            self::overwriteLog($logfile, $lines);
            fclose($logfile);
            $logfile = fopen($pluginDir . '/productLog.log', 'r+');
            $lines = self::getLogsOfPrevSevenDays($logfile);
            fclose($logfile);

            $logfile = fopen($pluginDir . '/productLog.log', 'w');
            self::overwriteLog($logfile, $lines);
            fclose($logfile);
            $logfile = fopen($pluginDir . '/orderLog.log', 'r+');
            $lines = self::getLogsOfPrevSevenDays($logfile);
            fclose($logfile);

            $logfile = fopen($pluginDir . '/orderLog.log', 'w');
            self::overwriteLog($logfile, $lines);
            fclose($logfile);
            $logfile = fopen($pluginDir . '/userLog.log', 'r+');
            $lines = self::getLogsOfPrevSevenDays($logfile);
            fclose($logfile);

            $logfile = fopen($pluginDir . '/userLog.log', 'w');
            self::overwriteLog($logfile, $lines);
            fclose($logfile);
        }
    }

    /**
     * Get only logs from the previous seven days from the log file
     *
     * @param File $logfile Log file to fetch logs from
     *
     * @return Array/Void
     **/
    public static function getLogsOfPrevSevenDays($logfile)
    {
        if (is_resource($logfile)) {
            $date = new DateTime(current_time('Y-m-d'));
            $lines = array();
            while (!feof($logfile)) {
                $line = fgets($logfile, 4096);
                $linedate = new DateTime((substr($line, 0, 10)));
                $dateDiff = $date->diff($linedate);
                if ($dateDiff->days <= 7) {
                    if (!ctype_space($line)) {
                        $lines[] = $line;
                    }

                }
            }
            return $lines;
        }
    }

    /**
     * Overwrite existing log files with only logs from previous seven days from the log file
     *
     * @param File $logfile Log file to be overwritten
     * @param Array $lines Array of lines from log  file from previous seven days from the log file
     *
     * @return Void
     **/
    public static function overwriteLog($logfile, $lines)
    {
        if (is_resource($logfile)) {
            if (is_array($lines) && !empty($lines)) {
                foreach ($lines as $line) {
                    fwrite($logfile, $line);
                }
            }

        }
    }

    /**
     * Main WooCommerce Salesforce Connector REST API Instance.
     *
     * Ensures only one instance of WooCommerce Salesforce Connector REST API is loaded or can be loaded.
     *
     * @since 1.0
     * @static
     * @see wooSfRest()
     * @return WooSfRest - Main instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Create Notice as a message
     *
     * @param String $type    type of notice updated/warning/error
     * @param String $title   title of message
     * @param String $message message
     *
     * @return Void
     */
    public static function showNotice($type, $title, $message)
    {
        ?>
        <div class="<?php echo $type; ?> notice is-dismissible">
            <p>
                <strong>
                    <?php echo $title; ?>
                </strong>
                <?php echo $message; ?>.
            </p>
            <button class="notice-dismiss" type="button">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div>
<?php
}

    /**
     * Migrate Data from woosfrest tables to Meta tables for users updating from older versions
     *
     * @return Void
     */
    public static function migrateTableData()
    {

        global $wpdb;
        foreach ($wpdb->get_results("SELECT `sf_order_id`, `sf_opp_id`, `woo_order_id`, `error` FROM {$wpdb->prefix}woosfrest_orders") as $order) {
            update_post_meta($order->woo_order_id, 'wk_sf_order_id', $order->sf_order_id);
            update_post_meta($order->woo_order_id, 'wk_sf_opp_id', $order->sf_opp_id);
            if (isset($order->error) && !empty($order->error)) {
                update_post_meta($order->woo_order_id, 'wk_sf_order_err', $order->error);
            }

        }
        foreach ($wpdb->get_results("SELECT `sf_category_id`,`woo_category_id`, `error` FROM {$wpdb->prefix}woosfrest_categories") as $category) {
            update_term_meta($category->woo_category_id, 'wk_sf_category_id', $category->sf_category_id);
            if (isset($category->error) && !empty($category->error)) {
                update_term_meta($category->woo_category_id, 'wk_sf_category_error', $category->error);
            }

        }
        foreach ($wpdb->get_results("SELECT `sf_product_id`,`woo_product_id`,`error` FROM {$wpdb->prefix}woosfrest_products") as $product) {
            update_post_meta($product->woo_product_id, 'wk_sf_product_id', $product->sf_product_id);
            if (isset($product->error) && !empty($product->error)) {
                update_post_meta($product->woo_product_id, 'wk_sf_product_err', $product->error);
            }

        }
        foreach ($wpdb->get_results("SELECT `sf_account_id`,`sf_user_id`,`woo_user_id`,`error` FROM {$wpdb->prefix}woosfrest_users") as $user) {
            update_user_meta($user->woo_user_id, 'wk_sf_account_id', $user->sf_account_id);
            update_user_meta($user->woo_user_id, 'wk_sf_contact_id', $user->sf_user_id);
            if (isset($user->error) && !empty($user->error)) {
                update_user_meta($user->woo_user_id, 'wk_sf_account_error', $user->error);
            }

        }
    }

    /**
     * Migrate Data from Previously Used JSON objects to store Meta tables to individual meta storing
     *
     * @return Void
     */
    public static function migrateToNonJsonMeta()
    {

        global $wpdb;
        foreach ($wpdb->get_results("SELECT `post_id`, `meta_key`, `meta_value` FROM {$wpdb->prefix}postmeta WHERE `meta_key` = 'wk_woo_sforder' OR `meta_key`='wk_woo_sforder_err LIMIT 1'") as $order) {
            if ($order->meta_key == 'wk_woo_sforder') {
                $value = unserialize($order->meta_value);
                if (array_key_exists('sf_order_id', $value)) {
                    $res = update_post_meta($order->post_id, 'wk_sf_order_id', $value['sf_order_id']);
                }

                if (array_key_exists('sf_opp_id', $value)) {
                    $res = update_post_meta($order->post_id, 'wk_sf_opp_id', $value['sf_opp_id']);
                }

            } else if ($order->meta_key == 'wk_woo_sforder_err') {
                $res = update_post_meta($order->post_id, 'wk_sf_order_err', $order->meta_value);
            }
            if (!empty($res) && $res) {
                delete_post_meta($order->post_id, 'wk_woo_sforder');
                delete_post_meta($order->post_id, 'wk_woo_sforder_err');
            }
        }
        foreach ($wpdb->get_results("SELECT `user_id`, `meta_key`, `meta_value` FROM {$wpdb->prefix}usermeta WHERE `meta_key` = 'wk_sf_user_error'") as $user) {
            if (update_user_meta($user->user_id, 'wk_sf_account_error', $user->meta_value)) {
                delete_user_meta($user->user_id, 'wk_sf_user_error');
            }
        }
    }

    /**
     * Get count of salesforce object
     *
     * @return String
     */
    public static function getSObjectCount($ajaxCall = true, $sObject = null)
    {
        $ajaxCall = ($ajaxCall === '' || $ajaxCall === true) ? true : false;

        if ($sObject == null) {
            $sObject = $_REQUEST['sObject'];
        }

        if ($sObject) {
            $returnResponse = SalesforceConnector::getSObjectCount($sObject);
        } else {
            $returnResponse = 'Error: sObject not set.';
        }

        if ($ajaxCall) {
            echo $returnResponse;
        } else {
            return $returnResponse;
        }
        exit;
    }

    /*  added the method for account search box by Praveen */
    public function getAccountWithSearchData()
    {
        $salesforceData = get_option('wksalesforce_data');
        $wooSfRestAccountConfig = get_option('wooSfRestAccountConfig');

        if (isset($_REQUEST['search_data'])) {
            $search_data = $_REQUEST['search_data'];
            if (isset($salesforceData['AccountRecordType']) && !empty($salesforceData['AccountRecordType'])) {
                if (isset($wooSfRestAccountConfig->account_recordtype)) {
                    $accountRecordTypeId = $wooSfRestAccountConfig->account_recordtype;
                    $query = "SELECT Id, Name FROM Account Where RecordTypeId = '$accountRecordTypeId' AND Name LIKE '$search_data%'  ";
                } else {
                    $accountRecordTypeId = $salesforceData['AccountRecordType'][0]->value;
                    $query = "SELECT Id, Name FROM Account Where RecordTypeId ='$accountRecordTypeId' AND Name LIKE '$search_data%' ";
                }
            } else {
                $query = "SELECT Id, Name FROM Account Where  Name LIKE '$search_data%' ";
            }

            $salesforceConnector = new SalesforceConnector();
            $allRecordType_Ids = $salesforceConnector->getSObject($query, 0);
            $sfrecordType = [];
            if ($allRecordType_Ids) {
                foreach ($allRecordType_Ids as $key => $book) {
                    $sfrecordType[$key] = new stdclass();
                    $sfrecordType[$key]->text = $book->Name;
                    $sfrecordType[$key]->id = $book->Id;
                }
            }

            echo json_encode($sfrecordType);
            exit;
        } else {
            if (isset($salesforceData['AccountRecordType']) && !empty($salesforceData['AccountRecordType'])) {
                if (isset($wooSfRestAccountConfig->account_recordtype)) {
                    $accountRecordTypeId = $wooSfRestAccountConfig->account_recordtype;
                    $query = "SELECT Id, Name FROM Account Where RecordTypeId = '$accountRecordTypeId' Order By Name ";
                } else {
                    $accountRecordTypeId = $salesforceData['AccountRecordType'][0]->value;
                    $query = "SELECT Id, Name FROM Account Where RecordTypeId ='$accountRecordTypeId' Order By Name ";
                }
            } else {
                $query = "SELECT Id, Name FROM Account Order By Name  ";
            }

            $salesforceConnector = new SalesforceConnector();
            $allRecordType_Ids = $salesforceConnector->getSObject($query, 0);

            $sfrecordType = [];
            if ($allRecordType_Ids) {
                foreach ($allRecordType_Ids as $key => $book) {
                    $sfrecordType[$key] = new stdclass();
                    $sfrecordType[$key]->text = $book->Name;
                    $sfrecordType[$key]->id = $book->Id;
                }
            }

            echo json_encode($sfrecordType);
            exit;
        }
    }

    /*  added the method for pricebook search box by Praveen */
    public function getPricebookWithSearchData()
    {
        if (isset($_REQUEST['search_data'])) {
            $search_data = $_REQUEST['search_data'];

            $query = "SELECT Id, Name, isStandard FROM Pricebook2 where IsActive=true AND Name LIKE '$search_data%' ";

            $salesforceConnector = new SalesforceConnector();
            $pricebook = $salesforceConnector->getSObject($query, 0);
            $sfpricebook = [];
            if ($pricebook) {
                foreach ($pricebook as $key => $book) {
                    $sfpricebook[$key] = new stdclass();
                    $sfpricebook[$key]->text = $book->Name;
                    $sfpricebook[$key]->id = $book->Id;
                    $sfpricebook[$key]->standard = $book->IsStandard;
                }
            }

            echo json_encode($sfpricebook);
            exit;
        } else {
            $query = "SELECT Id, Name, isStandard FROM Pricebook2 where IsActive=true ";

            $salesforceConnector = new SalesforceConnector();
            $pricebook = $salesforceConnector->getSObject($query, 0);
            $sfpricebook = [];
            if ($pricebook) {
                foreach ($pricebook as $key => $book) {
                    $sfpricebook[$key] = new stdclass();
                    $sfpricebook[$key]->text = $book->Name;
                    $sfpricebook[$key]->id = $book->Id;
                    $sfpricebook[$key]->standard = $book->IsStandard;
                }
            }
            echo json_encode($sfpricebook);
            exit;
        }
    }

    /*  added the method for Document Folder search box by Praveen */
    public function getDocumentFolderWithSearchData()
    {
        if (isset($_REQUEST['search_data'])) {
            $search_data = $_REQUEST['search_data'];

            $query = "SELECT Id, Name, Type FROM Folder where Type='Document' AND Name LIKE '$search_data%' ";

            $salesforceConnector = new SalesforceConnector();
            $documentFolder = $salesforceConnector->getSObject($query, 0);
            $document = [];
            if ($documentFolder) {
                foreach ($documentFolder as $key => $book) {
                    $document[$key] = new stdclass();
                    $document[$key]->text = $book->Name;
                    $document[$key]->id = $book->Id;
                }
            }

            echo json_encode($document);
            exit;
        } else {
            $query = "SELECT Id, Name, Type FROM Folder where Type='Document'";

            $salesforceConnector = new SalesforceConnector();
            $documentFolder = $salesforceConnector->getSObject($query, 0);
            $document = [];
            if ($documentFolder) {
                foreach ($documentFolder as $key => $book) {
                    $document[$key] = new stdclass();
                    $document[$key]->text = $book->Name;
                    $document[$key]->id = $book->Id;
                }
            }

            echo json_encode($document);
            exit;
        }
    }

    //Fetch Accounts As Per Record Type added by Naincy
    public function getAccountViaRecordTypeId()
    {
        $wooSfRestAccountConfig = get_option('wooSfRestAccountConfig');
        $salesforceData = get_option('wksalesforce_data');

        $result = array();
        $outputguest = '';
        $output = '';
        $recordTypeId = $_REQUEST['recordId'];
        $query = "SELECT Id, Name FROM Account Where RecordTypeId = '$recordTypeId' Order By Name";
        $allRecordType_Ids = SalesforceConnector::getSObject($query, 0);
        $sfrecordType = [];
        if ($allRecordType_Ids) {
            foreach ($allRecordType_Ids as $key => $book) {
                $sfrecordType[$key] = new stdclass();
                $sfrecordType[$key]->text = $book->Name;
                $sfrecordType[$key]->value = $book->Id;
                $sfrecordType[$key]->id = $book->Id;
            }
            $salesforceData['Accounts'] = $sfrecordType;
        } else {
            $salesforceData['Accounts'] = 'No Account';
        }

        update_option('wksalesforce_data', $salesforceData);
        echo json_encode($sfrecordType);
        exit;
    }

    public static function uploadSfThumbnailFile($thumbnailId)
    {
        try {

            $thumbnailUrl = self::getSfAttachmentUrl($thumbnailId);

            if (!str_contains($thumbnailUrl, 'wp-content/uploads')) {
                $thumbnailUrl = wp_get_attachment_url($thumbnailId);
            }
            $imageTitle = '';
            if ($thumbnailUrl) {
                $imageTitle = substr($thumbnailUrl, strrpos($thumbnailUrl, '/') + 1);
            }

            $documentMedia = new stdClass();
            if ($imageTitle) {
                $documentMedia->Name = $imageTitle;
                $contentVersion = new stdClass();
                $contentVersion->Title = $imageTitle;
                $contentVersion->webkul_wws__woo_image_id__c = $thumbnailId;

                $query = "SELECT Id,Title from ContentVersion WHERE (PathOnClient='$imageTitle')";
                $contentDocumentExist = SalesforceConnector::getSObject($query);

                if (!$contentDocumentExist) {
                    //IsMajorVersion field set false to update content version.
                    $contentVersion->IsMajorVersion = false;
                    $contentVersion->VersionData = base64_encode(file_get_contents($thumbnailUrl));
                    $contentVersion->PathOnClient = $imageTitle;
                    $result = SalesforceConnector::insertSObject('ContentVersion', json_encode($contentVersion));

                    return $result;
                } else {
                    $Id = $contentDocumentExist->Id;

                    $result = SalesforceConnector::updateSObject('ContentVersion', $Id, json_encode($contentVersion));

                    if (!is_array($result)) {
                        return $contentDocumentExist->Id;
                    } else {
                        return $result;
                    }
                }
            }
        } catch (Exception $e) {
            $errorList[] = $e->getMessage();
            $errorFlag = true;
            $log = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();
            self::createLog('Product', __LINE__ . ' ' . $log, true);
            return false;
        }
    }

    public static function uploadSfMedia($thumbnailId)
    {
        try {
            $wooSfRestProductConfig = get_option('wooSfRestProductConfig');
            $thumbnailUrl = self::getSfAttachmentUrl($thumbnailId);
            $imageTitle = '';
            if ($thumbnailUrl) {
                $imageTitle = substr($thumbnailUrl, strrpos($thumbnailUrl, '/') + 1);
            }
            $documentMedia = new stdClass();
            if ($imageTitle) {
                $documentMedia->Name = $imageTitle;
            }
            $documentMedia->IsPublic = true;
            if (empty($wooSfRestProductConfig->imagefolder)) {
                return false;
            } else {
                $documentMedia->FolderId = $wooSfRestProductConfig->imagefolder;
            }
            if ($thumbnailUrl) {
                $documentMedia->Body = base64_encode(file_get_contents($thumbnailUrl));
            }

            //upsert
            if (isset($documentMedia->Name) && !empty($documentMedia->Name)) {
                $query = "SELECT Id FROM DOCUMENT WHERE Name='$documentMedia->Name'";
                $documentExist = SalesforceConnector::getSObject($query);
                if ($documentExist) {
                    $documentId = $documentExist->Id;
                    //update
                    $result = SalesforceConnector::updateSObject('Document', $documentId, json_encode($documentMedia));
                    if (!is_array($result)) {
                        return $documentId;
                    } else {
                        return $result;
                    }
                } else {
                    // insert
                    $result = SalesforceConnector::insertSObject('Document', json_encode($documentMedia));
                    return $result;
                }
            } else {
                // insert
                $result = SalesforceConnector::insertSObject('Document', json_encode($documentMedia));
                return $result;
            }
        } catch (Exception $e) {
            $errorList[] = $e->getMessage();
            $errorFlag = true;
            $log = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();

            self::createLog('Product', __LINE__ . ' ' . $log, true);
            return false;
        }
    }

    /**
     * Retrieve the URL for an attachment.
     *
     * @global String $pagenow
     *
     * @param Integer $postId Optional. Attachment ID. Default 0.
     *
     * @return String|false Attachment URL, otherwise false.
     */
    public static function getSfAttachmentUrl($postId = 0)
    {
        $postId = (int) $postId;
        if (!$post = get_post($postId)) {
            return false;
        }
        if ('attachment' != $post->post_type) {
            return false;
        }
        $url = '';
        $parent = getcwd();
        $r_path = str_replace("wp-admin", "wp-content/uploads", $parent);
        // Get attached file.
        if ($file = get_post_meta($post->ID, '_wp_attached_file', true)) {
            // Get upload directory.
            if (($uploads = wp_upload_dir()) && false === $uploads['error']) {
                // Check that the upload base exists in the file location.
                if (0 === strpos($file, $uploads['basedir'])) {
                    // Replace file location with url location.
                    $url = str_replace($uploads['basedir'], $r_path, $file);
                } elseif (false !== strpos($file, 'wp-content/uploads')) {
                    // Get the directory name relative to the basedir (back compat for pre-2.7 uploads)
                    $url = trailingslashit($r_path . '/' . _wp_get_attachment_relative_path($file)) . basename($file);
                } else {
                    // It's a newly-uploaded file, therefore $file is relative to the basedir.
                    $url = $r_path . "/$file";
                }
            }
        }

        /*
         * If any of the above options failed, Fallback on the GUID as used pre-2.7,
         * not recommended to rely upon this.
         */
        if (empty($url)) {
            $url = get_the_guid($post->ID);
        }

        // On SSL front-end, URLs should be HTTPS.
        if (is_ssl() && !is_admin() && 'wp-login.php' !== $GLOBALS['pagenow']) {
            $url = set_url_scheme($url);
        }
        if (empty($url)) {
            return false;
        }
        return $url;
    }

    /**
     * Import salesforce categories
     *
     * @return String
     */
    public static function importSfCategories($ajaxCall = true)
    {

        global $wpdb;
        $limit = 200;
        $errorFlag = false;
        $errorList = array();
        $ajaxCall = ($ajaxCall === '' || $ajaxCall === true) ? true : false;
        $categoryIds = isset($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : 0;
        $locator = isset($_REQUEST['locator']) && !empty($_REQUEST['locator']) ? $_REQUEST['locator'] : '';
        $processedItem = array('total' => 0, 'updated' => 0, 'added' => 0, 'locator' => '', 'error' => '');
        if (!empty($_REQUEST['limit'])) {
            $limit = $_REQUEST['limit'];
        }
        try {
            if (empty($categoryIds)) {

                $query = "SELECT Id, Name, webkul_wws__woo_category_id__c, webkul_wws__Description__c, webkul_wws__Parent_category__c, webkul_wws__Image_ID__c, webkul_wws__Slug__c FROM webkul_wws__woo_commerce_categories__c where Id>'$locator' order by webkul_wws__Parent_category__c LIMIT $limit";
            } else {
                $sfCategoryIds = array();
                foreach ($categoryIds as $categoryId) {
                    $sfCategoryIds[] = get_term_meta($categoryId, 'wk_sf_category_id', true);
                }
                $sfCategoryIds = '(\'' . implode('\',\'', $sfCategoryIds) . '\')';
                $query = "SELECT Id, Name, webkul_wws__woo_category_id__c, webkul_wws__Description__c, webkul_wws__Parent_category__c, webkul_wws__Image_ID__c, webkul_wws__Slug__c FROM webkul_wws__woo_commerce_categories__c WHERE ID in $sfCategoryIds";
            }
            $response = SalesforceConnector::getSObject($query, 0);

            if ($response) {
                foreach ($response as $sfCategory) {
                    $processedItem['locator'] = $sfCategory->Id;
                    if ($sfCategory->Id) {
                        $processedItem['total'] += 1;
                        $wooCategoryId = '';
                        if (!empty($sfCategory->webkul_wws__woo_category_id__c) && term_exists((int) $sfCategory->webkul_wws__woo_category_id__c)) {
                            $wooCategoryId = $sfCategory->webkul_wws__woo_category_id__c;
                        } else {
                            if ($wooCategoryId = term_exists($sfCategory->Name, 'product_cat')) {
                                $wooCategoryId = $wooCategoryId['term_id'];
                            }

                        }
                        $categoryId = self::createWooCommerceCategories($sfCategory);
                        if ($categoryId) {
                            if (!empty($wooCategoryId)) {
                                $processedItem['updated'] += 1;
                            } else {
                                $processedItem['added'] += 1;
                            }
                        }
                        if (!empty($categoryId) && $categoryId) {
                            if (empty($sfCategory->webkul_wws__woo_category_id__c) || $sfCategory->webkul_wws__woo_category_id__c != $categoryId) {
                                $catUp = new stdClass();
                                $catUp->webkul_wws__woo_category_id__c = $categoryId;
                                SalesforceConnector::updateSObject('webkul_wws__woo_commerce_categories__c', $sfCategory->Id, json_encode($catUp));
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $errorList[] = $e->getMessage();
            $errorFlag = true;
            $log = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();

            self::createLog('Category', $log, true);
            $processedItem['error'] = $errorList[0];
            $processedItem['updated'] += 1; //category was neither added nor updated
        }
        self::createLog('Category', 'Category Import Finished, Total Categories imported - ' . $processedItem['total'] . ' ,Updated - ' . $processedItem['updated'] . ' ,Inserted - ' . $processedItem['added'], false);
        if ($ajaxCall) {
            echo json_encode($processedItem);
        } else {
            return $processedItem;
        }
        exit;
    }

    /**
     * Create Woo categories from fetched salesforce category data
     *
     * @param Object Salesforce category Data
     * @return String/Boolean Result Category id on success, false on failure
     */
    public static function createWooCommerceCategories($sfCatgData)
    {
        try {
            $parent_term_id = 0;
            if (!empty($sfCatgData->webkul_wws__Parent_category__c)) {
                $query = "SELECT webkul_wws__woo_category_id__c FROM webkul_wws__woo_commerce_categories__c WHERE ID = '$sfCatgData->webkul_wws__Parent_category__c'";
                $response = SalesforceConnector::getSObject($query, 1);
                if (!empty($response->webkul_wws__woo_category_id__c)) {
                    $parent_term_id = (int) $response->webkul_wws__woo_category_id__c;
                }
            }

            $wooCategoryId = '';
            if (!empty($sfCatgData->webkul_wws__woo_category_id__c) && (term_exists((int) $sfCatgData->webkul_wws__woo_category_id__c))) {
                $wooCategoryId = $sfCatgData->webkul_wws__woo_category_id__c;
            } else {
                if ($wooCategoryId = term_exists($sfCatgData->Name, 'product_cat')) {
                    $wooCategoryId = $wooCategoryId['term_id'];
                }

            }
            if (empty($sfCatgData->Name)) {
                return false;
            }
            if (!term_exists($parent_term_id)) {
                $parent_term_id = 0;
            }
            if (!empty($wooCategoryId)) {

                $wooCategoryId = wp_update_term(
                    $wooCategoryId,
                    'product_cat',
                    array(
                        'parent' => $parent_term_id,
                        'name' => $sfCatgData->Name,
                    )
                );
                if (is_array($wooCategoryId)) {
                    $msg = "Import - Salesforce ID: " . $sfCatgData->Id . " inserted, Category ID: " . $wooCategoryId['term_id'];
                    self::createLog('Category', $msg, false);
                } else if (is_object($wooCategoryId)) {
                    foreach ($wooCategoryId->errors as $errors) {
                        foreach ($errors as $error) {
                            self::createLog('Category', $error, true);
                        }
                    }
                }
                self::createLog('Category', $msg, false);
            } else {
                $wooCategoryId = wp_insert_term(
                    $sfCatgData->Name,
                    'product_cat',
                    array(
                        'parent' => $parent_term_id,
                    )
                );
                if (is_array($wooCategoryId)) {
                    $msg = "Import - Salesforce ID: " . $sfCatgData->Id . " updated, Category ID: " . $wooCategoryId['term_id'];
                    self::createLog('Category', $msg, false);
                } else if (is_object($wooCategoryId)) {
                    foreach ($wooCategoryId->errors as $errors) {
                        foreach ($errors as $error) {
                            self::createLog('Category', $error, true);
                        }
                    }
                }
            }
            if (empty($wooCategoryId->errors) && !empty($wooCategoryId['term_id'])) {
                update_term_meta($wooCategoryId['term_id'], 'wk_sf_category_id', $sfCatgData->Id);
                delete_term_meta($wooCategoryId['term_id'], 'wk_sf_category_error');
                return $wooCategoryId['term_id'];
            } else {
                return false;
            }
        } catch (Exception $e) {
            $errorList[] = $e->getMessage();
            $errorFlag = true;
            $log = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();

            self::createLog('Category', $log, true);
            return false;
        }
    }

    /**
     * Get all WooCommerce category Ids
     *
     * @return Void
     */
    public static function getWooCategoryIds($sync)
    {
        global $wpdb;
        $sync = isset($sync) ? $sync : 'A';
        $args = array(
            'fields' => 'ids',
            'number' => 2,
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'category_Synced',
                    'compare' => 'NOT EXISTS',
                ),
            ),

        );

        if ($sync == 'S') {
            $args = array(
                'fields' => 'ids',
                'taxonomy' => "product_cat",
                'number' => '2',

                'hide_empty' => false,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'wk_sf_category_error',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => 'wk_sf_category_id',
                        'compare' => 'EXISTS',
                    ),
                    array(
                        'key' => 'category_Synced',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            );
        } else {
            if ($sync == 'E') {
                $args = array(
                    'fields' => 'ids',
                    'taxonomy' => "product_cat",
                    'number' => '2',

                    'hide_empty' => false,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'wk_sf_category_error',
                            'compare' => 'EXISTS',
                        ),
                        array(
                            'key' => 'category_Synced',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),

                );
            }
            if ($sync == 'U') {
                $args = array(
                    'fields' => 'ids',
                    'taxonomy' => "product_cat",
                    'hide_empty' => false,
                    'number' => '2',

                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'wk_sf_category_id',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'wk_sf_category_error',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'category_Synced',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                );
            }
        }
        $allCategories = get_terms($args);

        return $allCategories;
    }
    public static function getWooCategoryCount($sync)
    {
        global $wpdb;
        $sync = isset($sync) ? $sync : 'A';
        $args = array(
            'fields' => 'ids',
            'taxonomy' => "product_cat",
            'hide_empty' => false,
            'meta_query' => array(
                'key' => 'category_Synced',
                'compare' => 'NOT EXISTS',
            ),
        );

        if ($sync == 'S') {
            $args = array(
                'fields' => 'ids',
                'taxonomy' => "product_cat",
                'hide_empty' => false,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'wk_sf_category_error',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => 'wk_sf_category_id',
                        'compare' => 'EXISTS',
                    ),
                    array(
                        'key' => 'category_Synced',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            );
        } else {
            if ($sync == 'E') {
                $args = array(
                    'fields' => 'ids',
                    'taxonomy' => "product_cat",
                    'hide_empty' => false,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'wk_sf_category_error',
                            'compare' => 'EXISTS',
                        ),
                        array(
                            'key' => 'category_Synced',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),

                );
            }
            if ($sync == 'U') {
                $args = array(
                    'fields' => 'ids',
                    'taxonomy' => "product_cat",
                    'hide_empty' => false,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'wk_sf_category_id',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'wk_sf_category_error',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'category_Synced',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                );
            }
        }
        $allCategories = get_terms($args);
        return $allCategories;
    }
    public static function wooSfExportCategoryData($syncType)
    {

        $dataIds = self::getWooCategoryIds($syncType);
        if (empty($dataIds)) {
            return false;
        }

        foreach ($dataIds as $item) {
            $cancel_category_sync = get_option('delete_category');

            if ($cancel_category_sync) {
                delete_option('delete_category');
                return false;
            }
            self::wooSfExportCategory(false, $item);
            update_term_meta($item, 'category_Synced', true);
            $processed_category = get_option('category_processed');
            update_option('category_processed', $processed_category + 1);
        }
        return $syncType;
    }

    /**
     * Export category to salesforce
     *
     * @param Integer $categoryId
     *
     * @return Void
     */
    public static function wooSfExportCategory($ajaxCall = true, $categoryId = 0)
    {

        $wooSfRestProductConfig = get_option('wooSfRestProductConfig');
        $ajaxCall = ($ajaxCall === '' || $ajaxCall === true) ? true : false;
        $categoryId = isset($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : $categoryId;
        $errorList = array();
        $errorFlag = false;
        $processedItem = array('total' => 0, 'updated' => 0, 'added' => 0, 'errorsValue' => 0, 'sf_category_id' => '', 'error' => '', 'syncc_time' => '');
        $wooSfObject = array();
        if (!isset($categoryId)) {
            if (!$ajaxCall) {
                return array('error' => "No category id");
            }
            $errorFlag = true;
            $errorList[] = "No category id";
        } else {
            try {
                $categoryData = self::getCategoryData($categoryId);
                $category = $categoryData[0];
                $processedItem['total'] += 1;
                $categoryRecords = new stdClass();
                $categoryRecords->Name = $category->name;
                $categoryRecords->webkul_wws__Slug__c = $category->slug;
                $categoryRecords->webkul_wws__woo_category_id__c = $category->term_id;
                $categoryRecords->webkul_wws__Description__c = $category->description;
                if (!empty($category->parent)) {
                    $sfParentCategoryId = self::getWooSfParentCategoryId($category->parent);
                    $categoryRecords->webkul_wws__Parent_category__c = $sfParentCategoryId;
                }
                if ($thumbnailId = self::getCategoryThumbnailId($category->term_id)) {
                    if ($wooSfRestProductConfig->sync_to_files) {
                        $imageDoc = self::uploadSfThumbnailFile($thumbnailId);
                        if (!empty($imageDoc) && !is_array($imageDoc) && $imageDoc) {
                            $categoryRecords->webkul_wws__ContentDocumentId__c = $imageDoc;
                            $categoryRecords->webkul_wws__Image_ID__c = $imageDoc;
                            $log = 'Category Id: ' . $category->term_id . ' Notes and Attachment added, Id: ' . $imageDoc;
                            self::createLog('Category', $log, false);
                        } else {
                            if (is_array($imageDoc)) {
                                foreach ($imageDoc as $err) {
                                    $log = 'Category Id: ' . $category->term_id . ' Notes and Attachment add Failed, Error: ' . $err;
                                    self::createLog('Category', $log, true);
                                }
                            } else {
                                $log = 'Category Id: ' . $category->term_id . ' Notes and Attachment add Failed';
                                self::createLog('Category', $log, true);
                            }
                        }
                    } else {
                        $imageDoc = self::uploadSfMedia($thumbnailId);
                        if (!empty($imageDoc) && !is_array($imageDoc) && $imageDoc) {
                            $categoryRecords->webkul_wws__ContentDocumentId__c = '';
                            $categoryRecords->webkul_wws__Image_ID__c = $imageDoc;
                            $log = 'Category Id: ' . $category->term_id . ' Document Media uploaded, Id: ' . $imageDoc;
                            self::createLog('Category', $log, false);
                        } else {
                            if (is_array($imageDoc)) {
                                foreach ($imageDoc as $err) {
                                    $log = 'ERROR Category Id: ' . $category->term_id . ' Document Media Upload Failed, Error: ' . $err;
                                    self::createLog('Category', $log, true);
                                }
                            } else {
                                $log = 'ERROR Category Id: ' . $category->term_id . ' Document Media Upload Failed';
                                self::createLog('Category', $log, true);
                            }
                        }
                    }
                }
                //upsert
                $query = "SELECT Id FROM webkul_wws__woo_commerce_categories__c WHERE webkul_wws__woo_category_id__c=$category->term_id";
                $sfCategoryExist = SalesforceConnector::getSObject($query, 1);
                if ($sfCategoryExist) {
                    //update
                    $sfCategoryExist = $sfCategoryExist->Id;
                    $result = SalesforceConnector::updateSObject('webkul_wws__woo_commerce_categories__c', $sfCategoryExist, json_encode($categoryRecords));
                    if (is_array($result)) {
                        $errorFlag = true;
                        $errorList[] = $result[0];
                        foreach ($errorList as $error) {
                            self::createLog('Category', $error, false);
                        }

                    } else {
                        $sfCategoryId = $sfCategoryExist;
                        $processedItem['updated'] += 1;
                        $msg = 'Export - Category ID ' . $category->term_id . ' updated, Salesforce id: ' . $sfCategoryId;
                        self::createLog('Category', $msg, false);
                    }
                } else {
                    // insert
                    $result = SalesforceConnector::insertSObject('webkul_wws__woo_commerce_categories__c', json_encode($categoryRecords));
                    if (!is_array($result)) {
                        $sfCategoryId = $result;
                        $processedItem['added'] += 1;
                        $msg = 'Export - Category ID ' . $category->term_id . ' inserted, Salesforce id: ' . $sfCategoryId;
                        self::createLog('Category', $msg, false);
                    } else {
                        $errorFlag = true;
                        $errorList[] = $result[0];
                        foreach ($errorList as $error) {
                            self::createLog('Category', $error, true);
                        }

                    }
                }

                if (!$errorFlag && isset($sfCategoryId)) {
                    /* Link document link to category in case of files */
                    if ($wooSfRestProductConfig->sync_to_files) {
                        if (isset($imageDoc)) {
                            self::createContentDocumentLink($sfCategoryId, $imageDoc);
                        }
                    }
                    $time_fraction = time();
                    $dateFormat = get_option('date_format');
                    $timeFormat = get_option('time_format');
                    $processedItem['syncc_time'] = date($dateFormat . ' ' . $timeFormat, $time_fraction);
                    $processedItem['sf_category_id'] = $sfCategoryId;
                    $wooSfObject['sf_category_id'] = $sfCategoryId;
                    $wooSfObject['woo_category_id'] = $categoryRecords->webkul_wws__woo_category_id__c;
                    $wooSfObject['sync_time'] = $time_fraction;
                    $wooSfObject['status'] = '1';
                    $wooSfObject['error'] = '';
                }
            } catch (Exception $e) {
                $errorList[] = $e->getMessage();
                $errorFlag = true;
                $log = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();

                self::createLog('Category', $log, true);
            }
        }
        if (!$errorFlag && !empty($sfCategoryId)) {
            update_term_meta($category->term_id, 'wk_sf_category_id', $sfCategoryId);
            delete_term_meta($category->term_id, 'wk_sf_category_error');
        } else {
            $processedItem['error'] = implode(',', $errorList);
            $processedItem['errorsValue'] += 1;
            update_term_meta($category->term_id, 'wk_sf_category_error', $processedItem['error']);
        }
        if (!$ajaxCall) {
            return json_encode($processedItem);
        } else {
            echo json_encode($processedItem);
            exit;
        }
    }

    public static function getWooSfParentCategoryId($parentCategoryId)
    {
        $wooSfObject = array();
        try {
            $wooSfRestProductConfig = get_option('wooSfRestProductConfig');
            $sfParentCategoryId = get_term_meta($parentCategoryId, 'wk_sf_category_id', true);
            if (!empty($sfParentCategoryId)) {
                return $sfParentCategoryId;
            } else {
                $categoryData = self::getCategoryData($parentCategoryId);
                $category = $categoryData[0];
                $categoryRecords = new stdClass();
                $categoryRecords->Name = $category->name;
                $categoryRecords->webkul_wws__Slug__c = $category->slug;
                $categoryRecords->webkul_wws__woo_category_id__c = $category->term_id;
                $categoryRecords->webkul_wws__Description__c = $category->description;

                if (!empty($category->parent)) {
                    $sfParentCategoryId = self::getWooSfParentCategoryId($category->parent);
                    $categoryRecords->webkul_wws__Parent_category__c = $sfParentCategoryId;
                }
                if ($thumbnailId = self::getCategoryThumbnailId($category->term_id)) {
                    if ($wooSfRestProductConfig->sync_to_files) {
                        if ($imageDoc = self::uploadSfThumbnailFile($thumbnailId)) {
                            $categoryRecords->webkul_wws__ContentDocumentId__c = $imageDoc;
                            $categoryRecords->webkul_wws__Image_ID__c = $imageDoc;
                        }
                    } else {
                        if ($imageDoc = self::uploadSfMedia($thumbnailId)) {
                            $categoryRecords->webkul_wws__ContentDocumentId__c = '';
                            $categoryRecords->webkul_wws__Image_ID__c = $imageDoc;
                        }
                    }
                }

                //upsert
                $sfCategoryExist = get_term_meta($category->term_id, 'wk_sf_category_id', true);
                if ($sfCategoryExist) {
                    //update
                    $result = SalesforceConnector::updateSObject('webkul_wws__woo_commerce_categories__c', $sfCategoryExist, json_encode($categoryRecords));
                    if (is_array($result)) {
                        $errorList[] = $result[0];
                        foreach ($errorList as $error) {
                            self::createLog('Category', $error, true);
                        }

                        return false;
                    } else {
                        $msg = 'Category ID ' . $category->term_id . ' updated, Salesforce id: ' . $result;
                        self::createLog('Category', $msg, false);
                    }
                } else {
                    // insert
                    $result = SalesforceConnector::insertSObject('webkul_wws__woo_commerce_categories__c', json_encode($categoryRecords));
                    if (!is_array($result)) {
                        $sfCategoryId = $result;
                        $msg = 'Category ID ' . $category->term_id . ' inserted, Salesforce id: ' . $sfCategoryId;
                        self::createLog('Category', $msg, false);
                    } else {
                        $errorList[] = $result[0];
                        foreach ($errorList as $error) {
                            self::createLog('Category', $error, true);
                        }

                        return false;
                    }
                }
                if ($wooSfRestProductConfig->sync_to_files && !empty($imageDoc)) {
                    self::createContentDocumentLink($sfCategoryId, $imageDoc);
                }
                update_term_meta($categoryRecords->webkul_wws__woo_category_id__c, 'wk_sf_category_id', $sfCategoryId);
                return $sfCategoryId;
            }
        } catch (Exception $e) {
            $errorList[] = $e->getMessage();
            $errorFlag = true;
            $log = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();

            self::createLog('Category', $log, true);
        }
    }

    /**
     * Get category data
     *
     * @param Integer $categoryId
     *
     * @return Object
     */
    public static function getCategoryData($categoryId)
    {
        global $wpdb;

        $cat_data = $wpdb->get_results("SELECT * FROM $wpdb->terms as trms INNER JOIN $wpdb->term_taxonomy trms_tax ON trms.term_id = trms_tax.term_id  WHERE trms_tax.taxonomy='product_cat' AND trms.term_id=" . $categoryId);
        return $cat_data;
    }

    public static function getCategoryThumbnailId($term_id)
    {
        global $wpdb;
        $thumbnailId = get_term_meta($term_id, 'thumbnail_id', true);
        if ($thumbnailId) {
            return $thumbnailId;
        } else {
            return false;
        }
    }

    /*----- Product Related -----*/

    public static function importSfProducts()
    {
        $errorList = array();
        $errorFlag = false;
        global $wpdb;
        $limit = 200;
        $productID = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : 0;
        if (!empty($_REQUEST['limit'])) {
            $limit = $_REQUEST['limit'];
        }
        $productMappedFields = self::getProductMappedFields();
        $productAttrFields = self::getProductMappedAttributes();
        $locator = isset($_REQUEST['locator']) && !empty($_REQUEST['locator']) ? $_REQUEST['locator'] : '';
        try {
            $cols = array(
                'Id',
                'Description',
                'IsActive',
                'Name',
                'ProductCode',
                'webkul_wws__woo_Stock__c',
                'webkul_wws__woo_Product_SKu__c',
                'webkul_wws__woo_Post_type__c',
                'webkul_wws__woo_Thumbnail_ID__c',
                'webkul_wws__woo_product_width__c',
                'webkul_wws__woo_product_weight__c',
                'webkul_wws__woo_post_Mime_Type__c',
                'webkul_wws__woo_Post_id__c',
                'webkul_wws__woo_product_height__c',
                'webkul_wws__woo_Post_excerpt__c',
                'webkul_wws__woo_Post_Date__c',
                'webkul_wws__woo_Post_Content__c',
                'webkul_wws__woo_Post_Author__c',
                'webkul_wws__woo_Menu_Order__c',
                'webkul_wws__woo_Comment_Status__c',
                'webkul_wws__woo_product_length__c',
                'webkul_wws__woo_comment_count__c',
                'webkul_wws__woo_post_Status__c',
                'webkul_wws__Woo_Post_Description__c',
            );
            if (isset($productMappedFields) && !empty($productMappedFields)) {
                foreach ($productMappedFields as $index => $value) {
                    if (!in_array($value['sffield'], $cols)) {
                        $cols[] = $value['sffield'];
                    }
                }
            }
            if (isset($productAttrFields) && !empty($productAttrFields)) {
                foreach ($productAttrFields as $index => $value) {
                    if (!in_array($value['sffield'], $cols)) {
                        $cols[] = $value['sffield'];
                    }
                }
            }
            $cols = implode(', ', $cols);
            $getProductQuery = '';
            if (empty($productID)) {
                $getProductQuery = "SELECT $cols FROM Product2 WHERE Id>'$locator' order by Id LIMIT $limit";
            } else {
                $sfIds = array();
                foreach ($productID as $key => $proId) {
                    if (metadata_exists('post', $proId, 'wk_sf_product_id')) {
                        $sfIds[] = get_post_meta($proId, 'wk_sf_product_id', true);
                    }
                }

                $sfIds = '(\'' . implode('\',\'', $sfIds) . '\')';
                $getProductQuery = "SELECT $cols FROM Product2 WHERE ID in $sfIds";
            }
            $sfProducts = SalesforceConnector::getSObject($getProductQuery, 0);

            $processedItem = array('total' => 0, 'updated' => 0, 'added' => 0, 'locator' => '');
            if (isset($sfProducts) && !empty($sfProducts)) {
                foreach ($sfProducts as $product) {
                    $processedItem['locator'] = $product->Id;
                    $processedItem['total'] += 1;
                    $productArray = array();
                    $productArray['post_type'] = 'product';
                    if (!empty($product->webkul_wws__woo_Post_excerpt__c)) {
                        $productArray['post_excerpt'] = $product->webkul_wws__woo_Post_excerpt__c;
                    } elseif (!empty($product->webkul_wws__Woo_Post_Description__c)) {
                        $productArray['post_excerpt'] = $product->webkul_wws__Woo_Post_Description__c;
                    }

                    if (!empty($product->webkul_wws__woo_Post_id__c)) {
                        $productArray['ID'] = $product->webkul_wws__woo_Post_id__c;
                        if (!self::checkIfPostExists($productArray['ID'])) {
                            unset($productArray['ID']);
                        }
                    }
                    if (!empty($product->webkul_wws__woo_post_Mime_Type__c)) {
                        $productArray['post_mime_type'] = $product->webkul_wws__woo_post_Mime_Type__c;
                    }

                    if (!empty($product->Name)) {
                        $productArray['post_name'] = $product->Name;
                        $productArray['post_title'] = $product->Name;
                    }
                    if (!empty($product->webkul_wws__woo_Post_Author__c)) {
                        $productArray['post_author'] = $product->webkul_wws__woo_Post_Author__c;
                    } else {
                        $productArray['post_author'] = '1';
                    }

                    if (!empty($product->webkul_wws__woo_post_Status__c)) {
                        $productArray['post_status'] = $product->webkul_wws__woo_post_Status__c;
                    } else {
                        if (!empty($product->IsActive)) {
                            $productArray['post_status'] = 'publish';
                        } else {
                            $productArray['post_status'] = 'draft';
                        }
                    }

                    if (!empty($product->webkul_wws__Woo_Post_Description__c)) {
                        $productArray['post_content'] = $product->webkul_wws__Woo_Post_Description__c;
                    }

                    if (!empty($product->webkul_wws__woo_Post_Date__c)) {
                        $productArray['post_date'] = $product->webkul_wws__woo_Post_Date__c;
                    } else {
                        $productArray['post_date'] = current_time('mysql', 0);
                    }

                    if (!empty($product->webkul_wws__woo_Menu_Order__c)) {
                        $productArray['menu_order'] = $product->webkul_wws__woo_Menu_Order__c;
                    } else {
                        $productArray['menu_order'] = 0;
                    }

                    if (!empty($product->webkul_wws__woo_Comment_Status__c)) {
                        $productArray['comment_status'] = $product->webkul_wws__woo_Comment_Status__c;
                    } else {
                        $productArray['comment_status'] = 'open';
                    }

                    if (!empty($product->comment_count)) {
                        $productArray['comment_count'] = $product->comment_count;
                    } else {
                        $productArray['comment_count'] = 0;
                    }

                    // Insert the post into the database
                    if (!empty($productArray['ID']) && $productArray['ID'] != 0) {
                        $productArray['ID'] = (int) $productArray['ID'];
                        $product_ID = wp_update_post($productArray);
                        $msg = 'Import - Product Salesforce Id: ' . $product->Id . ' updated, Product Id: ' . $product_ID;
                        self::createLog('Product', __LINE__ . ' ' . $msg, false);
                    } else {
                        $product_ID = wp_insert_post($productArray);
                        $msg = 'Import - Product Salesforce Id: ' . $product->Id . ' updated, Product Id: ' . $product_ID;
                        self::createLog('Product', __LINE__ . ' ' . $msg, false);
                    }

                    if (empty($product->webkul_wws__woo_Post_id__c) || $product->webkul_wws__woo_Post_id__c != $product_ID) {
                        $productDetails = new stdClass();
                        $productDetails->webkul_wws__woo_Post_id__c = $product_ID;
                        $result = SalesforceConnector::updateSObject('Product2', $product->Id, json_encode($productDetails));
                        if (is_array($result)) {
                            $errorFlag = true;
                            $errorList[] = $result[0];
                        }
                    }

                    if ($product_ID) {
                        $wooSfRestProductConfig = get_option('wooSfRestProductConfig');
                        $pricebook = $wooSfRestProductConfig->pricebook;
                        if (!isset($pricebook)) {
                            $pbQuery = "SELECT Id FROM Pricebook2 where IsStandard=true";
                            if ($standardPriceBookData = SalesforceConnector::getSObject($pbQuery)) {
                                $pricebook = $standardPriceBookData->Id;
                            }
                        }

                        if (isset($productMappedFields) && !empty($productMappedFields)) {
                            foreach ($productMappedFields as $index => $value) {
                                $sffield = $value['sffield'];
                                $sfValue = !empty($product->$sffield) ? $product->$sffield : '';
                                if (!empty($sfValue)) {
                                    update_post_meta($product_ID, $value['woofield'], $sfValue);
                                }
                            }
                        }
                        if (isset($productAttrFields) && !empty($productAttrFields)) {
                            foreach ($productAttrFields as $index => $value) {
                                $field_name = $value['sffield'];
                                $sfValue = !empty($product->$field_name) ? $product->$field_name : '';
                                if (!empty($sfValue)) {
                                    $attrVals = array();
                                    $pro_obj = wc_get_product($product->webkul_wws__woo_Post_id__c);
                                    if (is_object($pro_obj)) {
                                        foreach ($pro_obj->get_attributes() as $attr) {
                                            foreach ($attr->get_terms() as $val) {
                                                $attrVals[] = $val->name;
                                            }
                                        }
                                    }
                                    wp_remove_object_terms($product_ID, $attrVals, $value['woofield']);
                                    $err_term = wp_add_object_terms($product_ID, explode('|', $sfValue), $value['woofield']);
                                    $val_array = get_post_meta($product_ID, '_product_attributes', true);
                                    if (empty($val_array)) {
                                        $val_array = array();
                                    }
                                    if (!isset($val_array[$value['woofield']])) {
                                        $val_array[$value['woofield']] = array('name' => $value['woofield'], 'value' => '', 'position' => 0, 'is_visible' => 0, 'is_variation' => 0, 'is_taxonomy' => 1);
                                    }
                                    update_post_meta($product_ID, '_product_attributes', $val_array);
                                }
                            }
                        }
                        $query = "SELECT Id, UnitPrice FROM PricebookEntry where Product2Id='" . $product->Id . "' and Pricebook2Id='" . $pricebook . "'";
                        $salesforceProduct = SalesforceConnector::getSObject($query);
                        $unitPrice = 0;
                        if (!empty($salesforceProduct)) {
                            $unitPrice = $salesforceProduct->UnitPrice;
                        }
                        update_post_meta($product_ID, '_regular_price', $unitPrice);

                        update_post_meta($product_ID, '_price', $unitPrice);
                        if (!empty($product->webkul_wws__woo_Stock__c)) {
                            update_post_meta($product_ID, '_stock', $product->webkul_wws__woo_Stock__c);
                            if ($product->webkul_wws__woo_Stock__c > 0) {
                                update_post_meta($product_ID, '_manage_stock', 'yes');
                                update_post_meta($product_ID, '_stock_status', 'instock');
                            }
                        }
                        if (!empty($product->ProductCode)) {
                            update_post_meta($product_ID, '_sku', $product->ProductCode);
                        }

                        if (!empty($product->webkul_wws__woo_product_weight__c)) {
                            update_post_meta($product_ID, '_weight', $product->webkul_wws__woo_product_weight__c);
                        }

                        if (!empty($product->webkul_wws__woo_product_length__c)) {
                            update_post_meta($product_ID, '_length', $product->webkul_wws__woo_product_length__c);
                        }

                        if (!empty($product->webkul_wws__woo_product_width__c)) {
                            update_post_meta($product_ID, '_width', $product->webkul_wws__woo_product_width__c);
                        }

                        if (!empty($product->webkul_wws__woo_product_height__c)) {
                            update_post_meta($product_ID, '_height', $product->webkul_wws__woo_product_height__c);
                        }

                        $wooSfProductId = get_post_meta($product_ID, 'wk_sf_product_id', true);

                        if (isset($wooSfProductId) && !empty($wooSfProductId)) {
                            $processedItem['updated'] += 1;
                        } else {
                            $processedItem['added'] += 1;
                        }

                        update_post_meta($product_ID, 'wk_sf_product_id', $product->Id);
                        delete_post_meta($product_ID, 'wk_sf_product_err');
                        //import Product category Mappings
                        $query = "SELECT id, webkul_wws__woo_commerce_categories__c FROM webkul_wws__Product_Category_Mapping__c WHERE webkul_wws__Product__c='$product->Id'";
                        $prductCategoryMappings = SalesforceConnector::getSObject($query, 0);
                        $categoryIds = array();
                        if (isset($prductCategoryMappings) && !empty($prductCategoryMappings)) {
                            foreach ($prductCategoryMappings as $mapping) {
                                $query4 = "SELECT Id,Name,webkul_wws__Category_Id__c,webkul_wws__Parent_category__c,webkul_wws__Slug__c, webkul_wws__Image_ID__c,webkul_wws__Description__c FROM webkul_wws__woo_commerce_categories__c WHERE Id='$mapping->webkul_wws__woo_commerce_categories__c'";
                                $wooCategoryDatas = SalesforceConnector::getSObject($query4, 0);
                                foreach ($wooCategoryDatas as $wooCategoryData) {
                                    if (!empty($wooCategoryData->webkul_wws__Category_Id__c)) {
                                        $categoryIds[] = (int) $wooCategoryData->webkul_wws__Category_Id__c;
                                    } else {
                                        $wooCategoryId = self::createWooCommerceCategories($wooCategoryData);
                                        if (!empty($wooCategoryId) && $wooCategoryId) {
                                            $categoryIds[] = $wooCategoryId;
                                        }

                                    }
                                }
                            }
                        }
                        if (count($categoryIds) != 0) {
                            wp_set_object_terms($product_ID, $categoryIds, 'product_cat');
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $errorList[] = $e->getMessage();
            $errorFlag = true;
            $log = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();

            self::createLog('Product', __LINE__ . ' ' . $log, true);
            $processedItem['error'] = $$errorList[0];
        }
        if ($errorFlag) {
            $errorList = (array) $errorList;
            $processedItem['error'] = implode(',', $errorList);
            update_post_meta($product_ID, 'wk_sf_product_err', $processedItem['error']);

        }
        self::createLog('Product', __LINE__ . ' ' . 'Product Import Finished, Total Products imported - ' . $processedItem['total'] . ' ,Updated - ' . $processedItem['updated'] . ' ,Inserted - ' . $processedItem['added'], false);
        update_option('allow_import', true);
        echo json_encode($processedItem);
        exit;
    }
/**
 * Get all woocommerce product ids
 *
 * @return Void
 */
    public static function getWooProductIds($syncType)
    {
        global $wpdb;
        $sync = isset($syncType) ? $syncType : 'A';
        if ($sync == "A") {
            $args = array(
                'fields' => 'ids',
                'posts_per_page' => 2,
                'post_type' => 'product',
                'post_status' => array('publish', 'draft'),
                'orderby' => 'ID',
                'order' => 'DESC',
                'meta_key' => 'product_Synced',
                'meta_compare' => 'NOT EXISTS',
            );
        } elseif ($sync == "S") {
            $args = array(
                'fields' => 'ids',
                'posts_per_page' => 2,
                'post_type' => 'product',
                'post_status' => array('publish', 'draft'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'product_Synced',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => 'wk_sf_product_id',
                        ),
                        array(
                            'key' => 'wk_sf_product_err',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                ),
            );
        } elseif ($sync == "U") {
            $args = array(
                'fields' => 'ids',
                'posts_per_page' => 2,
                'post_type' => 'product',
                'post_status' => array('publish', 'draft'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'product_Synced',
                        'compare' => 'NOT EXISTS',
                    ),

                    array(
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
                ),
            );
        } elseif ($sync == "E") {
            $args = array(
                'fields' => 'ids',
                'posts_per_page' => 2,
                'post_type' => 'product',
                'post_status' => array('publish', 'draft'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'product_Synced',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' =>
                        'wk_sf_product_err'),
                ),
            );
        } else {
            echo json_encode(array());
            exit;
        }
        $allProductsIds = get_posts($args);
        return $allProductsIds;

        if (isset($_GET['page']) && isset($_GET['export']) && isset($_GET['_wpnonce'])) {
            return $allProductsIds;
        } else {
            echo json_encode($allProductsIds);
            exit;
        }

    }
    public static function getWooProductIdsCount($syncType)
    {
        global $wpdb;
        $sync = isset($syncType) ? $syncType : 'A';
        if ($sync == "A") {
            $args = array(
                'fields' => 'ids',
                'posts_per_page' => -1,
                'post_type' => 'product',
                'post_status' => array('publish', 'draft'),
                'orderby' => 'ID',
                'order' => 'DESC',
                'meta_key' => 'product_Synced',
                'meta_compare' => 'NOT EXISTS',
            );
        } elseif ($sync == "S") {
            $args = array(
                'fields' => 'ids',
                'posts_per_page' => -1,
                'post_type' => 'product',
                'post_status' => array('publish', 'draft'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'product_Synced',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => 'wk_sf_product_id',
                        ),
                        array(
                            'key' => 'wk_sf_product_err',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                ),
            );
        } elseif ($sync == "U") {
            $args = array(
                'fields' => 'ids',
                'posts_per_page' => -1,
                'post_type' => 'product',
                'post_status' => array('publish', 'draft'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'product_Synced',
                        'compare' => 'NOT EXISTS',
                    ),

                    array(
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
                ),
            );
        } elseif ($sync == "E") {
            $args = array(
                'fields' => 'ids',
                'posts_per_page' => -1,
                'post_type' => 'product',
                'post_status' => array('publish', 'draft'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'product_Synced',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' =>
                        'wk_sf_product_err'),
                ),
            );
        } else {
            echo json_encode(array());
            exit;
        }
        $allProductsIds = get_posts($args);
        return $allProductsIds;

        if (isset($_GET['page']) && isset($_GET['export']) && isset($_GET['_wpnonce'])) {
            return $allProductsIds;
        } else {
            echo json_encode($allProductsIds);
            exit;
        }

    }
    public static function wooSfExportProductData($syncType)
    {

        $dataIds = self::getWooProductIds($syncType);

        if (empty($dataIds)) {
            return false;
        }

        foreach ($dataIds as $item) {

            $cancel_product_sync = get_option('delete_product');

            if ($cancel_product_sync) {
                delete_option('delete_product');
                return false;
            }
            self::wooSfExportProduct(false, $item);
            update_post_meta($item, 'product_Synced', true);
            $processed_product = get_option('products_processed');
            update_option('products_processed', $processed_product + 1);

        }
        return $syncType;
    }
    public static function wooSfExportProduct($ajaxCall = true, $productId = 0)
    {

        $ajaxCall = ($ajaxCall === '' || $ajaxCall === true) ? true : false;
        $productId = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : $productId;
        $errorFlag = false;
        $errorList = array();
        $processedItem = array('total' => 0, 'sf_product_id' => '', 'updated' => 0, 'added' => 0, 'errorsValue' => 0, 'category_synchronised' => false, 'syncc_time' => '');

        if (empty($productId)) {
            if (!$ajaxCall) {
                return array('error' => "No product id");
            }
            echo "No product id";
            exit;
        }

        $productExists = get_post_status($productId);

        if (!$productExists) {
            if (!$ajaxCall) {
                return array('error' => "This order contains product that has been deleted from woocommerce, Order items mismatch may occur in Salesforce order");
            }
            $errorFlag = true;
            $errorList[] = "The product has either been deleted from woocommerce or does not exist.";
        } else {
            $wooSfRestProductConfig = get_option('wooSfRestProductConfig');
            $pricebook = $wooSfRestProductConfig->pricebook;
            if (!isset($pricebook)) {
                if (!$ajaxCall) {
                    return array('error' => "<b><i>Pricebook Error: </i></b> Pricebook Unavailable");
                }
                $errorFlag = true;
                $errorList[] = "<b><i>Pricebook Error: </i></b> Pricebook unavailable";
            }
        }
        if (!$errorFlag) {
            try {
                $product = wc_get_product($productId);
                $processedItem['total'] += 1;
                $pId = $product->get_id();
                $product_ID_cat = $pId;
                $productPostData = get_post($productId);
                $categoryArray = array();
                if ($wooSfRestProductConfig->sync_data) {
                    $categoryArray = self::getWooProductCategories($product_ID_cat);
                }

                if (!empty($categoryArray)) {
                    foreach ($categoryArray as $category) {
                        $wooCategoryId = $category->term_id;
                        if ($wooCategoryId) {
                            $existsCategoryMergeId = get_term_meta($wooCategoryId, 'wk_sf_category_id', true);

                            if (empty($existsCategoryMergeId)) {
                                $sfCategoryExportResult = self::wooSfExportCategory(false, $wooCategoryId);
                                if (isset($sfCategoryExportResult['error'])) {
                                    if (!$ajaxCall) {
                                        return array('error' => "<b><i>Category Error: </i></b> " . $sfCategoryExportResult['error']);
                                    }
                                    $errorList[] = "Category Error: " . $sfCategoryExportResult['error'];
                                    $errorFlag = true;
                                } else {
                                    $sfCategoryExportResult = json_decode($sfCategoryExportResult);
                                    $processedItem['category_synchronised'] = true;
                                }
                            }
                        }
                    }
                }
            } catch (exception $e) {
                $errorList[] = $e->getMessage();
                $errorFlag = true;
                $log = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();

                self::createLog('Product', __LINE__ . ' ' . $log, true);
            }
        }

        $wooSfProductId = get_post_meta($pId, 'wk_sf_product_id', true);
        $existingSfProductId = get_post_meta($pId, 'wk_sf_product_id', true);
        $attachment_ids = $product->get_gallery_image_ids();
        $wooSfObject = array();
        $productID = '';
        if (!$errorFlag) {
            try {
                if ($product->is_type('variable')) {
                    WC_Product_Variable::sync($product, false);
                }
                $regularPrice = !empty($product->get_regular_price()) ? $product->get_regular_price() : 0;
                $salesPrice = !empty($product->get_sale_price()) ? $product->get_sale_price() : 0;
                $productPrice = !empty($product->get_price()) ? $product->get_price() : 0;
                $manageStock = !empty($product->managing_stock()) ? $product->managing_stock() : 0;
                $thumbnailId = !empty($product->get_image_id()) ? $product->get_image_id() : 0;

                if ($productPrice == '' || $productPrice == 0) {
                    $productPrice = 0;
                }
                if ($salesPrice == '' || $salesPrice == 0) {
                    $salesPrice == 0;
                }
                if ($regularPrice == '' || $regularPrice == 0) {
                    $regularPrice = $salesPrice != 0 ? $salesPrice : $productPrice;
                }

                $checkSfProduct = self::getMatchingRecord($productId, 'Product2');

                $productDetails = new stdClass();
                $productDetails->IsActive = 1;
                $productDetails->webkul_wws__woo_Post_id__c = isset($pId) ? $pId : $productId;
                $productDetails->Name = !empty($product->get_title()) ? $product->get_title() : $product->get_formatted_name();
                $productDetails->webkul_wws__woo_Post_type__c = isset($product->product_type) ? $product->product_type : 'simple';
                $sku = $product->get_sku();
                if (isset($sku) && !empty($sku)) {
                    $productDetails->ProductCode = $sku;
                }

                if (null !== $product->get_width() && !empty($product->get_width())) {
                    $productDetails->webkul_wws__woo_product_width__c = $product->get_width();
                }

                if (null !== $product->get_weight() && !empty($product->get_weight())) {
                    $productDetails->webkul_wws__woo_product_weight__c = $product->get_weight();
                }

                if (null !== $product->get_length() && !empty($product->get_length())) {
                    $productDetails->webkul_wws__woo_product_length__c = $product->get_length();
                }

                if (null !== $product->get_height() && !empty($product->get_height())) {
                    $productDetails->webkul_wws__woo_product_height__c = $product->get_height();
                }

                if (isset($productPostData->post_content) && !empty($productPostData->post_content)) {
                    $desc = strip_tags($productPostData->post_content);
                    if (strlen($desc) > 131072) {
                        $desc = substr($desc, 0, 131071);
                    }
                    $productDetails->webkul_wws__Woo_Post_Description__c = $desc;
                }

                if (isset($productPostData->post_excerpt) && !empty($productPostData->post_excerpt)) {
                    $productDetails->webkul_wws__woo_Post_excerpt__c = $productPostData->post_excerpt;
                }

                $productDetails->webkul_wws__woo_post_Status__c = isset($productPostData->post_status) ? $productPostData->post_status : 'draft';
                $productDetails->webkul_wws__woo_comment_count__c = isset($productPostData->comment_count) ? $productPostData->comment_count : 0;
                $productDetails->webkul_wws__woo_Comment_Status__c = isset($productPostData->comment_status) ? $productPostData->comment_status : 'open';
                $productDetails->webkul_wws__woo_Menu_Order__c = isset($productPostData->menu_order) ? $productPostData->menu_order : 0;
                $productDetails->webkul_wws__woo_Post_Author__c = isset($productPostData->post_author) ? $productPostData->post_author : 1;

                if (isset($productPostData->post_date)) {
                    $date = date_create($productPostData->post_date);
                    $productDetails->webkul_wws__woo_Post_Date__c = date_format($date, 'Y-m-d');
                }

                if ($manageStock == '1') {
                    if ($product->get_stock_quantity() == null || $product->get_stock_quantity() == 0) {
                        $productDetails->webkul_wws__woo_Stock__c = null !== $product->get_stock_quantity() ? $product->get_stock_quantity() : 0;
                    }
                }
                if ($wooSfRestProductConfig->sync_to_files) {
                    // This will be done after product insert/update as product id will be required in ContentDocumentLink
                } else {
                    if ($imageDoc = self::uploadSfMedia($thumbnailId)) {
                        if (!empty($imageDoc) && !is_array($imageDoc) && $imageDoc) {
                            $productDetails->webkul_wws__ContentDocumentId__c = '';
                            $productDetails->webkul_wws__woo_Thumbnail_ID__c = $imageDoc;
                            $log = 'Product Id: ' . (isset($pId) ? $pId : $productId) . ' Document Media uploaded, Id: ' . $imageDoc;
                            self::createLog('Product', __LINE__ . ' ' . $log, false);
                        } else {
                            if (is_array($imageDoc)) {
                                foreach ($imageDoc as $err) {
                                    $log = 'Product Id: ' . (isset($pId) ? $pId : $productId) . ' Document Media Upload Failed, Error: ' . $err;
                                    self::createLog('Product', __LINE__ . ' ' . $log, true);
                                }
                            } else {
                                $log = 'Product Id: ' . (isset($pId) ? $pId : $productId) . ' Document Media Upload Failed';
                                self::createLog('Product', __LINE__ . ' ' . $log, true);
                            }
                        }
                    } else {
                        $productDetails->webkul_wws__woo_Thumbnail_ID__c = 0;
                    }
                }

                $productMappedFields = self::getProductMappedFields();
                if (isset($productMappedFields) && !empty($productMappedFields)) {
                    foreach ($productMappedFields as $key => $mapped_data) {
                        $keyValue = get_post_meta($productId, $mapped_data['woofield'], true);
                        if (!empty($keyValue)) {
                            $sffield = $mapped_data['sffield'];
                            if ($mapped_data['type'] != '' && ($mapped_data['type'] == 'date' || $mapped_data['type'] == 'datetime')) {

                                $timeStamp = strtotime($keyValue);
                                $dateValue = '';
                                if ($timeStamp) {

                                    $dateValue = gmdate('c', $timeStamp);

                                } else {

                                    $dateValue = gmdate('c', $keyValue);
                                    if (!$dateValue) {
                                        $dateValue = '';
                                    }

                                }
                                $productDetails->$sffield = $dateValue;

                            } else {
                                $productDetails->$sffield = $keyValue;

                            }
                        }
                    }
                }

                $productMappedAttributes = self::getProductMappedAttributes();

                if (isset($productMappedAttributes) && !empty($productMappedAttributes)) {
                    foreach ($product->get_attributes() as $value) {
                        foreach ($productMappedAttributes as $mapped_data) {
                            if ($value->get_name() == $mapped_data['woofield']) {
                                $fieldValue = $mapped_data['sffield'];
                                $attrVals = array();

                                foreach ($value->get_terms() as $val) {
                                    $attrVals[] = $val->name;
                                }
                                $productDetails->$fieldValue = implode('|', $attrVals);
                            }
                        }
                    }
                }
                if (isset($checkSfProduct->Id)) {
                    //upsert
                    $query = "SELECT Id FROM Product2 WHERE Id='$checkSfProduct->Id'";
                    $sfProductExist = SalesforceConnector::getSObject($query);
                    if ($sfProductExist) {
                        //update
                        $sfProductId = $sfProductExist->Id;
                        if ($wooSfRestProductConfig->update_product) {

                            $result = SalesforceConnector::updateSObject('Product2', $sfProductId, json_encode($productDetails));
                            if (is_array($result)) {
                                $errorFlag = true;
                                $errorList[] = $result;
                                foreach ($errorList as $error) {
                                    self::createLog('Product', __LINE__ . ' ' . $error, true);
                                }

                            } else {
                                $msg = "Export - Product ID " . (isset($pId) ? $pId : $productId) . " updated, Salesforce id: " . $sfProductId;
                                self::createLog('Product', __LINE__ . ' ' . $msg, false);
                            }

                        }
                    } else {
                        // insert
                        $result = SalesforceConnector::insertSObject('Product2', json_encode($productDetails));

                        if (!is_array($result)) {
                            $sfProductId = $result;
                            $msg = 'Export - Product ID ' . (isset($pId) ? $pId : $productId) . ' inserted, Salesforce id: ' . $sfProductId;
                            self::createLog('Product', __LINE__ . ' ' . $msg, false);
                        } else {
                            $errorFlag = true;
                            $errorList[] = $result[0];
                            foreach ($errorList as $error) {
                                self::createLog('Product', __LINE__ . ' ' . $error, true);
                            }

                        }

                    }
                } else {
                    $result = SalesforceConnector::insertSObject('Product2', json_encode($productDetails));

                    if (!is_array($result)) {
                        $sfProductId = $result;
                        $msg = 'Export - Product ID ' . (isset($pId) ? $pId : $productId) . ' inserted, Salesforce id: ' . $sfProductId;
                        self::createLog('Product', __LINE__ . ' ' . $msg, false);
                    } else {
                        $errorFlag = true;
                        $errorList[] = $result[0];
                        foreach ($errorList as $error) {
                            self::createLog('Product', __LINE__ . ' ' . $error, true);
                        }

                    }

                }
                if (isset($sfProductId) && !empty($sfProductId)) {
                    //Upload Product Multiple Images into File
                    if ($wooSfRestProductConfig->sync_to_files) {
                        $thumbnailIdArray = array($thumbnailId);
                        $productAllImages = array_merge($attachment_ids, $thumbnailIdArray);
                        if (isset($productAllImages) && !empty($productAllImages)) {
                            foreach ($productAllImages as $attachment_id) {
                                self::uploadProductAllImages($attachment_id, $sfProductId);
                            }
                        }
                    }

                    if ($wooSfRestProductConfig->sync_data) {
                        $newCategories = array();
                        if (!empty($categoryArray)) {
                            foreach ($categoryArray as $category) {
                                $wooCategoryId = $category->term_id;
                                $sfCategoryId = get_term_meta($wooCategoryId, 'wk_sf_category_id', true);
                                $mappingDetails = new stdClass();
                                $mappingDetails->webkul_wws__Product__c = $sfProductId;
                                $mappingDetails->webkul_wws__woo_commerce_categories__c = $sfCategoryId;
                                $query = "SELECT id FROM webkul_wws__Product_Category_Mapping__c WHERE webkul_wws__Product__c = '" . $sfProductId . "' AND webkul_wws__woo_commerce_categories__c = '" . $sfCategoryId . "'";
                                $mappingExist = SalesforceConnector::getSObject($query);
                                if (!$mappingExist) {

                                    $result = SalesforceConnector::insertSObject('webkul_wws__Product_Category_Mapping__c', json_encode($mappingDetails));

                                    if (is_array($result)) {
                                        $errorFlag = true;
                                        $errorList[] = $result[0];
                                        foreach ($errorList as $error) {
                                            self::createLog('Product', __LINE__ . ' ' . $error, true);

                                        }
                                    } else {
                                        $msg = 'Export - webkul_wws__Product_Category_Mapping__c created, Salesforce Product id: ' . $sfProductId . ' Salesforce Category Id: ' . $sfCategoryId;
                                        self::createLog('Product', __LINE__ . ' ' . $msg, false);
                                    }
                                }
                                $newCategories[] = $mappingDetails;
                            }
                        }
                        $query = "SELECT id,webkul_wws__woo_commerce_categories__c from webkul_wws__Product_Category_Mapping__c where webkul_wws__Product__c='" . $sfProductId . "'";
                        $existingSFMappings = SalesforceConnector::getSObject($query, 0);

                        if (isset($existingSFMappings) && !empty($existingSFMappings)) {
                            foreach ($existingSFMappings as $maps) {
                                $checked = false;
                                foreach ($newCategories as $newCategory) {
                                    if ($maps->webkul_wws__woo_commerce_categories__c == $newCategory->webkul_wws__woo_commerce_categories__c) {
                                        $checked = true;
                                        break;
                                    }
                                }
                                if (!$checked) {
                                    SalesforceConnector::deleteSObject('webkul_wws__Product_Category_Mapping__c', $maps->Id);
                                }
                            }
                        }
                    }

                    $wooSfProductId = get_post_meta($pId, 'wk_sf_product_id', true);
                    $dateFormat = get_option('date_format');
                    $timeFormat = get_option('time_format');
                    $processedItem['syncc_time'] = date($dateFormat . ' ' . $timeFormat, time());

                    if (isset($wooSfProductId) && !empty($wooSfProductId)) {
                        $processedItem['sf_product_id'] = $sfProductId;
                    } else {
                        $processedItem['sf_product_id'] = $sfProductId;
                        $wooSfObject = new stdClass();
                        $wooSfObject->id = 0;
                        $wooSfObject->sf_product_id = $sfProductId;
                        $wooSfObject->woo_product_id = $pId;
                        $wooSfObject->status = '1';
                    }

                    $query = "SELECT Id FROM Pricebook2 where IsStandard=true";
                    $standardPriceBookExist = SalesforceConnector::getSObject($query, 1);
                    if ($standardPriceBookExist) {
                        $standardPriceBookId = $standardPriceBookExist->Id;

                        // Fetch id from pricebookentry where priceid and product id
                        $query = "SELECT Id FROM PricebookEntry where Pricebook2Id='" . $standardPriceBookId . "' AND Product2Id='" . $sfProductId . "'";
                        $StandardProductPricebookEntryExist = SalesforceConnector::getSObject($query);
                        if ($StandardProductPricebookEntryExist) {
                            if ($wooSfRestProductConfig->update_std_pb) {
                                $priceBookEntry = new stdClass();
                                $priceBookEntry->UnitPrice = $regularPrice;

                                //upsert
                                $query = "SELECT Id FROM PricebookEntry WHERE Id='$StandardProductPricebookEntryExist->Id'";
                                $pricebookEntryExist = SalesforceConnector::getSObject($query);
                                if ($pricebookEntryExist) {
                                    //update
                                    $pricebookEntryId = $pricebookEntryExist->Id;
                                    $result = SalesforceConnector::updateSObject('PricebookEntry', $pricebookEntryId, json_encode($priceBookEntry));

                                    if (!is_array($result)) {
                                        $pricebookEntryId = $result;
                                        $msg = 'Export - PricebookEntry Updated , Salesforce Product id: ' . $sfProductId . ' Salesforce PricebookEntry Id: ' . $pricebookEntryId;
                                        self::createLog('Product', __LINE__ . ' ' . $msg, false);
                                    } else {
                                        $errorFlag = true;
                                        $errorList[] = $result[0];
                                        foreach ($errorList as $error) {
                                            self::createLog('Product', __LINE__ . ' ' . $error, true);
                                        }

                                    }
                                } else {
                                    // insert
                                    $result = SalesforceConnector::insertSObject('PricebookEntry', json_encode($priceBookEntry));

                                    if (!is_array($result)) {
                                        $pricebookEntryId = $result;
                                        $msg = 'Export - PricebookEntry Inserted, Salesforce Product id: ' . $sfProductId . ' Salesforce PricebookEntry Id: ' . $pricebookEntryId;
                                        self::createLog('Product', __LINE__ . ' ' . $msg, false);
                                    } else {
                                        $errorFlag = true;
                                        $errorList[] = $result[0];
                                        foreach ($errorList as $error) {
                                            self::createLog('Product', __LINE__ . ' ' . $error, true);
                                        }

                                    }
                                }
                            }
                        } else {
                            $priceBookEntry = new stdClass();
                            $priceBookEntry->IsActive = 1;
                            $priceBookEntry->Pricebook2Id = $standardPriceBookId;
                            if (isset($sfProductId) && !empty($sfProductId)) {
                                $priceBookEntry->Product2Id = $sfProductId;
                            }
                            $priceBookEntry->UnitPrice = $regularPrice;

                            // Create new pricebookentry with standard pricebook
                            $result = SalesforceConnector::insertSObject('PricebookEntry', json_encode($priceBookEntry));

                            if (is_array($result)) {
                                $errorFlag = true;
                                $errorList[] = $result[0];
                                foreach ($errorList as $error) {
                                    self::createLog('Product', __LINE__ . ' ' . $error, true);
                                }

                            } else {
                                $pricebookEntryId = $result;
                                $msg = 'Export - PricebookEntry Inserted, Salesforce Product id: ' . $sfProductId . ' Salesforce PricebookEntry Id: ' . $pricebookEntryId;
                                self::createLog('Product', __LINE__ . ' ' . $msg, false);
                            }
                        }
                    }
                    // Entry into admin selected Pricebook
                    // Fetch admin selected Price id from setting option
                    $priceBookId = $wooSfRestProductConfig->pricebook;
                    if (!empty($priceBookId)) {
                        // Fetch id from pricebookentry where priceid and product id
                        $query = "SELECT Id FROM PricebookEntry where Pricebook2Id='" . $priceBookId . "' AND Product2Id='" . $sfProductId . "'";
                        $productPricebookExist = SalesforceConnector::getSObject($query);
                        if ($productPricebookExist) {
                            $priceBookEntry = new stdClass();
                            $priceBookEntry->UnitPrice = $regularPrice;

                            //upsert
                            $query = "SELECT Id FROM PricebookEntry WHERE Id='$productPricebookExist->Id'";
                            $pricebookEntryExist = SalesforceConnector::getSObject($query);
                            if ($pricebookEntryExist) {
                                //update
                                $pricebookEntryId = $pricebookEntryExist->Id;
                                $result = SalesforceConnector::updateSObject('PricebookEntry', $pricebookEntryId, json_encode($priceBookEntry));

                                if (is_array($result)) {
                                    $errorFlag = true;
                                    $errorList[] = $result[0];
                                    foreach ($errorList as $error) {
                                        self::createLog('Product', __LINE__ . ' ' . $error, true);
                                    }

                                } else {
                                    $msg = 'Export - PricebookEntry Updated, Salesforce Product id: ' . $sfProductId . ' Salesforce PricebookEntry Id: ' . $pricebookEntryId;
                                    self::createLog('Product', __LINE__ . ' ' . $msg, false);
                                }
                            } else {
                                // insert
                                $result = SalesforceConnector::insertSObject('PricebookEntry', json_encode($priceBookEntry));

                                if (!is_array($result)) {
                                    $pricebookEntryId = $result;
                                    $msg = 'Export - PricebookEntry Inserted, Salesforce Product id: ' . $sfProductId . ' Salesforce PricebookEntry Id: ' . $pricebookEntryId;
                                    self::createLog('Product', __LINE__ . ' ' . $msg, false);
                                } else {
                                    $errorFlag = true;
                                    $errorList[] = $result[0];
                                    foreach ($errorList as $error) {
                                        self::createLog('Product', __LINE__ . ' ' . $error, true);
                                    }

                                }
                            }
                        } else {
                            $priceBookEntry = new stdClass();
                            $priceBookEntry->IsActive = 1;
                            $priceBookEntry->Pricebook2Id = $priceBookId;
                            if (isset($sfProductId) && $sfProductId) {
                                $priceBookEntry->Product2Id = $sfProductId;
                            }
                            $priceBookEntry->UnitPrice = $salesPrice != 0 ? $salesPrice : $regularPrice;
                            // Create new pricebookentry with selected pricebook

                            $result = SalesforceConnector::insertSObject('PricebookEntry', json_encode($priceBookEntry));

                            if (is_array($result)) {
                                $errorFlag = true;
                                $errorList[] = $result[0];
                                foreach ($errorList as $error) {
                                    self::createLog('Product', __LINE__ . ' ' . $error, true);
                                }

                            } else {
                                $msg = 'Export - PricebookEntry Inserted, Salesforce Product id: ' . $sfProductId . ' Salesforce PricebookEntry Id: ' . $result;
                                self::createLog('Product', __LINE__ . ' ' . $msg, false);
                            }
                        }
                    }

                } else {
                    if (!$ajaxCall) {
                        return array('error' => "<b><i>Product Error: </i></b> Unable to Insert/Update product to Salesforce");
                    }
                    $errorList[] = "Unable to Insert/Update product to Salesforce";
                    $errorFlag = true;
                }
            } catch (Exception $e) {
                $errorList[] = $e->getMessage();
                $errorFlag = true;
                $log = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();

                self::createLog('Product', __LINE__ . ' ' . $log, true);
                if (!$ajaxCall) {
                    return array('error' => $e->getMessage());
                }
            }
        }

        $processedItem['error'] = implode(',', $errorList);

        if (!$errorFlag) {
            update_post_meta($pId, 'wk_sf_product_id', $sfProductId);
            delete_post_meta($pId, 'wk_sf_product_err');
            if (!empty($wooSfProductId)) {
                $processedItem['sf_product_id'] = $sfProductId;
                $processedItem['wws'] = (array) $wooSfObject;
                $processedItem['updated'] += 1;
            } else {
                $processedItem['wws'] = $wooSfObject;
                $processedItem['added'] += 1;
            }
        } else {
            if (!empty($wooSfProductId)) {
                update_post_meta($pId, 'wk_sf_product_id', $existingSfProductId);
                if (isset($processedItem['error']) && !empty($processedItem['error'])) {
                    $processedItem['errorsValue'] += 1;
                    update_post_meta($pId, 'wk_sf_product_err', $processedItem['error']);
                } else {
                    $processedItem['updated'] += 1;
                    delete_post_meta($pId, 'wk_sf_product_err');
                }
                $processedItem['sf_product_id'] = isset($sfProductId) && !empty($sfProductId) ? $sfProductId : '';
            } else {
                $wooSfObject = new StdClass();
                $wooSfObject->status = 0;
                $wooSfObject->error = $processedItem['error'];
                $wooSfObject->woo_product_id = $pId;
                if (isset($processedItem['error']) && !empty($processedItem['error'])) {
                    update_post_meta($pId, 'wk_sf_product_err', $processedItem['error']);
                    $processedItem['errorsValue'] += 1;
                } else {
                    delete_post_meta($pId, 'wk_sf_product_err');
                    $processedItem['added'] += 1;
                }
            }
        }
        if (!$ajaxCall) {
            return $processedItem;
        }
        update_option('allow_import', true);
        echo json_encode($processedItem);
        exit;
    }

    public static function uploadProductAllImages($thumbnailId, $sfProductId)
    {
        if ($imageDocId = self::uploadSfThumbnailFile($thumbnailId)) {

            $query = "SELECT ContentDocumentId FROM ContentVersion WHERE Id='$imageDocId'";
            $sfContentDocumentExist = SalesforceConnector::getSObject($query);

            if ($sfContentDocumentExist) {
                $mediaLink = new stdClass();
                $mediaLink->LinkedEntityId = $sfProductId;
                $mediaLink->ShareType = 'I';
                $mediaLink->Visibility = 'AllUsers';

                $mediaLink->ContentDocumentId = $sfContentDocumentExist->ContentDocumentId;
                $a = SalesforceConnector::insertSObject('ContentDocumentLink', json_encode($mediaLink));
                if (is_array($a)) {
                    $errorFlag = true;
                    $errorList[] = 'Export - Product : ' . $a[0];
                    foreach ($errorList as $error) {
                        self::createLog('Product', __LINE__ . ' ' . $error, true);
                    }

                }
            }
        }
    }

    public static function getProductMappedFields()
    {
        if (ORG_TYPE == 'BAFM' || ORG_TYPE == 'PAFM') {
            $optionName = 'wkproduct_field_multiple_data';

            $savedMappingOptionName = get_option($optionName);
            if (isset($savedMappingOptionName) && !empty($savedMappingOptionName)) {
                foreach ($savedMappingOptionName as $key => $value) {
                    if (!isset($value['isactive']) || ($value['isactive'] != 'A')) {
                        unset($savedMappingOptionName[$key]);
                    }

                }
            }
            return $savedMappingOptionName;
        }
    }

    public static function getProductMappedAttributes()
    {
        if (ORG_TYPE == 'BAFM' || ORG_TYPE == 'PAFM') {
            $optionName = 'wkproduct_attr_multiple_data';

            $savedMappingOptionName = get_option($optionName);
            if (isset($savedMappingOptionName) && !empty($savedMappingOptionName)) {
                foreach ($savedMappingOptionName as $key => $mapped_data) {
                    if (!isset($mapped_data['isactive']) || ($mapped_data['isactive'] != 'A')) {
                        unset($savedMappingOptionName[$key]);
                    } else {
                        $savedMappingOptionName[$key]['woofield'] = 'pa_' . $mapped_data['woofield'];
                    }

                }
            }
            return $savedMappingOptionName;
        }
    }

    public static function checkIfPostExists($postId = 0)
    {
        global $wpdb;
        $postExists = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE id = '$postId' AND post_type = 'product'", 'ARRAY_A');
        if ($postExists) {
            return true;
        }
        return false;
    }

    /**
     * Get categories over a product
     *
     * @param Integer $productId
     *
     * @return Array
     */
    public static function getWooProductCategories($productId)
    {
        global $wpdb;
        $query = "SELECT t.*,tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ('product_cat') AND tr.object_id IN ($productId)";
        $result = $wpdb->get_results($query);
        return $result;
    }

    public static function getMatchingRecord($recordId, $sObject = 'User')
    {
        if ($sObject == 'Account') {
            $externalId = 'webkul_wws__Woocommerce_Customer_Email__c';
        }
        $MatchingConfig = get_option('wooSfRestAdvancedConfig');
        $wooSfRestAccountConfig = get_option('wooSfRestAccountConfig');

        $MatchingConfig = (array) $MatchingConfig;
        $returnRecord = new stdClass();

        $conditionString = '';

        $query = "SELECT Id FROM $sObject";
        if ($sObject == 'Product2') {
            $existMap = get_post_meta($recordId, 'wk_sf_product_id', true);
            if (!empty($existMap) && $existMap != '--') {
                $checkInSf = SalesforceConnector::getSObject($query . " WHERE Id='$existMap'");
                if ($checkInSf) {
                    $returnRecord->Id = $existMap;
                    return $returnRecord;
                }
            }
            $query .= ' WHERE webkul_wws__woo_post_id__c = ' . $recordId;
        } elseif ($sObject == 'User') {
            $query = "SELECT Id, AccountId FROM Contact";
            $existMap = get_user_meta($recordId, 'wk_sf_contact_id', true);

            if (!empty($existMap) && $existMap != '--') {
                $checkInSf = SalesforceConnector::getSObject($query . " WHERE Id = '$existMap'");
                if (!empty($checkInSf)) {
                    $returnRecord->Id = $existMap;
                    $returnRecord->AccountId = $checkInSf->AccountId;
                    return $returnRecord;
                }
            }
            $user = get_userdata($recordId);
            $userMeta = get_user_meta($user->ID);
            foreach ($userMeta as $key => $value) {
                if (count($value) != 0) {
                    $user->data->$key = $value[0];
                } else {
                    $user->data->$key = '';
                }
            }

            $contactmatchingCriteria = $MatchingConfig['contact-matching-table-filterCondition'];
            if (!empty($contactmatchingCriteria)) {
                $conditionString .= " WHERE " . $contactmatchingCriteria;
                $contactMatchingConfig = $MatchingConfig['contact-matching-table'];
                foreach ($contactMatchingConfig as $key => $matching) {
                    $conditionPart = $matching->sfField;
                    $conditionValue = $matching->wooField;
                    $conditionValue = isset($user->data->$conditionValue) ? $user->data->$conditionValue : '';
                    if ($matching->sfType == 'string' || $matching->sfType == 'email') {
                        if ($matching->sfType == 'email') {
                            $conditionValue = strtolower($conditionValue);
                        }
                        if ($matching->joinCondition == 'exact') {
                            $conditionPart .= " = '$conditionValue'";
                        } else {
                            $conditionPart .= " like '%$conditionValue%'";
                        }
                    } else {
                        if ($matching->joinCondition == 'exact') {
                            $conditionPart .= " = $conditionValue";
                        } else {
                            $conditionPart .= " like %$conditionValue%";
                        }
                    }
                    $conditionString = str_replace('{' . $matching->criteriaId . '}', $conditionPart, $conditionString);
                }
            }

        } elseif ($sObject == 'Account') {

            $existMap = get_user_meta($recordId, 'wk_sf_account_id', true);

            if (!empty($existMap) && $existMap != '--') {
                $checkInSf = SalesforceConnector::getSObject($query . " WHERE Id = '$existMap'");
                if (!empty($checkInSf)) {
                    $returnRecord->Id = $existMap;
                    return $returnRecord;
                }
            }
            $user = get_userdata($recordId);
            $userMeta = get_user_meta($user->ID);
            foreach ($userMeta as $key => $value) {
                if (count($value) != 0) {
                    $user->data->$key = $value[0];
                } else {
                    $user->data->$key = '';
                }
            }
            $userEmail = strtolower($user->data->user_email);

            $accountmatchingCriteria = isset($MatchingConfig['account-matching-table-filterCondition']) && !empty($MatchingConfig['account-matching-table-filterCondition']) ? $MatchingConfig['account-matching-table-filterCondition'] : '';

            if (!empty($accountmatchingCriteria)) {

                $conditionString .= " WHERE " . $accountmatchingCriteria;

                $accountMatchingConfig = $MatchingConfig['account-matching-table'];
                foreach ($accountMatchingConfig as $key => $matching) {
                    $conditionPart = $matching->sfField;
                    $conditionValue = $matching->wooField;
                    $conditionValue = $user->data->$conditionValue;
                    if ($matching->sfType == 'string' || $matching->sfType == 'email') {
                        if ($matching->sfType == 'email') {
                            $conditionValue = strtolower($conditionValue);
                        }
                        if ($matching->joinCondition == 'exact') {
                            $conditionPart .= " = '$conditionValue'";
                        } else {
                            $conditionPart .= " like '%$conditionValue%'";
                        }
                    } else {
                        $conditionPart .= " = $conditionValue";
                    }
                    $conditionString = str_replace('{' . $matching->criteriaId . '}', $conditionPart, $conditionString);
                }
            }
        }
        $queryResponse = SalesforceConnector::getSObject($query . $conditionString . " Order By lastmodifieddate desc limit 1");
        if ($queryResponse) {
            $returnRecord->Id = $queryResponse->Id;
            if ($sObject == 'User') {
                $returnRecord->AccountId = $queryResponse->AccountId;
            }
        }
        return $returnRecord;
    }

    public static function getWooUserIdscount($sync)
    {
        $sync = isset($sync) ? $sync : 'A';
        $wooSfRestAccountConfig = get_option('wooSfRestAccountConfig');

        if ($sync == "S") {
            if (ORG_TYPE == 'BA' || ORG_TYPE == 'BAFM' || (isset($wooSfRestAccountConfig->recordType) && $wooSfRestAccountConfig->recordType == 'B')) {
                $arg = array(
                    'fields' => 'ID',
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'wk_sf_account_id',
                        ),
                        array(
                            'key' => 'wk_sf_contact_id',
                        ),
                        array(
                            'key' => 'wk_sf_account_error',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'wk_sf_contact_error',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'user_Synced',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                );
            }
            if ((ORG_TYPE == 'PA' || ORG_TYPE == 'PAFM') && $wooSfRestAccountConfig->recordType == 'P') {
                $arg = array(
                    'fields' => 'ID',
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'wk_sf_account_id',
                        ),
                        array(
                            'key' => 'wk_sf_account_error',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'user_Synced',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                );
            }
        } elseif ($sync == "U") {
            $arg = array(
                'fields' => 'ID',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'wk_sf_account_id',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => 'wk_sf_contact_id',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => 'wk_sf_account_error',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => 'wk_sf_contact_error',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => 'user_Synced',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            );
        } elseif ($sync == "E") {
            $arg = array(
                'fields' => 'ID',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'user_Synced',
                        'compare' => 'NOT EXISTS',
                    ),

                    array(
                        'relation' => 'OR',
                        array(
                            'key' => 'wk_sf_account_error',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'wk_sf_contact_error',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                ),
            );
        } elseif ($sync == "A") {
            $arg = array(
                'fields' => 'ID',
                'meta_key' => 'user_Synced',
                'meta_compare' => 'NOT EXISTS',
            );
        } else {
            echo json_encode('');
            exit();
        }
        $wp_userSync_query = new WP_User_Query($arg);

        return $wp_userSync_query->get_results();

    }
    public static function changeGlobalVaribale($syncType)
    {
        self::$delete_user =  &$syncType;
        $GLOBALS['a'] = $syncType;

    }
    public static function wooSfExportUsersData($syncType)
    {

        $dataIds = self::getWooUserIds($syncType);
        $cancel_user_sync = get_option('delete_user');

        if (empty($dataIds)) {
            delete_option('delete_user');
            return false;
        }

        foreach ($dataIds as $item) {

            if ($cancel_user_sync) {
                delete_option('delete_user');
                return false;
            }

            self::wooSfExportUsers(false, $item, false);
            update_user_meta($item, 'user_Synced', true);
            $processed_user = get_option('user_processed');
            update_option('user_processed', $processed_user + 1);

        }

        return $syncType;
    }

    public function wooSfRestNewProduct($meta_id, $post_id, $meta_key, $meta_value)
    {
        if ($meta_key == '_edit_lock') { // we've been editing the post
            if (get_post_type($post_id) == 'product') { // we've been editing a product
                if (!wp_next_scheduled('real_time_product', array(false, $post_id))) {
                    wp_schedule_single_event(time(), 'real_time_product', array(false, $post_id));
                }
            }
        }
    }

    public function wooSfUpdateProduct($product_id)
    {
        if (!wp_next_scheduled('real_time_product', array(false, $product_id))) {
            wp_schedule_single_event(time(), 'real_time_product', array(false, $product_id));
        }
    }

    public function wp_custom_save_taxonomy($term_id, $tt_id)
    {
        if (!wp_next_scheduled('real_time_category', array(false, $term_id))) {
            wp_schedule_single_event(time(), 'real_time_category', array(false, $term_id));
        }
    }

    public static function createContentDocumentLink($objId, $imageid)
    {
        if (isset($imageid) && !empty($imageid)) {
            $contentDocumentId = SalesforceConnector::getSObject("SELECT ContentDocumentId FROM ContentVersion WHERE Id = '" . $imageid . "'");
        }
        if (isset($contentDocumentId) && !empty($contentDocumentId->ContentDocumentId) && isset($contentDocumentId->ContentDocumentId)) {
            $isExist = SalesforceConnector::getSObject("SELECT Id FROM ContentDocumentLink WHERE ContentDocumentId = '" . $contentDocumentId->ContentDocumentId . "' AND LinkedEntityId = '" . $objId . "'");
            if (!isset($isExist->Id) || empty($isExist->Id)) {
                $ContentDocumentLink = new stdClass();
                $ContentDocumentLink->ContentDocumentId = $contentDocumentId->ContentDocumentId;
                $ContentDocumentLink->LinkedEntityId = $objId;
                $ContentDocumentLink->ShareType = 'I';
                $ContentDocumentLink->Visibility = 'AllUsers';
                $res = SalesforceConnector::insertSObject('ContentDocumentLink', json_encode($ContentDocumentLink));
            }
        }
    }

    /** Background process Implementation for Export all entity*/
    public static function createBackgroundJob($type)
    {
        $process_all = '';
        $dataIds = array();
        // self::$process_user

        if ($type == 'category') {

            $process_all = self::$process_category;
            $sync = ($_REQUEST['item_type']) ? $_REQUEST['item_type'] : 'A';

            $dataIds = self::getWooCategoryCount($sync);

            update_option('category_total', count($dataIds));
            update_option('category_processed', 0);
            if (!empty($process_all)) {

                $process_all->push_to_queue($_REQUEST['item_type']);
                $process_all->save()->dispatch();

                WooSfRest::showNotice('updated', 'Background process for Category is running ', 'Job processed ' . get_option('category_processed') . '  out of  ' . get_option('category_total'));
            }

        }
        if ($type == 'products') {
            $process_all = self::$process_product;
            $sync = ($_REQUEST['item_type']) ? $_REQUEST['item_type'] : 'A';

            $dataIds = self::getWooProductIdsCount($sync);
            update_option('products_total', count($dataIds));
            update_option('products_processed', 0);
            if (!empty($process_all)) {

                $process_all->push_to_queue($_REQUEST['item_type']);
                $process_all->save()->dispatch();

                WooSfRest::showNotice('updated', 'Background process for Products is running ', 'Job processed ' . get_option('products_processed') . '  out of  ' . get_option('products_total'));
            }

        }

    }
}

add_action('wp_ajax_getSObjectCount', array('WOOSFREST', 'getSObjectCount'));

add_action('wp_ajax_importSfCategories', array('WOOSFREST', 'importSfCategories'));
add_action('wp_ajax_getWooCategoryIds', array('WOOSFREST', 'getWooCategoryIds'));
add_action('wp_ajax_wooSfExportCategory', array('WOOSFREST', 'wooSfExportCategory'));

add_action('wp_ajax_importSfProducts', array('WOOSFREST', 'importSfProducts'));
add_action('wp_ajax_getWooProductIds', array('WOOSFREST', 'getWooProductIds'));
add_action('wp_ajax_wooSfExportProduct', array('WOOSFREST', 'wooSfExportProduct'));

add_action('wp_ajax_getWooUserIds', array('WOOSFREST', 'getWooUserIds'));
add_action('wp_ajax_wooSfExportUsers', array('WOOSFREST', 'wooSfExportUsers'));

add_action('wp_ajax_getWooOrderIds', array('WOOSFREST', 'getWooOrderIds'));
add_action('wp_ajax_wooSfExportOrders', array('WOOSFREST', 'wooSfExportOrders'));

//Bulk API
add_action('wp_ajax_getBulkWooCategoryIds', array('WOOSFREST', 'getBulkWooCategoryIds'));
add_action('wp_ajax_wooSfExportBulkCategory', array('WOOSFREST', 'wooSfExportBulkCategory'));
add_action('wp_ajax_wooSfExportBulkUsers', array('WOOSFREST', 'wooSfExportBulkUsers'));
add_action('wp_ajax_getWooBulkProductIds', array('WOOSFREST', 'getWooBulkProductIds'));
add_action('wp_ajax_wooSfExportBulkProduct', array('WOOSFREST', 'wooSfExportBulkProduct'));

add_action('wp_ajax_getLogFileforDeletion', array('WOOSFREST', 'getLogFileforDeletion'));
add_action('wp_ajax_migrateTableData', array('WOOSFREST', 'migrateTableData'));

add_action('real_time_order', array('WOOSFREST', 'wooSfExportOrders'), 10, 2);

add_action('real_time_category', array('WOOSFREST', 'wooSfExportCategory'), 10, 2);

add_action('real_time_user', array('WOOSFREST', 'wooSfExportUsers'), 10, 2);

add_action('real_time_product', array('WOOSFREST', 'wooSfExportProduct'), 10, 2);
