<?php

/**
 * Plugin Name:     Woo Salesforce Connector
 * Plugin URI:         https://webkul.com/
 * Description:     Connector that lets your Salesforce connect to WooCommerce store via REST API.
 * Version:         4.1
 * Author:             Webkul Software Pvt. Ltd.
 * Author URI:         https://webkul.com
 * License:         GPL-2.0+
 *
 * @package WooCommerce Salesforce Connectore REST API
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
add_action('plugins_loaded', 'isExistsWoocommerce');

// Define WC_PLUGIN_FILE.
if (!defined('WOOSFREST_PLUGIN_FILE')) {
    define('WOOSFREST_PLUGIN_FILE', __FILE__);
}
function isExistsWoocommerce()
{
    ob_start();
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        deactivate_plugins('woo-salesforce-connector/woo-salesforce-connector.php');
        add_action('admin_notices', 'woocommerceMissingNotice');
        return false;
    } else {
        $GLOBALS['WOOSFREST'] = wooSfRest();
        // $GLOBALS['BIDIRECTIONAL'] = biDirectional();

    }
}
function woocommerceMissingNotice()
{
    echo '<div class="error"><p>' . sprintf(__('Wordpress WooCommerce Salesforce Connector depends on the latest version of %s or later to work!', 'Wordpress WooCommerce Salesforce Connector'), '<a href="http://www.woothemes.com/woocommerce/" target="_blank">' . __('WooCommerce', 'woocommerce-colors') . '</a>') . '</p></div>';
}

// Include the main WooSfRest class.
if (!class_exists('WooSfRest')) {

    include_once dirname(__FILE__) . '/includes/class-woosfrest.php';
}

/**
 * Main instance of WooCommerce Saleforce Connector REST API.
 *
 * Returns the main instance of WooSfRest to prevent the need to use globals.
 *
 * @since  1.0
 * @return WooSfRest
 */
function wooSfRest()
{
    return WooSfRest::instance();
}

function wp_object_to_array($object)
{
    if (!is_object($object) && !is_array($object)) {
        return $object;
    }
    if (is_object($object)) {
        $object = get_object_vars($object);
    }

    return array_map('wp_object_to_array', $object);
}
