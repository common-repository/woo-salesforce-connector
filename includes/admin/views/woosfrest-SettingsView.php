<?php
class WooSfRestSetingsView
{
    /**
     * Return a singleton instance of WooSfRestSetingsView class
     *
     * @brief Singleton
     *
     * @return WooSfRestSetingsView
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
     * Create an instance of WooSfRestSetingsView class
     *
     * @brief Construct
     *
     * @return WooSfRestSetingsView
     */
    public function __construct()
    {
        wp_enqueue_script('jquery', false, array('jquery-core', 'jquery-migrate'), '1.12.4-wp');
        wp_enqueue_script('jquery-ui-dialog', "/wp-includes/js/jquery/ui/dialog.min.js", array('jquery-ui-resizable', 'jquery-ui-draggable', 'jquery-ui-button', 'jquery-ui-position'), '1.11.4', 1);
        wp_enqueue_script('jquery-ui-widget', "/wp-includes/js/jquery/ui/widget.min.js", array('jquery'), '1.11.4', 1);
        wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
        wp_enqueue_style('tiptip-style', plugins_url() . '/woo-salesforce-connector/assets/css/tiptip.css', array());

        wp_enqueue_script('wkajax-script', plugins_url() . '/woocommerce/assets/js/jquery-tiptip/jquery.tipTip.min.js', array(), WC_VERSION);
        /* adding CSS and Script by Praveen  */
        wp_register_style('wws-css', plugins_url() . '/woo-salesforce-connector/assets/css/style.css');
        wp_enqueue_style('wws-css');?>
        <meta charset="UTF-8">

        <link href='https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' rel='stylesheet' type='text/css'>

        <!-- Script -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js'></script>



    <?php
}

