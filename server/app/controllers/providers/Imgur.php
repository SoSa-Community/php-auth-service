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
 * Imgur Controller
 **/
class Imgur extends ControllerBase {

	private $provider = 'imgur';
	private $clientID = '';
	private $secret = '';
	private $mashapeAuth = '';
	private $apiURI = '';
	private $oauthURI = '';
	
	
	public function __construct(){
		$this->setupProviderConfig();
	}
	
	private function setupProviderConfig(){
		$providerConfig = Startup::$config['providers'][$this->provider];
		
		$this->clientID = $providerConfig['clientID'];
		$this->secret = $providerConfig['secret'];
		$this->mashapeAuth = $providerConfig['mashapeAuth'];
		$this->apiURI = $providerConfig['apiURI'];
		$this->oauthURI = $providerConfig['oauthURI'];
	}
	
	public function index(){return [];}
	
	/**
	 * @get("imgur/login")
	 */
	public function login(){
		
		$_SESSION['app'] = ($_GET['app'] ? true : false);
		
		if(!empty($_GET['preauth'])){
			$preauth = DAO::getOne(Preauth::class, 'id = ?', false, [$_GET['preauth']]);
			if(!empty($preauth)){
				$_SESSION['preauth'] = $preauth;
				header('Location: '. $this->oauthURI.'/authorize/?client_id='.$this->clientID.'&response_type=code&state=initializing');
			}else{
				die('Invalid preauth');
			}
		}else{
			die('No preauth provided');
		}
	}
	
	/**
	 * @get("imgur/app_login")
	 */
	public function appLogin(){
		
		return $this->login();
	}
	
	/**
	 * @get("imgur/complete")
	 */
	public function complete(){
		if(!empty($_SESSION['preauth'])){
			
			if(isset($_GET['code']) && !empty($_GET['code'])){
				try{
					$tokens = $this->getAccessTokens($_GET['code']);
					if(!empty($tokens)){
						$providerUser = DAO::getOne(ProviderUser::class, 'provider = ? AND unique_id = ?', false, [$this->provider, $tokens['account_id']]);
						$user = null;
						
						if(!empty($providerUser)){
							if(!empty($providerUser->getUserId())){
								$user = DAO::getById(User::class, $providerUser->getUserId());
							}
						}else{
							$providerUser = new ProviderUser();
							$providerUser->setProvider($this->provider);
						}
						
						$providerUser->setFromJSON($tokens);
						
						if($user === null){
							$user = new User();
							$user->setUsername($tokens['account_username']);
							
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
		return ($accessCode) ? $this->request($this->oauthURI . "/token/", [
				'client_id' => $this->clientID,
				'client_secret' => $this->secret,
				'grant_type' => 'authorization_code',
				'code' => $accessCode], true) : null;
	}
	
	/***
	 * Retrieves the user data and access tokens for the provided refreshToken
	 *
	 * @param string $refreshToken
	 * @return mixed|null
	 * @throws \Exception
	 */
	private function getRefreshTokens(string $refreshToken=''){
		return ($refreshToken) ? $this->request($this->oauthURI . "/token/", [
				'client_id' => $this->clientID,
				'client_secret' => $this->secret,
				'grant_type' => 'refresh_token',
				'refresh_token' => $refreshToken
		], true) : null;
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
		
		$headers = (empty($accessToken)) ? array('Authorization: CLIENT-ID ' . $this->clientID) : array("Authorization: Bearer " . $accessToken);
		if(!empty($this->mashapeAuth))  $headers[] = "X-Mashape-Authorization: ".$this->mashapeAuth;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 25);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, ($post) ? 'POST' : 'GET');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		if (!empty($options) && $post) curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
		
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
