<?php
class SalesforceConnector
{
    public static $accessToken = '';
    public static $refreshToken = '';
    public static $instanceUrl = '';
    public static $tokenType = '';

    public function __construct()
    {
        $wooSfRestConfig = get_option('wooSfRestConfig');
        if (!empty($wooSfRestConfig)) {
            self::$accessToken = isset($wooSfRestConfig->access_token) && !empty($wooSfRestConfig->access_token) ? $wooSfRestConfig->access_token : '';
            self::$refreshToken = isset($wooSfRestConfig->refresh_token) && !empty($wooSfRestConfig->refresh_token) ? $wooSfRestConfig->refresh_token : '';
            self::$instanceUrl = isset($wooSfRestConfig->instance_url) && !empty($wooSfRestConfig->instance_url) ? $wooSfRestConfig->instance_url : '';
            self::$tokenType = isset($wooSfRestConfig->token_type) && !empty($wooSfRestConfig->token_type) ? $wooSfRestConfig->token_type : '';
        }
        do_action('woosfrest_loaded');
    }
    /***
     * Use of bulkdata API method for reference only.

    public static function exportDataBulkApi($operation, $bulkData, $sObject, $externalIdField = '')
    {

        $job_url = self::$instanceUrl . "/services/data/v44.0/jobs/ingest";
        $header = array(
            "Content-Type: application/json; charset=UTF-8",
            "Accept: application/json",
            "Authorization: " . self::$tokenType . " " . self::$accessToken,
        );

        $parms = array(
            "object" => $sObject,
            "contentType" => "CSV",
            "operation" => $operation,
            "externalIdFieldName" => $externalIdField,
            "lineEnding" => "LF"
        );

        $http = curl_init($job_url);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_HTTPHEADER, $header);
        curl_setopt($http, CURLOPT_POSTFIELDS, json_encode($parms));
        curl_setopt($http, CURLOPT_CUSTOMREQUEST, 'POST');
        $result = curl_exec($http);
        $response = json_decode($result);
        if (is_array($response)) {
            if ($response[0]->errorCode == 'INVALID_SESSION_ID') {
                if (self::refreshToken()) {
                    return self::exportDataBulkApi($operation, $bulkData, $sObject, $externalIdField);
                } else {
                    return false;
                }
            } elseif (!empty($response[0]->errorCode)) {
                return false;
            }
        }
        $http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
        if ($http_status == 200) {

            $decode_res = json_decode($result, true);

            $auth_header = array(
                "Content-Type: text/csv",
                "Accept: application/json",
                "Authorization: " . self::$tokenType . " " . self::$accessToken,
            );

            $upload_url = self::$instanceUrl . "/services/data/v44.0/jobs/ingest/$decode_res[id]/batches/";
            $curl = curl_init($upload_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $auth_header);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bulkData);
            $curl_result = curl_exec($curl);
            $curl_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($curl_status == 201) {
                $patch_body = array("state" => "UploadComplete");
                $patch_url = self::$instanceUrl . "/services/data/v44.0/jobs/ingest/$decode_res[id]";
                $patch_curl = curl_init($patch_url);
                curl_setopt($patch_curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($patch_curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($patch_curl, CURLOPT_HTTPHEADER, $header);
                curl_setopt($patch_curl, CURLOPT_POSTFIELDS, json_encode($patch_body));
                $patch_result = curl_exec($patch_curl);
                $patch_status = curl_getinfo($patch_curl, CURLINFO_HTTP_CODE);

                if ($patch_status == 200) {

                    $get_url = self::$instanceUrl . "/services/data/v44.0/jobs/ingest/$decode_res[id]/";
                    $get_curl = curl_init($get_url);
                    curl_setopt($get_curl, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($get_curl, CURLOPT_RETURNTRANSFER, true);
                    $get_result = curl_exec($get_curl);
                    $get_status = curl_getinfo($get_curl, CURLINFO_HTTP_CODE);

                    return array($get_status, $decode_res['id']);
                }
            }
        }
    }

    public static function getSuccessFailedResult($url)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => self::$instanceUrl . $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: " . self::$tokenType . " " . self::$accessToken,
                    "Cache-Control: no-cache",
                ),
            )
        );
        $response = curl_exec($curl);
        curl_close($curl);
        $fiveMBs = 5 * 1024 * 1024;
        $tmp = fopen("php://temp/maxmemory:$fiveMBs", 'r+');
        fwrite($tmp, $response);
        rewind($tmp);
        $csv_data = array_map("str_getcsv", preg_split('/\r*\n+|\r+/', stream_get_contents($tmp)));
        array_pop($csv_data);
        array_walk($csv_data, function (&$a) use ($csv_data) {
            if (count($csv_data[0]) == count($a)) {
                $a = array_combine($csv_data[0], $a);
            }
        });
        array_shift($csv_data);

        fclose($tmp);

        return $csv_data;
    }
    */


