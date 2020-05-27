<?php
namespace controllers\providers;

use \controllers\ControllerBase;
use models\Device;
use models\Preauth;
use models\ProviderUser;
use models\User;
use Ubiquity\orm\DAO;
use Ubiquity\controllers\Startup;

/**
 * Reddit Provider
 **/
class Reddit extends ControllerBase {

	private $provider = 'reddit';
	private $clientID = '';
	private $secret = '';

	private $apiURI = '';
	private $oauthURI = '';
	private $oauthAuthorizeURI = '';
	private $oauthTokenURI = '';
	private $redirectURI = '';
	
	private $scopes = '';
	
	public function __construct(){
		$this->setupProviderConfig();
	}
	
	private function setupProviderConfig(){
		$providerConfig = Startup::$config['providers'][$this->provider];
		
		$this->clientID = $providerConfig['clientID'];
		$this->secret = $providerConfig['secret'];
		$this->apiURI = $providerConfig['apiURI'];
		$this->oauthURI = $providerConfig['oauthURI'];
		$this->oauthAuthorizeURI = $providerConfig['oauthAuthorizeURI'];
		$this->oauthTokenURI = $providerConfig['oauthTokenURI'];
		$this->redirectURI = $providerConfig['redirectURI'];
		$this->scopes = $providerConfig['scopes'];
	}
	
	public function index(){return [];}
	
	/**
	 * @get("reddit/login")
	 */
	public function login(){
		
		$_SESSION['app'] = ($_GET['app'] ? true : false);
		
		if(!empty($_GET['preauth'])){
			$preauth = DAO::getOne(Preauth::class, 'id = ?', false, [$_GET['preauth']]);
			if(!empty($preauth)){
				$_SESSION['preauth'] = $preauth;
				header('Location: '. $this->oauthAuthorizeURI . '?response_type=code&client_id='.$this->clientID.'&redirect_uri='.$this->redirectURI.'&scope='.$this->scopes.'&state='.rand());
			}else{
				die('Invalid preauth');
			}
		}else{
			die('No preauth provided');
		}
	}
	
	/**
	 * @get("reddit/complete")
	 */
	public function complete(){
		
		if(!empty($_SESSION['preauth'])){
			
			if(isset($_GET['code']) && !empty($_GET['code'])){
				try{
					$tokens = $this->getAccessTokens($_GET['code']);
					if(!empty($tokens)){
						$userData = $this->getUser($tokens['access_token']);
						
						if(!empty($userData)){
							$providerUser = DAO::getOne(ProviderUser::class, 'provider = ? AND unique_id = ?', false, [$this->provider, $userData['id']]);
							$user = null;
							
							if(!empty($providerUser)){
								if(!empty($providerUser->getUserId())){
									$user = DAO::getById(User::class, $providerUser->getUserId());
								}
							}else{
								$providerUser = new ProviderUser();
								$providerUser->setProvider($this->provider);
							}
							
							$providerUser->setUniqueId($userData['id']);
							$providerUser->setAccessToken($tokens['access_token']);
							$providerUser->setAccessTokenExpiry(date('Y-m-j H:i',time() + $tokens['expires_in']));
							
							
							if($user === null){
								$user = new User();
								$user->setUsername($userData['name']);
								
								if(!DAO::save($user)){
									throw new \Exception('Failed to save user account');
								}
							}
							
							$providerUser->setUserId($user->getId());
							if(DAO::save($providerUser)){
								$preauth = $_SESSION['preauth'];
								$device = Device::registerDevice($user->getId(), $preauth->getDeviceSecret(), $preauth->getDeviceName(), $preauth->getDevicePlatform());
								
								if($_SESSION['app']){
									header('Location: sosa://login/preauth/success/'.$device->getId());
								}
							}
						}
					}
				}catch(\Exception $e){
					die($e->getCode() . ' - ' . $e->getMessage());
				}
			}
			
		}else{
			die('Invalid preauth');
		}
	}
	
	/***
	 * Retrieves the user data and access tokens for the provided accessCode
	 *
	 * @param string $accessCode
	 * @return mixed|null
	 * @throws \Exception
	 */
	private function getAccessTokens(string $accessCode=''){
		return ($accessCode) ? $this->request($this->oauthTokenURI, [
				'redirect_uri' => $this->redirectURI,
				'client_id' => $this->clientID,
				'grant_type' => 'authorization_code',
				'code' => $accessCode], true) : null;
	}
	
	/***
	 * Retrieves the user data for the provided accessToken
	 *
	 * @param string $accessToken
	 * @return mixed|null
	 * @throws \Exception
	 */
	private function getUser(string $accessToken=''){
		return ($accessToken) ? $this->request($this->oauthURI . '/api/v1/me', [], false, $accessToken) : null;
	}
	
	/***
	 * Fires a request to an endpoint and returns json_decoded data
	 *
	 * @param String $endpoint
	 * @param array $options
	 * @param bool $post
	 * @param String $accessToken
	 * @return array
	 * @throws \Exception
	 */
	private function request(string $endpoint, array $options=[], bool $post=false, string $accessToken=''){
		
		$headers = [];
		
		$ch = curl_init($endpoint);
		
		$curlOptions[CURLOPT_CONNECTTIMEOUT] = 60;
		$curlOptions[CURLOPT_DNS_CACHE_TIMEOUT] = 25;
		$curlOptions[CURLOPT_TIMEOUT] = 60;
		
		$curlOptions[CURLOPT_RETURNTRANSFER] = 1;
		$curlOptions[CURLOPT_FOLLOWLOCATION] = 1;
		$curlOptions[CURLOPT_USERAGENT] = $_SERVER['HTTP_USER_AGENT'];
		
		if(!empty($accessToken)){
			$headers[] = "Authorization: bearer " . $accessToken;
			$curlOptions[CURLOPT_HEADER] = 0;
			$curlOptions[CURLINFO_HEADER_OUT] = 0;
			
			
		}else{
			$curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
			$curlOptions[CURLOPT_USERPWD] = $this->clientID . ":" . $this->secret;
			$curlOptions[CURLOPT_SSLVERSION] = 4;
			$curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
			$curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
		}
		
		$curlOptions[CURLOPT_HTTPHEADER] = $headers;
		
		if (!empty($options) && $post){
			$curlOptions[CURLOPT_CUSTOMREQUEST] = 'POST';
			$curlOptions[CURLOPT_POSTFIELDS] = $options;
		}
		curl_setopt_array($ch, $curlOptions);
		
		$data = curl_exec($ch);
		curl_close($ch);
		
		if (empty($data)) {
			throw new \Exception("Over capacity", 2);
		}else{
			$returnData = json_decode($data, true);
			if(!$returnData) throw new \Exception("Over capacity",1);
			
			if(isset($returnData['status']) && $returnData['status'] !== 200){
				throw new \Exception($returnData['data']['error'],$returnData['status']);
			}else{
				return $returnData;
			}
		}
		
	}
}
