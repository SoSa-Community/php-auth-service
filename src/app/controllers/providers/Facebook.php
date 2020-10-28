<?php
namespace controllers\providers;

use Ubiquity\controllers\Startup;

/**
 * Facebook Provider
 **/
class Facebook extends PreauthControllerBase {
	
	protected $provider = 'facebook';
	private $clientID = '';
	private $secret = '';

	private $apiURI = '';
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
		$this->oauthAuthorizeURI = $providerConfig['oauthAuthorizeURI'];
		$this->oauthTokenURI = $providerConfig['oauthTokenURI'];
		$this->redirectURI = $providerConfig['redirectURI'];
		$this->scopes = $providerConfig['scopes'];
	}
	
	public function index(){return [];}
	
	/**
	 * @get("facebook/login")
	 */
	public function login(){
		$_SESSION['for_login'] = true;
		$this->loginRegister();
	}

	/**
	 * @get("facebook/register")
	 */
	public function register(){
		$_SESSION['for_login'] = false;
		$this->loginRegister();
	}
	
	private function loginRegister(){
		try{
			
			$this->setup();
			header('Location: '. $this->oauthAuthorizeURI . '?client_id='.$this->clientID.'&redirect_uri='.$this->redirectURI.'&state='.$_SESSION['preAuth']->getId().'&scopes='.$this->scopes);
		}catch (\Exception $e){
			$this->handlePreauthResponse('failure', ['error' => $e->getMessage()]);
		}
	}
	
	/**
	 * @get("facebook/complete")
	 */
	public function complete(){
		$responseData = [];
		$error = new \APIError('Invalid Request');
		
		if(isset($_GET['code']) && !empty($_GET['code'])){
			if(isset($_GET['state']) && $_GET['state'] == $_SESSION['preAuth']->getId()){
				try{
					$tokens = $this->getAccessTokens($_GET['code']);
					if(!empty($tokens) && $tokens['access_token']){
						$userData = $this->getUser($tokens['access_token']);
						if(!empty($userData)){
							try{
								$responseData = $this->completePreauth(
										$tokens['access_token'],
										null,
										null,
										$tokens['expires_in'],
										$userData['id'],
										preg_replace('/[\s]+/i','',$userData['name']),
										$userData['email'] ?? null
								);
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
			}else{
				$error = new \APIError('CSRF Failed');
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
				'client_secret' => $this->secret,
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
		return ($accessToken) ? $this->request($this->apiURI . '/me?fields=id,name,email&access_token='.$accessToken) : null;
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
