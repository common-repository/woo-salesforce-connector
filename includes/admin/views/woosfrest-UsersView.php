<?php

/**
 * @class   WooSfRestUsersView
 *
 * @version 2.0
 */

class WooSfRestUsersView
{
    /**
     * Return a singleton instance of WooSfRestUsersView class
     *
     * @brief Singleton
     *
     * @return WooSfRestUsersView
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
     * Create an instance of WooSfRestUsersView class
     *
     * @brief Construct
     *
     * @return WooSfRestUsersView
     */
    public function __construct()
    {
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
        ?>
        <div class="upgrade-alert">
            <p>
                <a style="font-weight: bold;" target="_blank" href="https://store.webkul.com/Wordpress-WooCommerce-Salesforce-Connector.html">
                    Upgrade to the Full edition to use full version
                </a>
            </p>
            Do you have full version?<br>
            <ol>
                <li>Please uninstall this version.</li>
                <li>Remove files for this version</li>
                <li>Install new version</li>
            </ol>
        </div>
        <?php
    }
}
