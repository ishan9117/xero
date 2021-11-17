<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
ini_set('display_errors', 'On');
//include_once APPPATH.'/third_party/xero/authorization.php';
require APPPATH . '/third_party/xero/vendor/autoload.php';
require_once('xero_storage.php');
require_once('xero_config.php');
  // Use this class to deserialize error caught
use XeroAPI\XeroPHP\AccountingObjectSerializer;
class Xero {
    public $apiInstance;
    public $organisation;
    public $xeroTenantId;
    
    public function __construct()
    {

        if(DISABLE_XERO){
            return false;
        }

        $storage = new StorageClass();
        $config = new ConfigClass(); 
         $this->xeroTenantId = $xeroTenantId =  $storage->getSession()['tenant_id'];

         // echo '<pre>';
         // print_r($this->xeroTenantId);
         // //print_r($storage);
         // exit();
     

        if ($storage->getHasExpired()) {
            $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => $config->connect_config()['clientId'],   
            'clientSecret'            => $config->connect_config()['clientSecret'],
            'redirectUri'             => $config->connect_config()['redirectUri'],
            'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
            'urlAccessToken'          => 'https://identity.xero.com/connect/token',
            'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
        ]);

        $newAccessToken = $provider->getAccessToken('refresh_token', [
          'refresh_token' => $storage->getRefreshToken()
        ]);

            // Save my token, expiration and refresh token
        $storage->setToken(
                $newAccessToken->getToken(),
                $newAccessToken->getExpires(),
                $xeroTenantId,
                $newAccessToken->getRefreshToken(),
                $newAccessToken->getValues()["id_token"] );
        }

      /*  echo "<pre>";
        print_r($xeroTenantId);
        exit();*/

        $config = XeroAPI\XeroPHP\Configuration::getDefaultConfiguration()->setAccessToken( (string)$storage->getSession()['token'] );
        $config->setHost("https://api.xero.com/api.xro/2.0");

        $this->apiInstance = $apiInstance = new XeroAPI\XeroPHP\Api\AccountingApi(
          new GuzzleHttp\Client(),
          $config
        );
        
    }
}