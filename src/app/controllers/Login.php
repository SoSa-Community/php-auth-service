<?php
namespace controllers;

use controllers\providers\PreauthControllerBase;
use models\Device;
use models\ProviderUser;
use models\User;
use models\Session;
use models\Preauth;

use Ubiquity\exceptions\DAOException;
use Ubiquity\orm\DAO;


/**
 * Login Controller
 **/
class Login extends ControllerBase{
	
	public function index(){return [];}
	
	/**
	 * @post("login")
	 */
	public function login(){
		
		$responseData = null;
		$status = 'failure';
		$error = new \APIError('Incorrect username or password', 1);
		
		$request = $_POST;
		
		$username = $request['username'] ?? null;
		$password = $request['password'] ?? null;
		
		$user = null;
		$userVerified = false;
		
		if((!empty($username) && !empty($password))){
			
			if(empty($username) || empty($password)){
				$error = new \APIError('Username or password must not be empty',2);
			}else{
				try{
					$criteria = [$username];
					
					$checkEmail = '';
					if(preg_match('/@/',$username)){
						$checkEmail = ' OR email_hash = ?';
						$criteria[] = User::generateEmailHash($username);
					}
					
					$user = DAO::getOne(User::class, 'username = ?'.$checkEmail, false, $criteria);
					if(!empty($user)){
						if($user->verifyPassword($password))    $userVerified = true;
					}
				}catch (DAOException $exception){
					$error = new \APIError('System error, please contact administrator', 3);
				}
			}
		}
		
		if($userVerified && !empty($user)){
			try{
				list($session, $device) = $this->createSessionFromRequest($user, $request, true);
				
				$responseData = ['user' => $user->getPublicOutput(), 'session' => $session->getPublicOutput()];
				
				$responseData['device_id'] = $device->getId();
				$status = 'success';
				$error = null;
				
			}catch(\Exception $e){
				$error = new \APIError($e->getMessage(), $e->getCode());
			}
		}
		
		echo $this::generateResponse($status, $responseData, $error);
	}
	
	/**
	 * @post("login/welcome")
	 */
	public function welcome(){
		
		$responseData = null;
		$status = 'failure';
		$errors = [];
		
		$request = $_POST;
		
		$username = $request['username'] ?? null;
		$email = $request['email'] ?? null;
		
		if(isset($_REQUEST['_user']) && !empty($_REQUEST['_user'])){
			$existingUser = $_REQUEST['_user'];
			
			$emailRequired = empty($existingUser->getEmailHash());
			$usernameRequired = empty($existingUser->getUsername());
			
			$emailHash = null;
			if(!empty($email)) $emailHash = User::generateEmailHash($email);
			
			if($existingUser->getUsername() === $username && (!$emailRequired || ($emailHash !== null && $emailHash == $existingUser->getEmailHash()))){
				$existingUser->setWelcome(true);
				if(DAO::save($existingUser)) {
					$errors = null;
					$responseData = ['user' => $existingUser->getPublicOutput()];
				}
			}else {
				
				$usernameValid = false;
				$emailIsValid = false;
				
				try{
					$usernameValid = User::isUsernameValid($username);
				}catch (\Exception $exception){
					$errors[] = new \APIError($exception->getMessage(), 0, 'username');
				}
				
				try {
					$emailIsValid = (!$emailRequired || User::isEmailValid($email));
				} catch (\Exception $exception) {
					$errors[] = new \APIError($exception->getMessage(), 0, 'email');
				}
				
				if ($emailIsValid && empty($errors)) {
					
					$userExistErrors = User::checkUsersExist($username, ($emailRequired ? $emailHash : null), $existingUser);
					if(!empty($userExistErrors)){
						$errors = array_merge($userExistErrors, $errors);
					}else{
						$existingUser->setWelcome(true);
						
						if (!empty($username)) {
							$existingUser->setUsername($username);
						}
						if ($emailRequired && !empty($emailHash)) {
							$existingUser->setEmailHash($emailHash);
						}
						
						if(DAO::save($existingUser)){
							$_REQUEST['_user'] = $existingUser;
							$errors = null;
							$responseData = ['user' => $existingUser->getPublicOutput()];
						}
					};
				}
			}
		}else{
			$errors = [new \APIError('You need to be logged in to perform this action', 1)];
		}
		echo $this::generateResponse($status, $responseData, $errors);
	}
	
	/**
	 * @post("login/link")
	 */
	public function link(){
		$responseData = null;
		$status = 'failure';
		$error = new \APIError('system_error');;
		
		$request = $_POST;
		
		$preauthId = $request['preauth_id'] ?? null;
		$token = $request['token'] ?? null;
		
		if(empty($preauthId)){
			$error = new \APIError('invalid_preauth_id');
		}else if(empty($token)){
			$error = new \APIError('invalid_token');
		}
		else{
			try{
				$preAuth = DAO::getOne(Preauth::class, 'id = ?', false, [$preauthId]);
				if(empty($preAuth)){
					$error = new \APIError('invalid_preauth_id');
				}else{
					try{
						$tokenData = $preAuth->validateAndDecodeToken($token);
						if(!empty($tokenData)){
							
							$providerUser = DAO::getOne(ProviderUser::class, 'preauth_id = ? AND link_token = ?', false, [$preauthId, $tokenData->link_token]);
							if(empty($providerUser)){
								$error = new \APIError('invalid_link_token');
							}else{
								$user = PreauthControllerBase::createPreauthUser($providerUser->getUsername(), $providerUser->getEmail());
								if(!empty($user)){
									$providerUser->setLinkToken(null);
									$providerUser->setPreauthId(null);
									$providerUser->setUsername(null);
									$providerUser->setEmail(null);
									$providerUser->setUserId($user->getId());
									if (DAO::save($providerUser)) {
										$status = 'success';
										$error = null;
										
										$device = Device::registerDevice($user->getId(), $preAuth->getDeviceSecret(), $preAuth->getDeviceName(), $preAuth->getDevicePlatform());
										$session = Session::generateNewSession($user->getId(), $device->getId(), true);
										if(empty($device) ||  empty($session)) {
											error_log('Preauth::Link device or session empty');
										}else{
											$responseData = ['device_id' => $device->getId(), 'user' => $user->getPublicOutput(), 'session' => $session->getPublicOutput()];
										}
									}else{
										error_log('Preauth::Link Unable to save provider user');
									}
								}else{
									error_log('Preauth::Link Unable to create user');
								}
							}
						}
					}catch (\Exception $e){
						$error = new \APIError($e->getMessage());
						error_log('Preauth::Link '.$e->getMessage());
					}
				}
			}catch (\Exception $e){
				$error = new \APIError($e->getMessage());
				error_log('Preauth::Link '.$e->getMessage());
			}
		}
		echo $this::generateResponse($status, $responseData, $error);
	}
}