    /**
     * Display method to create view elements
     *
     * @return void
     */
    public function display()
    {

        if (isset($_GET['action']) && $_GET['action'] == 'resetallmapp') {

            WooSfRestInstall::deleteMappingForAllObjects();
            wp_safe_redirect($_SERVER['PHP_SELF'] . '?page=woosfrest_connector_settings');
        }
        if (isset($_GET['action']) && $_GET['action'] == 'unlinkSalesforce') {
            delete_option('wooSfRestConfig');

            delete_option('wksalesforce_data');
            delete_option('wooSfRestInstance');
            if (isset($_GET['delConfig']) && $_GET['delConfig'] == 1) {
                delete_option('wooSfRestAccountConfig');
                delete_option('wooSfRestProductConfig');
                delete_option('wooSfRestOrderConfig');
                delete_option('wooSfRestAdvancedConfig');
                delete_option('wkproduct_attr_multiple_data');
                delete_option('wkproduct_field_multiple_data');
                delete_option('wkaccount_field_multiple_data');
                delete_option('wkcontact_field_multiple_data');
                delete_option('wkorder_field_multiple_data');
                delete_option('wkopp_field_multiple_data');
                delete_option('wkopportunity_stage_mapping');
                delete_option('wkorder_status_mapping');
            }
            SalesforceConnector::revokeToken();
            wp_safe_redirect($_SERVER['PHP_SELF'] . '?page=woosfrest_connector_settings');
        }
        if (isset($_GET['action']) && $_GET['action'] == 'testconnection') {
            $salesforceConnector = new SalesforceConnector();
            delete_option('wksalesforce_data');
            $salesforceData = $salesforceConnector->getConfigSalesforceData();
            update_option('wksalesforce_data', $salesforceData);
            wp_safe_redirect($_SERVER['PHP_SELF'] . '?page=woosfrest_connector_settings');
        }
        if (isset($_GET['action']) && $_GET['action'] == 'datamigration') {
            WooSfRest::showNotice('Updated', 'Data Migration started.', ' It will be processed in the background');
        }
        if (isset($_GET['action']) && $_GET['action'] == 'logsdeleted') {
            WooSfRest::showNotice('Updated', 'Log file entries older than 1 week have been deleted from the Plugin\'s log file', '');
        }
        //changes removing the default matching conditions and implementing the default first condition by kapil

        $defaultConfigData = array();
        $defaultConfigData['account-matching-table-filterCondition'] = $defaultConfigData['contact-matching-table-filterCondition'] = $defaultConfigData['product-matching-table-filterCondition'] = '{1}';

        $defaultConfigData['account-matching-table'] = $defaultConfigData['contact-matching-table'] = $defaultConfigData['product-matching-table'] = array();
        $defaultConfigData['account-matching-table'][1] = array(
            'criteriaId' => 1,
            'sfField' => 'webkul_wws__Woocommerce_Customer_Email__c',
            'joinCondition' => 'exact',
            'wooField' => 'user_email',
            'sfType' => 'email',
        );
        $defaultConfigData['contact-matching-table'][1] = array(
            'criteriaId' => 1,
            'sfField' => 'Email',
            'joinCondition' => 'exact',
            'wooField' => 'user_email',
            'sfType' => 'email',
        );
        $defaultConfigData['product-matching-table'][1] = array(
            'criteriaId' => 1,
            'sfField' => 'webkul_wws__woo_Post_id__c',
            'joinCondition' => 'exact',
            'wooField' => 'ID',
            'sfType' => 'double',
        );

        //changes removing the default matching conditions and implementing the default first condition
        if (isset($_POST['connector_data'])) {
            $json_string = str_replace('\\', '', $_POST['connector_data']);
            $data = json_decode($json_string);

            $data = SalesforceConnector::generateAccessTokenFromCode($data);
            $getDefaultData = get_option('wooSfRestAdvancedConfig');
            if (empty($getDefaultData)) {
                update_option('wooSfRestAdvancedConfig', json_decode(json_encode($defaultConfigData)));
            }
            // update_option('wooSfRestAdvancedConfig', $defaultAdvancedSetting);
            $data = json_decode($data);
            if (isset($data->error) && $data->error == 'invalid_grant') {
                update_option('wooSfRestConfig', 'error');

            } else {

                update_option('wooSfRestConfig', $data);

            }
        } elseif (isset($_POST['product_data_json'])) {
            $json_string = str_replace('\\', '', $_POST['product_data_json']);
            $data = json_decode($json_string);
            update_option('wooSfRestProductConfig', $data);
        } elseif (isset($_POST['account_data_json'])) {
            $json_string = str_replace('\\', '', $_POST['account_data_json']);
            $data = json_decode($json_string);
            update_option('wooSfRestAccountConfig', $data);
        } elseif (isset($_POST['order_data_json'])) {
            $json_string = str_replace('\\', '', $_POST['order_data_json']);
            $data = json_decode($json_string);
            update_option('wooSfRestOrderConfig', $data);
        } elseif (isset($_POST['matching_criteria_mapping'])) {

            $json_string = str_replace('\\', '', $_POST['matching_criteria_mapping']);
            $dataArray = json_decode($json_string, 1);
            $flag = true;

            if (!empty($dataArray)) {

                if ($dataArray['account-matching-table-filterCondition'] == '' && sizeof($dataArray['account-matching-table']) == 1) {

                    foreach ($dataArray['account-matching-table'] as $k => $v) {
                        $dataArray['account-matching-table-filterCondition'] = '{' . $v['criteriaId'] . '}';
                    }

                } elseif ($dataArray['account-matching-table-filterCondition'] == '' && sizeof($dataArray['account-matching-table']) > 1) {

                    $flag = false;
                    $dataArray['account-matching-table-filterCondition'] = '';
                }

                if ($dataArray['contact-matching-table-filterCondition'] == '' && sizeof($dataArray['contact-matching-table']) == 1) {

                    foreach ($dataArray['contact-matching-table'] as $k => $v) {
                        $dataArray['contact-matching-table-filterCondition'] = '{' . $v['criteriaId'] . '}';
                    }

                } elseif ($dataArray['contact-matching-table-filterCondition'] == '' && sizeof($dataArray['contact-matching-table']) > 1) {

                    $flag = false;
                    $dataArray['contact-matching-table-filterCondition'] = '';
                }

                if ($dataArray['product-matching-table-filterCondition'] == '' && sizeof($dataArray['product-matching-table']) == 1) {

                    foreach ($dataArray['product-matching-table'] as $k => $v) {
                        $dataArray['product-matching-table-filterCondition'] = '{' . $v['criteriaId'] . '}';
                    }

                } elseif ($dataArray['product-matching-table-filterCondition'] == '' && sizeof($dataArray['product-matching-table']) > 1) {

                    $flag = false;
                    $dataArray['product-matching-table-filterCondition'] = '';
                }

            }

            $data = json_decode(json_encode($dataArray));
            if ($flag) {
                update_option('wooSfRestAdvancedConfig', $data);
            } else {
                WooSfRest::showNotice('error', 'Advanced configuration is not saved because account,contact and product anyone of the filer-criteria are empty ', '');
            }

        }

        if (empty(get_option('wooSfRestConfig')) || get_option('wooSfRestConfig') == 'error') {
            $this->generateConnectionTab();
        } else {
            $this->generateConfigTabs();
        }?>
        <script>
            jQuery(function($) {
                $(".ttip").tipTip({
                    delay: 50,
                    defaultPosition: "top"
                });
                $('.disconnect').on('click', function(event) {
                    event.preventDefault();
                    $('#wpwrap').addClass('ui-widget-overlay');
                    $("#dialogbox").dialog({
                        buttons: [{
                                text: "Cancel",
                                click: function() {
                                    location.reload();
                                }
                            },
                            {
                                text: 'Yes',
                                click: function() {
                                    var link = $('.disconnect').attr('href');
                                    link += '&delConfig=1';
                                    location.href = link;
                                }
                            },

                            {
                                text: "No",
                                click: function() {
                                    var link = $('.disconnect').attr('href');
                                    location.href = link;
                                }
                            }
                        ],
                        resizable: false,
                        title: 'Delete All Config?',
                    });

                });
                $('.resetallmap').on('click', function(event) {
                    event.preventDefault();
                    if (confirm('It will delete all your mapping data as well as connection setting.Are you sure?'))
                        location.href = $('.resetallmap').attr('href');
                    else
                        location.reload();
                });
            });
        </script>
        <style>
            .ui-dialog .ui-dialog-titlebar-close span {
                margin-right: unset !important;
                margin-bottom: unset !important;
            }

            .ui-button-icon-only .ui-icon {
                margin-left: -8px !important;
            }

            .ui-button-icon-only .ui-icon,
            .ui-button-text-icon .ui-icon,
            .ui-button-text-icons .ui-icon,
            .ui-button-icons-only .ui-icon {
                margin-top: -8px !important;
            }

            .button.testconnection-button {
                color: #fff;
                background-color: #5cb85c;
                border-color: #4cae4c;
            }
        </style>
    <?php
}

