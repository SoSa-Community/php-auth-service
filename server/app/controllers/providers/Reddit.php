<?php
namespace controllers\providers;

use Ubiquity\controllers\Startup;

/**
 * Reddit Provider
 **/
class Reddit extends PreauthControllerBase {
	
	protected $provider = 'reddit';
	private $clientID = '';
	private $secret = '';

	private $apiURI = '';
	private $oauthURI = '';
	private $oauthAuthorizeURI = '';
	private $oauthTokenURI = '';
	private $redirectURI = '';
	
	private $scopes = '';
	
	public function __construct(){
		parent::__construct();
		
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
		try{
			$this->loginSetup();
			header('Location: '. $this->oauthAuthorizeURI . '?response_type=code&client_id='.$this->clientID.'&redirect_uri='.$this->redirectURI.'&scope='.$this->scopes.'&state='.rand());
		}catch (\Exception $e){
			$this->handlePreauthResponse('failure', ['error' => $e->getMessage()]);
		}
	}
	
	/**
	 * @get("reddit/complete")
	 */
	public function complete(){
		$responseData = [];
		$error = new \Error('Invalid Request');
		
		if(isset($_GET['code']) && !empty($_GET['code'])){
			try{
				$tokens = $this->getAccessTokens($_GET['code']);
				if(!empty($tokens) && $tokens['access_token']){
					$userData = $this->getUser($tokens['access_token']);
					if(!empty($userData)){
						try{
							$responseData = $this->completePreauth($tokens['access_token'], '', $tokens['expires_in'], $userData['id'], $userData['name']);
							$error = null;
						}catch (\Exception $e){
							$error = $e;
						}
					}else{
						$error = new \Error('Could not get user data from '.ucfirst($this->provider));
					}
				}else{
					$error = new \Error(ucfirst($this->provider) . ' denied access');
				}
			}catch(\Exception $e){
				$error = new \Error('Something went wrong, please contact the administrator');
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
	 * @param array $postData
	 * @param bool $post
	 * @param String $accessToken
	 * @return array
	 * @throws \Exception
	 */
	private function request(string $endpoint, array $postData=[], bool $post=false, string $accessToken=''){
		
		$headers = [];
		
		$ch = curl_init($endpoint);
		
		$curlOptions = $this->getRequestDefaults($postData, $post);
		
		if(!empty($accessToken)){
			$curlOptions[CURLOPT_HTTPHEADER] = "Authorization: bearer " . $accessToken;
		}else{
			$curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
			$curlOptions[CURLOPT_USERPWD] = $this->clientID . ":" . $this->secret;
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
