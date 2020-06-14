<?php
namespace controllers\providers;

use Twig\Error\Error;
use Ubiquity\controllers\Startup;

/**
 * Twitter Provider
 **/
class Twitter extends PreauthControllerBase {

	protected $provider = 'twitter';
	private string $consumerKey = '';
	private string $secret = '';
	private string $accessTokenURI = '';
	private string $authenticateURI = '';
	private string $requestTokenURI = '';
	private string $redirectURI = '';
	
	
	public function __construct(){
		parent::__construct();
		$this->setupProviderConfig();
	}
	
	private function setupProviderConfig(){
		$providerConfig = Startup::$config['providers'][$this->provider];
		
		$this->consumerKey = $providerConfig['consumerKey'];
		$this->secret = $providerConfig['secret'];
		$this->accessTokenURI = $providerConfig['accessTokenURI'];
		$this->authenticateURI = $providerConfig['authenticateURI'];
		$this->requestTokenURI = $providerConfig['requestTokenURI'];
		$this->redirectURI = $providerConfig['redirectURI'];
	}
	
	public function index(){return [];}
	
	/**
	 * @get("twitter/login")
	 */
	public function login(){
		$_SESSION['for_login'] = true;
		$this->loginRegister();
	}
	
	/**
	 * @get("twitter/register")
	 */
	public function register(){
		$_SESSION['for_login'] = false;
		$this->loginRegister();
	}
	
	private function loginRegister(){
		$_SESSION['twitter'] = null;
		try{
			$this->setup();
			$token = $this->getRequestToken();
			
			if(!empty($token) && isset($token['oauth_token'])){
				$_SESSION['twitter'] = array('token' => [
						'key' => $token['oauth_token'],
						'secret' => $token['oauth_token_secret']
				]);
				
				header('Location: '. $this->authenticateURI.'?oauth_token='.$token['oauth_token']);
			}else{
				throw new Error(ucfirst($this->provider) . ' could not get OAuth Token');
			}
			
		}catch (\Exception $e){
			$this->handlePreauthResponse('failure', ['error' => $e->getMessage()]);
		}
	}
	
	/**
	 * @get("twitter/complete")
	 */
	public function complete(){
		$responseData = [];
		$error = new \Error('Invalid Request');
		
		if(isset($_GET['oauth_verifier']) && !empty($_GET['oauth_verifier'])){
			try{
				$tokens = $this->getAccessTokens($_GET['oauth_verifier']);
				if(!empty($tokens) && isset($tokens['oauth_token'])){
					try{
						$responseData = $this->completePreauth(
								$tokens['oauth_token'],
								$tokens['oauth_token_secret'] ?? null,
								null,
								null,
								$tokens['user_id'],
								$tokens['screen_name'],
								null
						);
						$error = null;
					}catch (\Exception $e){
						$error = $e;
					}
				}else{
					$error = new \Error(ucfirst($this->provider) . ' denied access');
				}
			}catch(\Exception $e){
				$error = new \Error($e->getMessage());
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
	private function getRequestToken(){
		$requestData = ['oauth_callback' => $this->redirectURI];
		
		$requestData = $this->signRequest('GET', $this->requestTokenURI, $requestData);
		
		return $this->request($this->requestTokenURI, $requestData);
	}
	
	/***
	 * Retrieves the user data and access tokens for the provided accessCode
	 *
	 * @param string $accessToken
	 * @return mixed|null
	 * @throws \Exception
	 */
	private function getAccessTokens(string $accessToken=''){
		
		$requestData = ['oauth_verifier' => $accessToken];
		$requestData = $this->signRequest('GET', $this->accessTokenURI, $requestData);
		return $this->request($this->accessTokenURI, $requestData);
	}
	
	private function signRequest($method="GET", $url="", $requestData=array()){
		
		$requestData = array_merge(
				$requestData,
				[
						'oauth_version' => '1.0',
						'oauth_nonce' => substr(bin2hex(random_bytes(32)), 0, 32),
						'oauth_timestamp' => time(),
						'oauth_consumer_key' => $this->consumerKey,
						'oauth_signature_method' => 'HMAC-SHA1'
				]
		);
		
		$signingKeyParts = [$this->secret];
		if(isset($_SESSION['twitter']) && is_array($_SESSION['twitter']['token'])){
			$token = $_SESSION['twitter']['token'];
			$requestData['oauth_token'] = $token['key'];
			$signingKeyParts[] = $token['secret'];
		}else{
			$signingKeyParts[] = '';
		}
		
		uksort($requestData, 'strcmp');
		
		$forBaseString = [];
		foreach($requestData as $key => $value){
			$forBaseString[] = rawurlencode($key) . "=" . rawurlencode($value);
		}
		
		$baseString = $method . "&" . rawurlencode($url) . "&" . rawurlencode(implode("&", $forBaseString));
		
		$signingKey = implode("&", $signingKeyParts);
		$signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
		
		$requestData['oauth_signature'] = $signature;
		
		return $requestData;
	}
	
	/***
	 * Fires a request to an endpoint and returns json_decoded data
	 *
	 * @param String $endpoint
	 * @param array $postData
	 * @param bool $post
	 * @param String $accessToken
	 * @return array
	 * @throws \Exception
	 */
	private function request(string $endpoint, array $postData=[], bool $post=false, string $accessToken=''){
		
		if(!empty($postData) && !$post){
			$getData = [];
			uksort($postData, 'strcmp');
			foreach($postData as $key => $value){
				$getData[] = urlencode($key) . "=" . urlencode($value);
			}
			$endpoint .= "?" . implode('&', $getData);
		}
		
		$ch = curl_init($endpoint);
		$curlOptions = $this->getRequestDefaults($postData, $post);
		$curlOptions[CURLOPT_HTTPHEADER] = array('Expect: ');
		
		curl_setopt_array($ch, $curlOptions);
		$data = curl_exec($ch);
		
		if (empty($data)) {
			throw new \Exception("Over capacity", 2);
		}else{
			$parts = explode("&", urldecode($data));
			$returnData = [];
			foreach($parts as $part){
				$part = explode("=", $part);
				if(!empty($part)){
					$returnData[$part[0]] = $part[1];
				}
				
			}
			
			if(empty($returnData)) throw new \Exception("Over capacity",1);
			return $returnData;
		}
	}
}