    /**
     * Connection tab to allow salesforce connection
     * This view will render before connection
     *
     * @return void
     */
    public function generateConnectionTab()
    {
        $productionAuthUri = "https://login.salesforce.com/services/oauth2/authorize?response_type=code&client_id=" . APP_CONSUMER_KEY . "&redirect_uri=" . urlencode("https://eshopsync.com/connector/auth.php");
        $sandboxAuthUri = "https://test.salesforce.com/services/oauth2/authorize?response_type=code&client_id=" . APP_CONSUMER_KEY . "&redirect_uri=" . urlencode("https://eshopsync.com/connector/auth.php");
        $wooSfRestConfig = get_option('wooSfRestConfig');
        ?>


        <h2>
            <u>Establish Connection With Salesforce</u>
        </h2>
        <?php
if ($wooSfRestConfig == 'error') {?>
            <div class='notice'>
                <p>
                    <strong>
                        NOTE:
                    </strong>
                    Connection is not established due to the permission denied.
                </p>
            </div>
        <?php }
        if (ORG_TYPE == 'PAFM' || ORG_TYPE == 'PA') {
            ?>
            <div class='notice'>
                <p>
                    <strong>
                        NOTE:
                    </strong>
                    Currently installed Woocommerce Salesforce Connector is for Person Account Orgs ,please connect to a Salesforce Org with Person Account enabled
                </p>
            </div>
        <?php
} else {
            ?>
            <div class='notice'>
                <p>
                    <strong>
                        NOTE:
                    </strong>
                    Currently installed Woocommerce Salesforce Connector is for Non-person Account or Business Account Orgs ,please connect to a Salesforce Org with Person Account disabled
                </p>
            </div>
        <?php
}
        ?>
        <br>
        <form method="post" id="initForm">
            <button type="button" onclick="initilaizeConnection('P')" class="button button-primary">Connect Salesforce Production</button>
            <button type="button" onclick="initilaizeConnection('S')" class="button button-primary">Connect Salesforce Sandbox</button>
            <input type="hidden" id="connector_data" name="connector_data" />
        </form>

        <script>
            window.accessToken = '';
            window.refreshToken = '';
            window.connectionType = '';

            function initilaizeConnection(target) {
                if (target == 'P') {
                    window.open('<?php echo $productionAuthUri; ?>', 'popup', 'width=600,height=600');
                    window.connectionType = 'production';
                } else {
                    window.open('<?php echo $sandboxAuthUri; ?>', 'popup', 'width=600,height=600');
                    window.connectionType = 'sandbox';
                }
            }
            window.addEventListener('message', initSave);

            function initSave(event) {
                if (event.origin == 'https://eshopsync.com') {
                    var obj = {
                        code: event.data,
                        instance: window.connectionType
                    };
                    jQuery('#connector_data').val(JSON.stringify(obj));
                    jQuery('#initForm').submit();
                }
            }
        </script>
        <?php
}

