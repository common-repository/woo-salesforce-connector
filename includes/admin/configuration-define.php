<?php

define('WOOSFREST_BASE_DIR', __DIR__);

define('APP_CONSUMER_KEY', '3MVG9ZL0ppGP5UrDmNAiEGMQGPYAYCNk.hb1xNVmqWoDi21Ug18eq6ucsSD1ZanfepgDlh0BNUF3gNjwjZaEM');
define('APP_CONSUMER_SECRET', '3093543145986445979');

define('REQUEST_AUTHORIZE', '/services/oauth2/authorize?response_type=code');
define('REQUEST_TOKEN', '/services/oauth2/token?grant_type=authorization_code');
define('REFRESH_TOKEN', '/services/oauth2/token?grant_type=refresh_token');
define('REQUEST_QUERY', '/services/data/v44.0/query/?q=');
define('REQUEST_SOBJECT', '/services/data/v44.0/sobjects/');
define('REQUEST_REVOKE', '/services/oauth2/revoke');
define('ORG_TYPE', 'BAFM');
