<?php
namespace controllers\providers;

use models\Device;
use models\Preauth;
use models\ProviderUser;

use models\User;
use Ubiquity\orm\DAO;



/**
 * ControllerBase.
 **/
abstract class PreauthControllerBase extends \Ubiquity\controllers\ControllerBase {
	
	protected $provider = '';
	
	public function loginSetup(){
		$_SESSION['app'] = ($_GET['app'] ? true : false);
		if(!empty($_GET['preauth'])){
			$preauth = DAO::getOne(Preauth::class, 'id = ?', false, [$_GET['preauth']]);
			if(!empty($preauth)){
				$_SESSION['preAuth'] = $preauth;
				return true;
			}else{
				throw new \Error('Invalid Pre-auth provided');
			}
		}else{
			throw new \Error('Invalid Pre-auth provided');
		}
	}
	
	public function completePreauth($accessToken, $refreshToken, $expiresIn, $userId, $username){
		
		if(!empty($_SESSION['preAuth'])) {
			
			$providerUser = DAO::getOne(ProviderUser::class, 'provider = ? AND unique_id = ?', false, [$this->provider, $userId]);
			$user = null;
			
			if (!empty($providerUser)) {
				if (!empty($providerUser->getUserId())) {
					$user = DAO::getById(User::class, $providerUser->getUserId());
				}
			} else {
				$providerUser = new ProviderUser();
				$providerUser->setProvider($this->provider);
			}
			
			$providerUser->setUniqueId($userId);
			$providerUser->setAccessToken($accessToken);
			$providerUser->setAccessTokenExpiry(date('Y-m-j H:i', time() + intval($expiresIn)));
			
			
			if ($user === null) {
				$user = new User();
				
				$existingUser =  DAO::getOne(User::class, 'username = ?', false, [$username]);
				if(!empty($existingUser)) $username = null;
				$user->setUsername($username);
				
				if (!DAO::save($user)) {
					throw new \Exception('Failed to save user account');
				}
			}
			
			$providerUser->setUserId($user->getId());
			if (DAO::save($providerUser)) {
				$preAuth = $_SESSION['preAuth'];
				$device = Device::registerDevice($user->getId(), $preAuth->getDeviceSecret(), $preAuth->getDeviceName(), $preAuth->getDevicePlatform());
				return ['device_id' => $device->getId()];
			} else {
				throw new \Exception('Error saving user');
			}
		}else{
			throw new \Exception('Invalid Request');
		}
		
	}
	
	public function handlePreauthResponse($status='failure', $returnData = []){
		$returnData['status'] = $status;
		
		if($_SESSION['app']){
			header('Location: sosa://login/preauth/'.base64_encode(json_encode($returnData)));
		}else{
			header('Location: https://sosa.net/preauth/'.$status.'/'.implode('/', $returnData));
		}
	}
	
	public function getRequestDefaults($postData, $isPost){
		$curlOptions = [];
		
		$curlOptions[CURLOPT_CONNECTTIMEOUT] = 60;
		$curlOptions[CURLOPT_DNS_CACHE_TIMEOUT] = 25;
		$curlOptions[CURLOPT_TIMEOUT] = 60;
		
		$curlOptions[CURLOPT_RETURNTRANSFER] = 1;
		$curlOptions[CURLOPT_FOLLOWLOCATION] = 1;
		$curlOptions[CURLOPT_USERAGENT] = $_SERVER['HTTP_USER_AGENT'];
		
		$curlOptions[CURLOPT_HEADER] = 0;
		$curlOptions[CURLINFO_HEADER_OUT] = 0;
		
		if (!empty($postData) && $isPost){
			$curlOptions[CURLOPT_CUSTOMREQUEST] = 'POST';
			$curlOptions[CURLOPT_POSTFIELDS] = $postData;
		}
		
		return $curlOptions;
	}
	
	

}