    /**
     * Generate configuration tabs
     * This view will render after connection successful
     *
     * @return void
     */
    public function generateConfigTabs()
    {
        $salesforceData = get_option('wksalesforce_data');
        $tabActive = 'general';
        if (isset($_GET['tab'])) {
            if ($_GET['tab'] == 'products') {
                $tabActive = 'products';
            } elseif ($_GET['tab'] == 'account') {
                $tabActive = 'account';
            } elseif ($_GET['tab'] == 'order') {
                $tabActive = 'order';
            } elseif ($_GET['tab'] == 'advance') {
                $tabActive = 'advance';
            } else {
                wp_enqueue_script('ajax-script', plugins_url() . '/woo-salesforce-connector/assets/js/js-script.js', array('jquery'), 1.0);
            }
        } else {
            wp_enqueue_script('ajax-script', plugins_url() . '/woo-salesforce-connector/assets/js/js-script.js', array('jquery'), 1.0);
        }

        //check salesforceData is set or not.
        if (!isset($salesforceData) || empty($salesforceData) || (isset($salesforceData['userName']) && empty($salesforceData['userName'])) && (isset($salesforceData['orgDetails']) || empty($salesforceData['orgDetails']))) {
            $salesforceConnector = new SalesforceConnector();
            $salesforceData = $salesforceConnector->getConfigSalesforceData();

            update_option('wksalesforce_data', $salesforceData);
        }

        /* If the isPersonOrg property is undefined assign it as true,i.e., The connector will assume the account to be business account if the property is undefined*/
        if (!array_key_exists("isPersonOrg", $salesforceData)) {
            $salesforceData['isPersonOrg'] = false;
        }
        /* Check if Connected org is person account and if it matches the connector variant */
        if ($salesforceData['isPersonOrg'] && (ORG_TYPE == 'BA' || ORG_TYPE == 'BAFM')) {?>
            <div class='error'>
                <p>
                    <strong>
                        Org Mismatch Error:
                    </strong>
                    Person account is enabled in the connected org, please use a non-person account org or install Person Account version of Webkul Woocommerce Salesforce Connector
                </p>
            </div>
        <?php
echo "<br>&nbsp;&nbsp;&nbsp;&nbsp<a href='{$_SERVER['PHP_SELF']}?page=woosfrest_connector_settings&action=unlinkSalesforce' class='button button-primary disconnect'>Disconnect Account</a>";
            delete_option('wooSfRestConfig');

            delete_option('wksalesforce_data');
            exit;
        }
        if (!$salesforceData['isPersonOrg'] && (ORG_TYPE == 'PA' || ORG_TYPE == 'PAFM')) { ?>
            <div class='error'>
                <p>
                    <strong>
                        Org Mismatch Error:
                    </strong>
                    Connected Salesforce Org doesn't have Person account enabled, please enable Person Account or switch to an org with person org already enabled. For Non-person account or Business account orgs, please install Business account version of Webkul Woocommerce Salesforce Connector
                </p>
            </div>
            <?php
echo "<br>&nbsp;&nbsp;&nbsp;&nbsp<a href='{$_SERVER['PHP_SELF']}?page=woosfrest_connector_settings&action=unlinkSalesforce' class='button button-primary disconnect'>Disconnect Account</a>";
            delete_option('wooSfRestConfig');

            delete_option('wksalesforce_data');
            exit;
        }
        /*END */

        $folder = array();
        $pricebook = array();
        $showGenerationLink = false;

        if (isset($salesforceData['error'])) {
            WooSfRest::showNotice('error', 'Error', $salesforceData['error']);
        } else {
            if (!empty($salesforceData['orgDetails'])) {
                ?>
                <div class='notice notice-success my-dismiss-notice is-dismissible'>
                    <p>
                        <strong>Connection Status</strong>
                        Successfully Connected
                    </p>
                </div>
        <?php
$pluginDirForPermissionCheck = WP_PLUGIN_DIR . '/woo-salesforce-connector';
                if (!is_writable($pluginDirForPermissionCheck)) {

                    WooSfRest::showNotice('error', 'Permission Required', 'Permission needed for creating logs. Please go to plugins and give write permission to ' . '<b>woo-salesforce-connector</b>' . ' directory.');
                }
            }
            $folder = isset($salesforceData['folder']) ? $salesforceData['folder'] : array();

            $pricebook = isset($salesforceData['pricebook']) ? $salesforceData['pricebook'] : array();

            $AccountRecordType = isset($salesforceData['AccountRecordType']) ? $salesforceData['AccountRecordType'] : array();
            $contactRecordType = isset($salesforceData['ContactRecordType']) ? $salesforceData['ContactRecordType'] : array();
            $opportunityRecordType = isset($salesforceData['OpportunityRecordType']) ? $salesforceData['OpportunityRecordType'] : array();
            $Accounts = isset($salesforceData['Accounts']) ? $salesforceData['Accounts'] : array();
            $personAccRecordType = isset($salesforceData['personAccRecordType']) ? $salesforceData['personAccRecordType'] : array();
            if (empty($pricebook)) {
                WooSfRest::showNotice('error', 'Pricebook Error', 'Please check if the standard price books are available and active. This might disturb Product and Order Synchronization');
            }
        }?>
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
            <a href="admin.php?page=woosfrest_connector_settings&amp;tab=general" class="nav-tab <?php if ($tabActive == 'general') {
            echo 'nav-tab-active';
        }?>">Connection Settings</a>
            <a href="admin.php?page=woosfrest_connector_settings&amp;tab=products" class="nav-tab <?php if ($tabActive == 'products') {
            echo 'nav-tab-active';
        }?>">Category &amp; Product</a>

        </nav>
        <div id="dialogbox"></div>
        <form method="post" action="#" onsubmit="return setJSONString()" id="config-form">
            <?php
