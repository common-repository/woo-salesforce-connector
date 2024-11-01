<?php
defined('ABSPATH') || exit;
/**
 * WooSfRestInstall Class.
 */
class WooSfRestInstall
{
    /**
     * Background update class.
     *
     * @var object
     */
    private static $background_updater;

    /**
     * Hook in tabs.
     */
    public static function init()
    {
        self::install();
    }

    /**
     * Install WooSfRest.
     */
    public static function install()
    {
        // Check if we are not already running this routine.
        if (get_transient('woosfrest_installing') === 'yes') {
            return;
        }
        // If we made it till here nothing is running yet, lets set the transient now.
        set_transient('woosfrest_installing', 'yes');

        /*Uncomment if table data migration is to be started on installation of connector */
        // WooSfRest::migrateTableData();

        WooSfRest::migrateToNonJsonMeta();
        
        delete_transient('woosfrest_installing');

        do_action('woosfrest_flush_rewrite_rules');
        do_action('woosfrest_installed');
    }

    /**
     * Return a list of WooCommerce Salesforce connector REST API tables. Used to make sure all WC tables are dropped when uninstalling the plugin
     * in a single site or multi site environment.
     * 
     * Currently being used to fetch tables for data migration only
     *
     * @return array WC tables.
     */
    public static function getTables()
    {
        global $wpdb;

        $tables = array(
            "{$wpdb->prefix}woosfrest_orders",
            "{$wpdb->prefix}woosfrest_products",
            "{$wpdb->prefix}woosfrest_users",
            "{$wpdb->prefix}woosfrest_categories"
        );
        return $tables;
    }

    /**
     * Delete all mapped data from meta tables for all Woocommerce objects
     *
     * @return void
     */
    public static function deleteMappingForAllObjects()
    {
        delete_metadata('term', 0, 'wk_sf_category_id', false, true);
        delete_metadata('term', 0, 'wk_sf_category_error', false, true);
        delete_metadata('post', 0, 'wk_sf_order_id', false, true);
        delete_metadata('post', 0, 'wk_sf_opp_id', false, true);
        delete_metadata('post', 0, 'wk_sf_order_err', false, true);
        delete_metadata('post', 0, 'wk_sf_opp_err', false, true);
        delete_metadata('post', 0, 'wk_sf_product_id', false, true);
        delete_metadata('post', 0, 'wk_sf_product_err', false, true);
        delete_metadata('user', 0, 'wk_sf_account_id', false, true);
        delete_metadata('user', 0, 'wk_sf_contact_id', false, true);
        delete_metadata('user', 0, 'wk_sf_contact_error', false, true);
        delete_metadata('user', 0, 'wk_sf_account_error', false, true);
    }
}

WooSfRestInstall::init();
