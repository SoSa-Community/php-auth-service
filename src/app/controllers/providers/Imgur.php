<?php
namespace controllers\providers;

use Ubiquity\controllers\Startup;

/**
 * Imgur Provider
 **/
class Imgur extends PreauthControllerBase {

	protected $provider = 'imgur';
	private $clientID = '';
	private $secret = '';
	private $mashapeAuth = '';
	private $apiURI = '';
	private $oauthURI = '';
	
	
	public function __construct(){
		parent::__construct();
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
		$_SESSION['for_login'] = true;
		$this->loginRegister();
	}
	
	/**
	 * @get("imgur/register")
	 */
	public function register(){
		$_SESSION['for_login'] = false;
		$this->loginRegister();
	}
	
	private function loginRegister(){
		
		try{
			$this->setup();
			header('Location: '. $this->oauthURI.'/authorize/?client_id='.$this->clientID.'&response_type=code&state=initializing');
		}catch (\Exception $e){
			$this->handlePreauthResponse('failure', ['error' => $e->getMessage()]);
		}
	}
	
	/**
	 * @get("imgur/complete")
	 */
	public function complete(){
		$responseData = [];
		$error = new \Error('Invalid Request');
		
		if(isset($_GET['code']) && !empty($_GET['code'])){
			try{
				$tokens = $this->getAccessTokens($_GET['code']);
				if(!empty($tokens) && $tokens['access_token']){
					try{
						$responseData = $this->completePreauth($tokens['access_token'], '', $tokens['expires_in'], $tokens['account_id'], $tokens['account_username']);
						$error = null;
					}catch (\Exception $e){
						$error = $e;
					}
				}else{
					$error = new \Error('Could not get user data from '.ucfirst($this->provider));
				}
			}catch(\Exception $e){
				die($e->getCode() . ' - ' . $e->getMessage());
			}
		}
		
		if(!empty($error)){
			$this->handlePreauthResponse('failure', ['error' => $error->getMessage()]);
		}else{
			$this->handlePreauthResponse('success', $responseData);
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
	private function request(string $endpoint, array $postData=[], bool $post=false, string $accessToken=''){
		
		$headers = (empty($accessToken)) ? array('Authorization: CLIENT-ID ' . $this->clientID) : array("Authorization: Bearer " . $accessToken);
		if(!empty($this->mashapeAuth))  $headers[] = "X-Mashape-Authorization: ".$this->mashapeAuth;
		
		$ch = curl_init($endpoint);
		
		$curlOptions = $this->getRequestDefaults($postData, $post);
		$curlOptions[CURLOPT_HTTPHEADER] = $headers;
		
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