if ($tabActive == 'products') {
            $this->generateProductTab($folder, $pricebook);
        } else {
            if ($tabActive !== 'general') {
                WooSfRest::showNotice('error', 'Unknown Tab', 'Please Recheck Tab in URL');
            }
            $this->generateGeneralTab($salesforceData);
        }?>
        </form>
    <?php
}

    /**
     * Create general configuration tab view
     *
     * @param object $salesforceConnector
     *
     * @return void
     */
    public function generateGeneralTab($salesforceData)
    {
        $orgDetails = $salesforceData['orgDetails'];
        $userName = $salesforceData['userName'];

        if (empty($orgDetails)) {
            WooSfRest::showNotice('error', 'Connection Error', "There seems to be some issue with connection. Try reconnecting to your Salesforce Org");
            delete_option('wooSfRestConfig');

        }
        if (!$userName) {
            WooSfRest::showNotice('error', 'Connection Error', "Could not connect to Salesforce.");
            delete_option('wooSfRestConfig');

        }?>
        <h2>
            <u>WooCommerce Salesforce Connection Information</u>
        </h2>
        <?php
if (!empty($orgDetails) && $userName) {?>
            <table class="form-table">
                <tr>
                    <th width="24%">Organization Id </th>
                    <td title="Connected Salesforce Organization ID" class="ttip" width='1%'><span class="dashicons dashicons-editor-help"></span></td>
                    <td width="85%"><?php echo $orgDetails->Id ?></td>

                </tr>
                <tr>
                    <th width="24%">Organization Name</th>
                    <td title="Connected Salesforce Organization Name" class="ttip" width='1%'><span class="dashicons dashicons-editor-help"></span></td>
                    <td width="85%"><?php echo $orgDetails->Name ?></td>

                </tr>
                <tr>
                    <th width="24%">Connected User</th>
                    <td title="Connected Salesforce Organization User" class="ttip" width='1%'><span class="dashicons dashicons-editor-help"></span></td>
                    <td width="85%"><?php echo $userName->Name ?></td>

                </tr>
                <tr>
                    <th width="24%">Username</th>
                    <td title="Connected Salesforce Organization User's Username" class="ttip" width='1%'>
                        <span class="dashicons dashicons-editor-help"></span>
                    </td>

                    <td><?php echo $userName->Username ?></td>

                </tr>
                <tr>
                    <th width="24%">Email</th>
                    <td title="Connected Salesforce Organization User's Email" class="ttip" width='1%'><span class="dashicons dashicons-editor-help"></span></td>
                    <td><?php echo $userName->Email ?></td>

                </tr>
            </table>
            <table class="config-button-table">
                <tr>
                    <td>
                        <?php echo "<a href='{$_SERVER['PHP_SELF']}?page=woosfrest_connector_settings&action=testconnection' class='config-button button testconnection-button'>Refresh Connection</a>" ?>
                    </td>
                    <td>
                        <?php echo "<a href='{$_SERVER['PHP_SELF']}?page=woosfrest_connector_settings&action=unlinkSalesforce' class='config-button button button-primary disconnect'>Disconnect Account</a>" ?>
                    </td>
                    <td>
                        <?php echo "<a href='{$_SERVER['PHP_SELF']}?page=woosfrest_connector_settings&action=resetallmapp' class='config-button button button-primary resetallmap'>Reset All Mapping</a>" ?>
                    </td>
                    <td>
                        <?php echo "<a id='deletelog' href='#' title='Delete Logs older than last 7 days' class='config-button button button-primary deletelog'>Delete Logs</a>" ?>

                    </td>
                    <td>
                        <?php
global $wpdb;
            $dataCount = false;
            foreach (WooSfRestInstall::getTables() as $table) {
                if ($result = $wpdb->query("SHOW TABLES LIKE '" . $table . "'")) {
                    $dataCount = $wpdb->query("SELECT 1 FROM {$table} LIMIT 1");
                }

                if (!empty($dataCount) && $dataCount && $dataCount > 0) {
                    break;
                } else {
                    continue;
                }

            }
            if ($dataCount && $dataCount > 0) {
                echo "<a href='#' id='datamigration' class='config-button button button-primary datamigration'>Migrate Data</a>";
            }
            ?>
                    </td>
                </tr>

            </table>
        <?php
} else {
            ?>
            <?php echo "<a href='{$_SERVER['PHP_SELF']}?page=woosfrest_connector_settings&action=resetallmapp' class='button button-primary resetallmap'>Reset All Mapping</a>" ?>
        <?php
}?>

    <?php
}

    /**
     * Create category and product configuration tab
     *
     * @param array $folder
     * @param array $pricebook
     * @return void
     */

    /* Added the toggle switch button instead of checkbox and dropdown search box by Praveen */
    public function generateProductTab($folder, $pricebook)
    {
        wp_enqueue_script('ajax-script', plugins_url() . '/woo-salesforce-connector/assets/js/product-config.js', array('jquery'), 1.0);
        $wooSfRestProductConfig = get_option('wooSfRestProductConfig');
        $configObj = get_option('wooSfRestConfig');
        if (!$wooSfRestProductConfig) {
            $wooSfRestProductConfig = new stdclass;
        }?>
        <h2>
            <u>WooCommerce Category And Product Configurations</u>
        </h2>

        <table class="form-table" id="prod-setting-table">
            <tr>
                <th width="24%">
                    <?php _e('Sync Categories to Salesforce')?>
                </th>
                <td scope="row" title="<strong><u>Enable:</u></strong> Synchronize Woocommerce Categories into Salesforce Categories on any Category and Product Sync.<br>
               <strong><u>Disable:</u></strong> Categories Won't Synchronize to Salesforce" class="ttip" width='1%'>
                    <span class="dashicons dashicons-editor-help"></span>
                </td>

                <td width="50%">
                    <label class="switch">
                        <input type="checkbox" data-name="sync_data" id="woosfrest_sync_categories" name="woosfrest_sync_categories" <?php if (isset($wooSfRestProductConfig->sync_data) && $wooSfRestProductConfig->sync_data == true) {
            echo 'checked="checked"';
        }?> />
                        <div class="slider round"></div>
                    </label>
                </td>

            </tr>
            <tr>
                <th scope="row" width="24%">
                    <?php _e('Sync Category/Product Thumbnail Image to Files')?>
                </th>
                <td title="<strong><u>Enable:</u></strong> Synchronize All the Thumbnail images of a product into Salesforce as Files<br>
                <strong><u>Disable:</u></strong> Only the Feature image will be Synced as a document/attachment." class="ttip" width='1%'>
                    <span class="dashicons dashicons-editor-help"></span>
                </td>
                <td width="50%">
                    <label class="switch">
                        <input type="checkbox" data-name="sync_to_files" id="woosfrest_sync_to_files" <?php if (isset($wooSfRestProductConfig->sync_to_files) && $wooSfRestProductConfig->sync_to_files == true) {
            echo 'checked="checked"';
        }?> onchange="showDocFolder(this)" />
                        <div class="slider round"></div>
                    </label>

                </td>

            </tr>
            <tr valign="top" id="imagefolder_row">
                <?php
if (!empty($folder)) {?>
                    <th scope="row" width="24%">
                        <?php _e('Choose Document Folder');?>
                    </th>
                    <td title="Choose the Salesforce Document Folder to Store Feature Image of Products " class="ttip" width='1%'>
                        <span class="dashicons dashicons-editor-help"></span>
                    </td>
                    <td width="50%">
                        <select id="woosfrest_imagefolder" style='width: 250px;' value="" data-name="imagefolder">
                            <option value="" disabled selected>-Search Folder-</option>
                            <?php
if (!empty($wooSfRestProductConfig->imagefolder)) {
            ?>
                                <option value="<?php echo $wooSfRestProductConfig->imagefolder; ?>" selected>
                                    <?php
if (!empty($wooSfRestProductConfig->imagefolder)) {
                $query = "SELECT Id, Name, Type FROM Folder where Type='Document' AND Id= '$wooSfRestProductConfig->imagefolder'";
                $salesforceConnector = new SalesforceConnector();
                $folder_names = $salesforceConnector->getSObject($query, 0);
                foreach ($folder_names as $key => $val) {
                    $folder_name = $val->Name;
                }
                echo $folder_name;
            }?>
                                </option>
                            <?php
}
            ?>

                            <?php
foreach ($folder as $doc) {
                ?>
                                <option value="<?php echo $doc->value; ?>" <?php if (isset($wooSfRestProductConfig->imagefolder) && $wooSfRestProductConfig->imagefolder == $doc->value) {
                    echo 'selected';
                }?>>
                                    <?php echo $doc->text; ?>
                                </option>
                            <?php
}?>

                        </select>


                        <script>
                            $(document).ready(function() {
                                $("#woosfrest_imagefolder").select2({
                                    ajax: {
                                        url: ajaxurl,
                                        type: "post",
                                        dataType: 'json',
                                        delay: 250,
                                        data: function(params) {
                                            return {
                                                search_data: params.term,
                                                action: 'getDocumentFolderWithSearchData' // search term
                                            };
                                        },
                                        processResults: function(response) {
                                            return {
                                                results: response
                                            };
                                        },
                                        cache: true
                                    }
                                });
                            });
                        </script>
                    </td>
            </tr>
        <?php
}?>

        <tr valign="top">
            <?php
if (!empty($pricebook)) {
            ?>
                <th scope="row" width="24%">
                    <?php _e('Choose Price Book');
            ?>
                </th>
                <td title="Select Salesforce Pricebook for Product Price Sync" class="ttip" width='1%'>
                    <span class="dashicons dashicons-editor-help"></span>
                </td>
                <td width="50%">
                    <select id="woosfrest_pricebook" style='width: 250px;' value="" onchange="showUpdateStandard(this)" data-name="pricebook">
                        <option value="" disabled selected>- Search Pricebook -</option>
                        <?php
if (!empty($wooSfRestProductConfig->pricebook)) {
                ?>
                            <option value="<?php echo $wooSfRestProductConfig->pricebook; ?>" selected>
                                <?php
if (!empty($wooSfRestProductConfig->pricebook)) {
                    $query = "SELECT Id, Name, isStandard FROM Pricebook2 where IsActive=true AND Id= '$wooSfRestProductConfig->pricebook'";
                    $salesforceConnector = new SalesforceConnector();
                    $pricebook_names = $salesforceConnector->getSObject($query, 0);
                    if (is_array($pricebook_names)) {
                        foreach ($pricebook_names as $key => $val) {
                            $pricebook_name = $val->Name;
                        }
                        echo $pricebook_name;
                    }
                }?>
                            </option>
                        <?php
}?>

                        <?php
foreach ($pricebook as $pb) {
                ?>
                            <option value="<?php echo $pb->value; ?>" data-standard="<?php if ($pb->standard == true) {
                    echo 'true';
                } else {
                    echo 'false';
                }?>" <?php if (isset($wooSfRestProductConfig->pricebook) && $wooSfRestProductConfig->pricebook == $pb->value) {
                    echo 'selected';
                }?>>
                                <?php echo $pb->text; ?>
                            </option>
                        <?php
}?>
                    </select>

                    <script>
                        $(document).ready(function() {
                            $("#woosfrest_pricebook").select2({
                                ajax: {
                                    url: ajaxurl,
                                    type: "post",
                                    dataType: 'json',
                                    delay: 250,
                                    data: function(params) {
                                        return {
                                            search_data: params.term,
                                            action: 'getPricebookWithSearchData' // search term
                                        };
                                    },
                                    processResults: function(response) {
                                        return {
                                            results: response
                                        };
                                    },
                                    cache: true
                                }
                            });
                        });
                        $("#woosfrest_pricebook").on("select2:select", function(e) {
                            var select_val = $(e.currentTarget).val();
                            $("#pricebook_link").attr("href", "<?php echo $configObj->instance_url . '/'; ?>" + select_val);
                        });
                    </script>

                </td>
                <td width="25%">
                    <?php