    public static function getCURLObject($apiEndPoint, $isPost, $otherHeaders)
    {
        $cSession = curl_init();

        curl_setopt($cSession, CURLOPT_URL, self::$instanceUrl . $apiEndPoint);
        curl_setopt($cSession, CURLOPT_RETURNTRANSFER, true);

        if ($isPost) {
            curl_setopt($cSession, CURLOPT_POST, 1);
        }

        $headers = array_merge(array("Authorization:" . self::$tokenType . " " . self::$accessToken), $otherHeaders);
        $headers = array_unique($headers);
        curl_setopt($cSession, CURLOPT_HTTPHEADER, $headers);
        return $cSession;
    }

    /**
     * Method to regenerate access token from refresh token in case of session expired
     *
     * @return boolean
     */
    public static function refreshToken()
    {
        $wooSfRestInstance = get_option('wooSfRestInstance');
        if ($wooSfRestInstance === 'testing') {
            $refreshTokenUrl = "https://test.salesforce.com" . REFRESH_TOKEN . "&client_id=" . APP_CONSUMER_KEY . "&client_secret=" . APP_CONSUMER_SECRET . "&refresh_token=" . self::$refreshToken;
        } else {
            $refreshTokenUrl = "https://login.salesforce.com" . REFRESH_TOKEN . "&client_id=" . APP_CONSUMER_KEY . "&client_secret=" . APP_CONSUMER_SECRET . "&refresh_token=" . self::$refreshToken;
        }
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $refreshTokenUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
            )
        );
        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        curl_close($curl);
        if ($curlError) {
            return false;
        } else {
            $response = json_decode($response);
            if (property_exists($response, 'error')) {
                return false;
            } else {
                $wooSfRestConfig = get_option('wooSfRestConfig');
                self::$accessToken = $response->access_token;
                $wooSfRestConfig->access_token = $response->access_token;
                update_option('wooSfRestConfig', $wooSfRestConfig);
                return true;
            }
        }
    }

    /**
     * Revoke token permission
     *
     * @return boolean
     */
    public static function revokeToken()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => self::$instanceUrl . REQUEST_REVOKE,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "token=" . self::$refreshToken,
                CURLOPT_HTTPHEADER => array(
                    "Cache-Control: no-cache",
                    "Content-Type: application/x-www-form-urlencoded",
                )
            )
        );
        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        if ($curlError) {
            return false;
        } else {
            $response = json_decode($response);
            if (!empty($response)) {
                if (property_exists($response, 'error')) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }

    public static function describeSObject($sObjectName)
    {
        $curlObj = self::getCURLObject(REQUEST_SOBJECT . "$sObjectName/describe/", false, array());
        $response = curl_exec($curlObj);
        $response = json_decode($response);
        if (is_array($response)) {
            if ($response[0]->errorCode == 'INVALID_SESSION_ID') {
                if (self::refreshToken()) {
                    return self::describeSObject($sObjectName);
                } else {
                    return false;
                }
            } elseif (!empty($response[0]->errorCode)) {
                return false;
            }
        } else {
            return $response;
        }
        curl_close($curlObj);
    }

    /**
     * Check if object id exist as per query
     *
     * @param string $query
     *
     * @return mixed
     */
    public static function getSObject($query, $limit = 1, $throwError = 0)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => self::$instanceUrl . REQUEST_QUERY . urlencode($query),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: " . self::$tokenType . " " . self::$accessToken,
                    "Cache-Control: no-cache",
                ),
            )
        );
        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        curl_close($curl);
        if ($curlError) {
            return false;
        } else {
            $response = json_decode($response);
            if (is_array($response) && $response[0]->errorCode == 'INVALID_SESSION_ID') {
                if (self::refreshToken()) {
                    return self::getSObject($query, $limit);
                } else {
                    return false;
                }
            } elseif (is_array($response) && !empty($response[0]->errorCode)) {
               
                    return array($response[0]->message);

                
            } elseif (empty($response->records)) {
                return false;
            } else {
                if ($limit == 1) {
                    return $response->records[0];
                } else {
                    return $response->records;
                }
            }
        }
    }

    /**
     * Get count of salesforce object
     *
     * @return string
     */
    public static function getSObjectCount($sObject)
    {
        if ($sObject) {
            $curl = curl_init();
            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_URL => self::$instanceUrl . REQUEST_QUERY . "SELECT+COUNT()+FROM+" . $sObject,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: " . self::$tokenType . " " . self::$accessToken,
                        "Cache-Control: no-cache",
                    ),
                )
            );

            $response = curl_exec($curl);
            $curlError = curl_error($curl);
            curl_close($curl);
            if ($curlError) {
                $returnResponse = "cURL Error #:" . $curlError;
            } else {
                $response = json_decode($response);
                if (is_array($response) && $response[0]->errorCode == 'INVALID_SESSION_ID') {
                    if (self::refreshToken()) {
                        self::getSObjectCount($sObject);
                    } else {
                        $returnResponse = 'Error: Refresh token issue.';
                    }
                } else {
                    $returnResponse = json_encode($response->totalSize);
                }
            }
        } else {
            $returnResponse = 'Error: sObject not set.';
        }
        return $returnResponse;
    }

    /**
     * Method to update sObject
     *
     * @param string $sObject
     * @param string $objectId
     * @param string $postFields
     *
     * @return boolean
     */
    public static function updateSObject($sObject, $objectId, $postFields)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$instanceUrl . REQUEST_SOBJECT . $sObject . '/' . $objectId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => array(
                "Authorization: " . self::$tokenType . " " . self::$accessToken,
                "Cache-Control: no-cache",
                "Content-Type: application/json",
            ),
        ));
        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        if ($curlError) {
            return false;
        } else {
            $response = json_decode($response);
            if (is_array($response) && $response[0]->errorCode == 'INVALID_SESSION_ID') {
                if (self::refreshToken()) {
                    return self::updateSObject($sObject, $objectId, $postFields);
                } else {
                    return array('Invalid session id: unable to refresh token');
                }
            } elseif (is_array($response) && !empty($response[0]->errorCode)) {
                return array($response[0]->message);
            } elseif ($response == null) {
                return true;
            }
        }
    }

    /**
     * Method to add sObject
     *
     * @param string $sObject
     * @param string $postFields
     *
     * @return mixed
     */
    public static function insertSObject($sObject, $postFields)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => self::$instanceUrl . REQUEST_SOBJECT . $sObject,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: " . self::$tokenType . " " . self::$accessToken,
                    "Cache-Control: no-cache",
                    "Content-Type: application/json",
                ),
            )
        );
        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        curl_close($curl);
        if ($curlError) {
            return false;
        } else {
            $response = json_decode($response);
            if (is_array($response) && $response[0]->errorCode == 'INVALID_SESSION_ID') {
                if (self::refreshToken()) {
                    return self::insertSObject($sObject, $postFields);
                } else {
                    return array('Invalid session id: unable to refresh token');
                }
            } elseif (is_array($response) && !empty($response[0]->errorCode)) {
                return array($response[0]->message);
            } else {
                return $response->id;
            }
        }
    }

    /**
     * Method to upsert sObject
     *
     * @param string $sObject
     * @param string $externalIdField
     * @param string $value
     * @param string $postFields
     *
     * @return boolean
     */
    public static function upsertSObject($sObject, $externalIdField, $value, $postFields)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$instanceUrl . REQUEST_SOBJECT . $sObject . '/' . $externalIdField . '/' . $value,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => array(
                "Authorization: " . self::$tokenType . " " . self::$accessToken,
                "Cache-Control: no-cache",
                "Content-Type: application/json",
            ),
        ));
        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        if ($curlError) {
            return false;
        } else {
            $response = json_decode($response);
            
            if (is_array($response) && $response[0]->errorCode == 'INVALID_SESSION_ID') {
                if (self::refreshToken()) {
                    return self::upsertSObject($sObject, $externalIdField, $value, $postFields);
                } else {
                    return array(false, 'Invalid session id: unable to refresh token');
                }
            } elseif (is_array($response) && !empty($response[0]->errorCode)) {
                return array(false, $response[0]->message);
            } elseif ($response == null) {
                return array(true, self::getIdByExternalId($sObject, $externalIdField, $value));
            } else {
                return array(true, $response);
            }
        }
    }
    public static function getIdByExternalId($sObject, $externalIdField, $value)
    {
        $query = "SELECT Id FROM $sObject WHERE $externalIdField = '" . $value . "'";

        $response = self::getSObject($query);

        if (isset($response) && !empty($response))
            return $response;
    }
    public static function deleteSObject($sObject, $sObjectId)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => self::$instanceUrl . REQUEST_SOBJECT . $sObject . "/" . $sObjectId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: " . self::$tokenType . " " . self::$accessToken,
                    "Cache-Control: no-cache",
                    "Content-Type: application/json"
                ),
            )
        );
        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        curl_close($curl);
        if ($curlError) {
            return false;
        } else {
            $response = json_decode($response);
            if (is_array($response) && $response[0]->errorCode == 'INVALID_SESSION_ID') {
                if (self::refreshToken()) {
                    return self::deleteSObject($sObject, $sObjectId);
                } else {
                    return false;
                }
            } elseif (is_array($response) && !empty($response[0]->errorCode)) {
                return false;
            } else {
                return true;
            }
        }
    }

    public static function generateAccessTokenFromCode($data)
    {
        $curlObj = curl_init();
        if ($data->instance == 'production') {
            update_option('wooSfRestInstance', 'production');
            curl_setopt($curlObj, CURLOPT_URL, 'https://login.salesforce.com/services/oauth2/token?grant_type=authorization_code&client_id=' . APP_CONSUMER_KEY . '&client_secret=' . APP_CONSUMER_SECRET . '&redirect_uri=' . urlencode("https://eshopsync.com/connector/auth.php") . '&code=' . $data->code);
        } else {
            update_option('wooSfRestInstance', 'testing');
            curl_setopt($curlObj, CURLOPT_URL, 'https://test.salesforce.com/services/oauth2/token?grant_type=authorization_code&client_id=' . APP_CONSUMER_KEY . '&client_secret=' . APP_CONSUMER_SECRET . '&redirect_uri=' . urlencode("https://eshopsync.com/connector/auth.php") . '&code=' . $data->code);
        }
        curl_setopt($curlObj, CURLOPT_POST, 1);
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curlObj);
        curl_close($curlObj);

        return $result;
    }
    function getConfigSalesforceData()
    {
        $salesforceData = [];
        try {
            $configObj = get_option('wooSfRestConfig');
            $urlArr = explode('/', $configObj->id);
            $email = end($urlArr);
            $orgId = $urlArr[key($urlArr) - 1];

            $response = self::getSObject("SELECT id, Name FROM Organization WHERE id='$orgId'", 1, 1);
            if (is_array($response) && !empty($response[0]->errorCode)) {
                $salesforceData['orgDetails'] = false;
                WooSfRest::showNotice('error', 'Connection Error', $response[0]->message);
                WooSfRest::showNotice('Notice', 'Connection Error', 'Need Help <a href="https://webkul.uvdesk.com/en/customer/create-ticket/">Contact Us</a>');
            } else
                $salesforceData['orgDetails'] = $response;
            $salesforceData['userName'] = self::getSObject("SELECT id, username, email, name FROM User WHERE id='$email'");

            $contactRoles = SalesforceConnector::describeSObject('OpportunityContactRole');
            if (isset($contactRoles) && !empty($contactRoles))
                $salesforceData['oppContactRole'] = $contactRoles->fields;

            $query = "SELECT Id, Name, Type FROM Folder where Type='Document'";
            $documentFolder = self::getSObject($query, 0);
            $document = [];
            if ($documentFolder) {
                foreach ($documentFolder as $key => $folder) {
                    $document[$key] = new stdclass();
                    $document[$key]->text = $folder->Name;
                    $document[$key]->value = $folder->Id;
                }
                $salesforceData['folder'] = $document;
            }

            $query = "SELECT Id, Name, isStandard FROM Pricebook2 where IsActive=true";
            $pricebook = self::getSObject($query, 0);
            $sfpricebook = [];
            if ($pricebook) {
                foreach ($pricebook as $key => $book) {
                    $sfpricebook[$key] = new stdclass();
                    $sfpricebook[$key]->text = $book->Name;
                    $sfpricebook[$key]->value = $book->Id;
                    $sfpricebook[$key]->standard = $book->IsStandard;
                }
                $salesforceData['pricebook'] = $sfpricebook;
            }

            $response = self::describeSObject("account");
            $salesforceData['isPersonOrg'] = false;
            if (isset($response->fields)) {
                foreach ($response->fields as $accountField) {
                    if ($accountField->name == 'IsPersonAccount') {
                        $salesforceData['isPersonOrg'] = true;
                        break;
                    }
                }
            }
            if (ORG_TYPE == 'BA' || ORG_TYPE == 'BAFM') {
                $query = "SELECT Id, Name, SobjectType FROM RecordType where SobjectType = 'Account' OR SobjectType = 'Contact' OR SobjectType = 'Opportunity'";
                $allRecordType_Ids = self::getSObject($query, 0);
                $sfAccrecordType = [];
                $sfContactrecordType = [];
                $sfOpprecordType = [];
                if ($allRecordType_Ids) {
                    foreach ($allRecordType_Ids as $key => $book) {
                        if ($book->SobjectType == 'Account') {
                            $sfAccrecordType[$key] = new stdclass();
                            $sfAccrecordType[$key]->text = $book->Name;
                            $sfAccrecordType[$key]->value = $book->Id;
                            

                        }
                        if ($book->SobjectType == 'Contact') {
                            $sfContactrecordType[$key] = new stdclass();
                            $sfContactrecordType[$key]->text = $book->Name;
                            $sfContactrecordType[$key]->value = $book->Id;
                        }
                        if ($book->SobjectType == 'Opportunity') {
                            $sfOpprecordType[$key] = new stdclass();
                            $sfOpprecordType[$key]->text = $book->Name;
                            $sfOpprecordType[$key]->value = $book->Id;
                        }
                    }
                    if (isset($sfOpprecordType) && !empty($sfOpprecordType))
                        $salesforceData['OpportunityRecordType'] = $sfOpprecordType;
                    if (isset($sfContactrecordType) && !empty($sfContactrecordType))
                        $salesforceData['ContactRecordType'] = $sfContactrecordType;
                    if (isset($sfAccrecordType) && !empty($sfAccrecordType))
                        $salesforceData['AccountRecordType'] = $sfAccrecordType;
                }
                $wooSfRestAccountConfig = get_option('wooSfRestAccountConfig');

                if (isset($salesforceData['AccountRecordType']) && !empty($salesforceData['AccountRecordType'])) {
                    if (isset($wooSfRestAccountConfig->account_recordtype)) {
                        $accountRecordTypeId = $wooSfRestAccountConfig->account_recordtype;
                        $query = "SELECT Id, Name FROM Account Where RecordTypeId = '$accountRecordTypeId' Order By Name ";
                    } else {
                        $accountRecordTypeId = $salesforceData['AccountRecordType'][0]->value;
                        $query = "SELECT Id, Name FROM Account Where RecordTypeId ='$accountRecordTypeId' Order By Name ";
                    }
                } else {
                    $query = "SELECT Id, Name FROM Account Order By Name ";
                }

                $allRecordType_Ids = self::getSObject($query, 0);

                $sfrecordType = [];
                if ($allRecordType_Ids) {
                    foreach ($allRecordType_Ids as $key => $book) {
                        $sfrecordType[$key] = new stdclass();
                        $sfrecordType[$key]->text = $book->Name;
                        $sfrecordType[$key]->value = $book->Id;
                        $sfrecordType[$key]->id = $book->Id;
                    }
                    $salesforceData['Accounts'] = $sfrecordType;
                }
            }


            if (ORG_TYPE == 'PA' || ORG_TYPE == 'PAFM') {

                $query = "SELECT Id, Name,IsPersonType FROM RecordType" ;
                $allRecordType_Ids = self::getSObject($query, 0);
                
                $sfrecordType = [];
                $sfrecordTypeBusiness = [];
                if ($allRecordType_Ids) {
                    foreach ($allRecordType_Ids as $key => $record) {
                        if(!$record->IsPersonType){
                            $sfrecordTypeBusiness[$key] = new stdclass();
                            $sfrecordTypeBusiness[$key]->text = $record->Name;
                            $sfrecordTypeBusiness[$key]->value = $record->Id;
                            $sfrecordTypeBusiness[$key]->type = 'B';
                            
                        }else{
                            $sfrecordTypeBusiness[$key] = new stdclass();
                            $sfrecordTypeBusiness[$key]->text = $record->Name;
                            $sfrecordTypeBusiness[$key]->value = $record->Id;
                            $sfrecordTypeBusiness[$key]->type = 'P';
                            $sfrecordType[$key] = new stdclass();
                            $sfrecordType[$key]->text = $record->Name;
                            $sfrecordType[$key]->value = $record->Id;
                            $sfrecordType[$key]->type = 'P';

                        }
                        
                    }
                    $salesforceData['personAccRecordType'] = $sfrecordType;

                    if(isset($sfrecordTypeBusiness) && !empty($sfrecordTypeBusiness)){
                        $salesforceData['AccountRecordType'] = $sfrecordTypeBusiness;
                        $wooSfRestAccountConfig = get_option('wooSfRestAccountConfig');

                        if (isset($salesforceData['AccountRecordType']) && !empty($salesforceData['AccountRecordType'])) {
                            if (isset($wooSfRestAccountConfig->account_recordtype)) {
                                $accountRecordTypeId = $wooSfRestAccountConfig->account_recordtype;
                                $query = "SELECT Id, Name FROM Account Where RecordTypeId = '$accountRecordTypeId' Order By Name ";
                            } else {
                                $accountRecordTypeId = $salesforceData['AccountRecordType'][0]->value;
                                $query = "SELECT Id, Name FROM Account Where RecordTypeId ='$accountRecordTypeId' Order By Name ";
                            }
                        } else {
                            $query = "SELECT Id, Name FROM Account Order By Name ";
                        }
        
                        $allRecordType_Ids = self::getSObject($query, 0);
        
                        $sfrecordType = [];
                        if ($allRecordType_Ids) {
                            foreach ($allRecordType_Ids as $key => $book) {
                                $sfrecordType[$key] = new stdclass();
                                $sfrecordType[$key]->text = $book->Name;
                                $sfrecordType[$key]->value = $book->Id;
                                $sfrecordType[$key]->id = $book->Id;
                            }
                            $salesforceData['Accounts'] = $sfrecordType;
                        }

                    }
                }
            }
        } catch (Exception $e) {
            $salesforceData['error'] = $e->getMessage();
            $log = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();

            WooSfRest::createLog('Product', $log, true);
        }
        return $salesforceData;
    }
}
