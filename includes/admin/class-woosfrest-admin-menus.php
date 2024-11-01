<?php
/**
 * @class   WooSfRestAdminMenus
 *
 * @version 2.0
 */

defined('ABSPATH') || exit;

if (class_exists('WooSfRestAdminMenus', false)) {
    return new WooSfRestAdminMenus();
}

/**
 * WooSfRestAdminMenus Class.
 */

class WooSfRestAdminMenus
{
    public static $capabilities = 'manage_options';
    /**
     * Hook in tabs.
     */
    public function __construct()
    {
        // Add menus.
        add_action('admin_menu', array($this, 'adminMenu'));
        add_filter('set-screen-option', array(__class__, 'woosf_set_option'), 10, 3 );
    }
    function woosf_set_option($status, $option, $value)
    {
        if ('woosf_items_per_page' == $option) return $value;
        return $status;
    }

    /**
     * Add menu items.
     */
    public function adminMenu()
    {
        add_menu_page('WooSfRest', 'WooCommerce Salesforce Connector', WooSfRestAdminMenus::$capabilities, 'woosfrest_connector_settings', '', '', 100);

        add_submenu_page('woosfrest_connector_settings', 'Configuration', 'Configuration', WooSfRestAdminMenus::$capabilities, 'woosfrest_connector_settings', 'WooSfRestAdminMenus::wooSfRestSettings');
        if (get_option('wooSfRestconfig')) {
            add_submenu_page('woosfrest_connector_settings', 'Syncronize Categories', 'Syncronize Categories', WooSfRestAdminMenus::$capabilities, 'sync_cat', 'WooSfRestAdminMenus::wooSfRestCategories');
            $hook = add_submenu_page('woosfrest_connector_settings', 'Syncronize Products', 'Syncronize Products', WooSfRestAdminMenus::$capabilities, 'sync_prod', 'WooSfRestAdminMenus::wooSfRestProducts');
            add_action("load-" . $hook, array('WooSfRestAdminMenus', 'woosf_add_option'));
            $hook = add_submenu_page('woosfrest_connector_settings', 'Syncronize Users', '<span style="color:orange">Synchronize Users</span> (full version only)', WooSfRestAdminMenus::$capabilities, 'sync_user', 'WooSfRestAdminMenus::wooSfRestUsers');

            add_action("load-". $hook, array('WooSfRestAdminMenus', 'woosf_add_option'));
            $hook = add_submenu_page('woosfrest_connector_settings', 'Syncronize Orders', '<span style="color:orange">Synchronize Orders</span> (full version only)', WooSfRestAdminMenus::$capabilities, 'sync_order', 'WooSfRestAdminMenus::wooSfRestOrder');
            add_action("load-" . $hook, array('WooSfRestAdminMenus', 'woosf_add_option'));

        }
    }

    public static function wooSfRestSettings()
    {
        require_once 'views/woosfrest-SettingsView.php';
        $pageView = new WooSfRestSetingsView();
        $pageView->display();
    }

    public static function wooSfRestCategories()
    {
        require_once 'views/woosfrest-CategoriesView.php';
        $pageView = new WooSfRestCategoriesView();
        $pageView->display();
    }

    public static function wooSfRestProducts()
    {
        require_once 'views/woosfrest-ProductsView.php';
        $pageView = new WooSfRestProductsView();
        $pageView->display();
    }

    public static function wooSfRestUsers()
    {
        require_once 'views/woosfrest-UsersView.php';
        $pageView = new WooSfRestUsersView();
        $pageView->display();
    }

    public static function wooSfRestOrder()
    {
        require_once 'views/woosfrest-OrdersView.php';
        $pageView = new WooSfRestOrdersView();
        $pageView->display();
    }
    public static function woosf_add_option()
    {
        $option = 'per_page';
        $args = array(
            'label' => 'Items Per Page',
            'default' => 5,
            'option' => 'woosf_items_per_page'
        );
        add_screen_option($option, $args);
    }

}

return new WooSfRestAdminMenus();