if (!empty($wooSfRestProductConfig->pricebook)) {;?>
                        <a href="<?php echo $configObj->instance_url . '/' . $wooSfRestProductConfig->pricebook; ?>" id="pricebook_link">Salesforce Pricebook Page </a>
                    <?php
};?>
                </td>
        </tr>
        <tr>
                <th scope="row" width="24%">
                    <?php _e('Update Product')?>
                </th>
                <td title="<strong><u>Enable:</u></strong> Update product at salesforce end.<br>
                <strong><u>Disable:</u></strong> Product will not be updated at salesforce end." class="ttip" width='1%'>
                    <span class="dashicons dashicons-editor-help"></span>
                </td>
                <td width="50%">
                    <label class="switch">
                        <input type="checkbox" data-name="update_product" id="update_product" <?php if (isset($wooSfRestProductConfig->update_product) && $wooSfRestProductConfig->update_product == true) {
                echo 'checked="checked"';
            }?> />
                        <div class="slider round"></div>
                    </label>

                </td>

            </tr>
        <tr id="standard-pricebook">
            <th scope="row" width="24%">
                <?php _e('Update Existing Entry of Standard Pricebook')?>
            </th>
            <td title="<strong><u>Enable:</u></strong> Synchronize Standard PriceBook data on Product Update<br>
                <strong><u>Disable:</u></strong> Only the entries of Selected PriceBook will be Updated" class="ttip" width='1%'>
                <span class="dashicons dashicons-editor-help"></span>
            </td>
            <td width="50%">
                <label class="switch">
                    <input type="checkbox" id="woosfrest_std_pricebook" <?php if (isset($wooSfRestProductConfig->update_std_pb) && $wooSfRestProductConfig->update_std_pb == true) {
                echo 'checked="checked"';
            }?> data-name="update_std_pb" />
                    <div class="slider round"></div>
                </label>
            </td>
        <?php
}?>
        </tr>
        <tr valign="top">
            <th scope="row" width="24%">
                <?php _e('Enable Auto Product Synchronization');?>
            </th>
            <td title="<strong><u>Enable:</u></strong> Real time Product Synchronization on Product Create and Update <br>
                <strong><u>Disable:</u></strong> Only Manual Synchronization will be functional" class="ttip" width='1%'>
                <span class="dashicons dashicons-editor-help"></span>
            </td>
            <td width="50%">
                <label class="switch">
                    <input type="checkbox" id="auto_product_sync" <?php if (isset($wooSfRestProductConfig->auto_product_sync) && $wooSfRestProductConfig->auto_product_sync == true) {
            echo 'checked="checked"';
        }?> data-name="auto_product_sync" />
                    <div class="slider round"></div>
                </label>
            </td>

        </tr>
        <tr valign="top">
            <th scope="row" width="24%">
                <?php _e('Enable Auto Category Synchronization');?>
            </th>
            <td title="<strong><u>Enable:</u></strong> Real time Category Synchronization on category Create and Update <br>
                <strong><u>Disable:</u></strong> Only Manual Synchronization will be Functional" class="ttip" width='1%'>
                <span class="dashicons dashicons-editor-help"></span>
            </td>
            <td width="50%">
                <label class="switch">
                    <input type="checkbox" id="auto_category_sync" <?php if (isset($wooSfRestProductConfig->auto_category_sync) && $wooSfRestProductConfig->auto_category_sync == true) {
            echo 'checked="checked"';
        }?> data-name="auto_category_sync" />
                    <div class="slider round"></div>
                </label>
            </td>
        </tr>
        </table>

        <input type="hidden" name="product_data_json" id="product_data_json" />
    <?php
submit_button();
    }

}
