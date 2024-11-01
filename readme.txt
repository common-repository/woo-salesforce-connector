=== Wordpress WooCommerce Salesforce Connector ===
Contributors: webkul
Tags: wordpress salesforce, woocommerce salesforce, salesforce, synchronization, connector, Salesforce Connector, webkul, WooCommerce Salesforce Integration
Requires at least: 6.4
Tested up to: 6.5.4
Requires PHP: 7.4
Stable tag: 4.1
License: GNU/GPL for more info see license.txt included with plugin
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

== Description==

Integrate [eShopSync for WooCommerce Salesforce CRM](https://eshopsync.com/wordpress/) with complete auto-sync functionality having B2B and B2C Concept, Real Time Synchronization, Bi-directional Sync, Manual Field Mapping Features, Users, Categories, Products and Orders(Guest and Registered) Sync.
The idea behind the concept is to increase data integrity, maintain proper inventory of stock, real time order generation, provide a brilliant customer care services and much more.

You need to install this [application](https://appexchange.salesforce.com/appxListingDetail?listingId=a0N30000000rHmIEAU) into your Salesforce org to complete the setup process of this connector.

For the full version [Click here](https://store.webkul.com/Wordpress-WooCommerce-Salesforce-Connector.html/)



==FEATURES==

* It gives the concept of a ‘Service-first’ approach.
* Acts as a bridge between WooCommerce and Salesforce.
* The synchronization process will run in the background when you export the records.
* Lightning features support and provide you with an interactive design.
* Can process bulk data synchronization from WooCommerce to Salesforce.
* Option to Map WooCommerce Order Status to Salesforce Order Status and/or Opportunity stages.
* Option to check the Custom error and success message logging while importing or exporting objects.
* Provided a feature to Synchronize individual Tax rates to Salesforce fields when syncing orders from Woocommerce to Salesforce.
* Option to Synchronize WooCommerce users as Business or Person accounts in Salesforce.
* Dynamic field mapping is provided for users, products, and Orders, you can add/remove field mappings as per your need.
* Option to enable or disable Real-Time synchronization of users, products, categories, and orders from WooCommerce to Salesforce.
* Manual synchronization of WooCommerce Users, Products, Categories, and Orders to Salesforce is also provided.
* Users can navigate to Salesforce by clicking on the Salesforce IDs from WordPress WooCommerce Salesforce Connector end.
* Option to synchronize the Product/Category image in Notes and attachment or folders to Salesforce.
* Synchronize Products Price to the selected Price book of Salesforce.
* Only Synchronization of Simple products from WooCommerce site to salesforce.
* Fully based on REST API using OAuth2.0 of Salesforce to provide an easy, fast, and secure setup for the user.
* HPOS compatible.

Note: In the REST API functionality user would not need to generate the WSDL file and the Security Token.

If you want to build the dedicated native e-commerce store on Salesforce then you can use our Salesforce app [WedgeCommerce](https://wedgecommerce.com/) for migrate your data please contact us here [WedgeCommerce migration](https://wedgecommerce.com/migration/)

How To Configure Connector Video Tutrial :

https://www.youtube.com/watch?v=EicQCMfA9oI


== Installation ==

1. Upload the `woo-salesforce-connector` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin using the 'WooCommerce Salesforce Connector' menu

== Frequently Asked Questions ==

= Error FIELD_INTEGRITY_EXCEPTION:field integrity exception: AccountId, ContractId (Make sure the order’s account and the contract’s account are the same.) =

Possible reason : While changing Sync settings from “ Sync all contacts to Single Account” to “Sync All Contact to Individual Account”.
Once you change the sync type and saved it. This issue will only impact Registered users stored in WooCommerce account. Then, follow the steps to resolve the error.
Sync Users first once you update Syncing type as desired.
Only Delete ” WooCommerce ” account from Salesforce that was previously mentioned to sync all contacts in Single account. Deleting WooCommerce account will automatically delete Orders and related contracts. There is no need to make any changes with Guest User Account as it will remain with same functionality.
Once you delete the ”WooCommerce” account, automatically all the Orders and Contracts linked with this account will get removed. Then, Sync Orders, it will sync all the orders in the corresponding account normally. This will fix the issue completely.;

= Error API_DISABLED_FOR_ORG : API is not enabled for this Organization or Partner. =

You need to enable API in your Salesforce Org. For Enterprise edition & up, API is enabled by default. To verify follow the steps : From Setup | Users | Profiles | Choose the concerned Profile of the User | Scroll down to Administrative Permissions | API Enabled must be check marked
Note : For Professional Edition, you need to get API enabled Professional Edition to avail connector Sync features.

= Error string INVALID_QUERY_FILTER_OPERATOR: SELECT AccountId FROM Contact where Id= ‘–‘ ^ ERROR at Row:1:Column:37 invalid ID field: =

You need to generate updated WSDL file. Go through the blog to generate updated WSDL file : How to generate WSDL file Further, you need to upload generated WSDL file at WordPress end in WWS Connector settings and Save it. Then, try to sync Users, Categories, products and Orders.

**Note:** This is a issue related to previous versions of this extension. The present version 2.0 is totally based on REST and so no need for WSDL file.

= Error INVALID_FIELD_FOR_INSERT_UPDATE: Object Name: bad field names on insert/update call: Field Name =

From Setup | Customize | Object | Fields | Click on the Concerned field | Click View Field Accessibility|| Click on the Hidden on the concerned Profile | Mark it visible and click Save. Follow this step for all the fields with concerned object thrown in error. And upload the updated WSDL file in WWS Connector. It will fix this issue.

**Note:** This is a issue related to previous versions of this extension. The present version 2.0 is totally based on REST and so no need for WSDL file.

= WSDL file not visible in drop-down after successful upload =

It might be possible that the user with which you have logged into WordPress might not have write permission. To verify it, follow the path mentioned below. Login with your FTP details, kindly go through the path : wp-content/plugins/woo-salesforce-connector/classes/views/services/ You need to verify that is there any directory available as : custom_wsdl

* If directory not found, then you need to provide the write permission from admin folder through root folder and then try to upload the WSDL file manually further. Refresh the WWS Connector page and choose the uploaded WSDL file from drop down and Save it. It will surely work and fix your issue.
* If directory found then open it and verify that the WSDL file that you have uploaded is available there or not.
* If file not found, then you need to provide the write permission from admin folder through root folder and then try to upload the WSDL file manually further. Refresh the WWS Connector page and choose the uploaded WSDL file from drop down and Save it.
* If file found, re upload the WSDL file manually and refresh the WWS Connector settings page and select the WSDL file from the drop-down and Save it.

**Note:** This is a issue related to previous versions of this extension. The present version 2.0 is totally based on REST and so no need for WSDL file.

= For more frequent changes =

Visit [WordPress WooCommerce Salesforce Connector Blog](https://webkul.com/blog/wordpress-woocommerce-salesforce-connector/)

= For any other question feel free to do so. =

For any Query please generate a ticket at [Webkul Ticket](https://webkul.uvdesk.com/en/customer/create-ticket/)

== Changelog ==

= 1.0 =
Initial release
No code modification, changes made only in readme.txt

= 2.0 =
REST API integrated with additional code improvements

= 3.0 =
Screen option added in all view.
Option provided for test/refresh connection.
Option provided to flush all setting data and mapping
Option added to sync account with company name
Salesforce ids are clickable and navigate to Salesforce end on particular object detail pages.

= 3.1 =
Product and Category real time sync functionality added.(In full version)
In products multiple images sync as a file functionality added.(In full version)

= 4.1 =
User and Order real time sync functionality added using background job.(In full version)
Option to update product at salesforce end.

== Upgrade Notice ==

= 1.0.0 =
All files added
changes made in readme.txt
