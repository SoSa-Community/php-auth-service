<?php
namespace controllers\providers;

use Ubiquity\controllers\Startup;

/**
 * Google Provider
 **/
class Google extends PreauthControllerBase {

	protected $provider = 'google';
	private $clientID = '';
	private $secret = '';
	private $oauthURI = '';
	private $oauthTokenURI = '';
	private $userInfoURI = '';
	private $redirectURI = '';
	private $scopes = [];
	
	public function __construct(){
		parent::__construct();
		$this->setupProviderConfig();
	}
	
	private function setupProviderConfig(){
		$providerConfig = Startup::$config['providers'][$this->provider];
		
		$this->clientID = $providerConfig['clientID'];
		$this->secret = $providerConfig['secret'];
		$this->oauthURI = $providerConfig['oauthURI'];
		$this->oauthTokenURI = $providerConfig['oauthTokenURI'];
		$this->userInfoURI = $providerConfig['userInfoURI'];
		$this->redirectURI = $providerConfig['redirectURI'];
		$this->scopes = $providerConfig['scopes'];
	}
	
	public function index(){return [];}
	
	/**
	 * @get("google/login")
	 */
	public function login(){
		$_SESSION['for_login'] = true;
		$this->loginRegister();
	}
	
	/**
	 * @get("google/register")
	 */
	public function register(){
		$_SESSION['for_login'] = false;
		$this->loginRegister();
	}
	
	private function loginRegister(){
		
		try{
			$this->setup();
			
			$scopes = array_map(function($value){
				return $value;
			}, $this->scopes);
			
			header('Location: '. $this->oauthURI.'?client_id='.$this->clientID.'&redirect_uri='.$this->redirectURI.'&scope='.implode(' ',$scopes).'&state='.rand().'&response_type=code&access_type=offline');
		}catch (\Exception $e){
			$this->handlePreauthResponse('failure', ['error' => $e->getMessage()]);
		}
	}
	
	/**
	 * @get("google/complete")
	 */
	public function complete(){
		$responseData = [];
		$error = new \APIError('Invalid Request');
		
		if(isset($_GET['code']) && !empty($_GET['code'])){
			try{
				$tokens = $this->getAccessTokens($_GET['code']);
				
				if(!empty($tokens) && $tokens['access_token']){
					$userData = $this->getUser($tokens['access_token']);
					if(!empty($userData)){
						try{
							$verifiedEmail = $userData['verified_email'] ?? false;
							$email = null;
							
							if($verifiedEmail && $userData['email'])    $email = $userData['email'];
							
							$username = trim(preg_replace('/[^A-Za-z0-9\-]+/ism', '-', $userData['name']));
							if(empty($username)){
								$username = "guser-".$userData['id'];
							}
							
							$responseData = $this->completePreauth($tokens['access_token'], null, $tokens['refresh_token'] ?? null, $tokens['expires_in'], $userData['id'], $username, $email);
							$error = null;
						}catch (\Exception $e){
							$error = $e;
						}
					}else{
						$error = new \APIError('Could not get user data from '.ucfirst($this->provider));
					}
				}else{
					$error = new \APIError(ucfirst($this->provider) . ' denied access');
				}
			}catch(\Exception $e){
				$error = new \APIError($e->getMessage());
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
		return ($accessCode) ? $this->request($this->oauthTokenURI, [
				'client_id' => $this->clientID,
				'client_secret' => $this->secret,
				'grant_type' => 'authorization_code',
				'redirect_uri' => $this->redirectURI,
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
		return ($accessToken) ? $this->request($this->userInfoURI, [], false, $accessToken) : null;
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
		
		$ch = curl_init($endpoint);
		$curlOptions = $this->getRequestDefaults($postData, $post);
		
		if(!empty($accessToken)){
			$curlOptions[CURLOPT_HTTPHEADER] = array("Authorization: Bearer " . $accessToken);
			$curlOptions[CURLOPT_HEADER] = false;
			$curlOptions[CURLINFO_HEADER_OUT] = false;
		}else{
			$curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
			$curlOptions[CURLOPT_USERPWD] = $this->clientID . ":" . $this->secret;
		}
		
		curl_setopt_array($ch, $curlOptions);
		
		$data = curl_exec($ch);
		
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
