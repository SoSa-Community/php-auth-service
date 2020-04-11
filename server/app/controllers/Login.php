<?php
namespace controllers;

use models\Session;
use models\User;
use Ubiquity\controllers\Startup;
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
		$error = new \Error('Incorrect username or password', 1);
		
		$request = $_POST;
		
		$username = $request['username'] ?? null;
		$password = $request['password'] ?? null;
		
		$sessionId = $request['session_id'] ?? null;
		$refreshToken = $request['refresh_token'] ?? null;
		
		$deviceID = $request['device_id'] ?? null;
		
		
		$user = null;
		$userVerified = false;
		
		if(!empty($sessionId)){
			try{
				list($user, $session) = $this->loginWithSession($sessionId, $refreshToken);
				if(!empty($user)){
					$userVerified = true;
				}
			}catch(\Error $e){
				$error = $e;
			}
		}
		
		
		if(empty($sessionId) || (!empty($username) && !empty($password))){
			
			if(empty($username) || empty($password)){
				$error = new \Error('Username or password must not be empty',2);
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
					$error = new \Error('System error, please contact administrator',3);
				}
			}
		}
		
		if($userVerified && !empty($user)){
			$status = 'success';
			$error = null;
			if(!isset($session) || empty($session)){
				$session = Session::generateNewSession($user->getId());
			}
			$responseData = ['user' => $user->getPublicOutput(), 'session' => $session->getPublicOutput()];
		}
		
		echo $this::generateResponse($status, $responseData, $error);
	}
	
	private function loginWithSession($sessionId='', $refreshToken=''){
		if(!empty($sessionId)){
			try{
				$criteria = [$sessionId, date('Y-m-j H:i', strtotime('-'.intval(Startup::$config['sessionTimeout']).' minutes'))];
				$refreshTokenCheck = '';
				
				if(!empty($refreshToken)){
					$refreshTokenCheck = ' OR (id = ? AND refresh_token = ?)';
					$criteria[] = $sessionId;
					$criteria[] = $refreshToken;
				}
				
				$session = DAO::getOne(Session::class, '(id = ? AND expiry >= ?)'. $refreshTokenCheck, false, $criteria);
				if(!empty($session)){
					$user = DAO::getById(User::class, $session->getUserId());
					if(!empty($user)){
						/** if a refresh token is passed, we should generate a new session even if the current one hasn't expired **/
						if($refreshToken === $session->getRefreshToken()){
							DAO::remove($session);
							Session::generateNewSession($user->getId());
						}
						else if(!$session->hasExpired()){
							$session->generateAndSetExpiry();
							if(!DAO::save($session)){
								throw new \Error('Could not update session, a system error occurred');
							}
						}
						return [$user, $session];
					}else{
						throw new \Error('Session invalid, user not found');
					}
				}else{
					throw new \Error('Session invalid');
				}
			}catch (DAOException $exception){
				throw new \Error('System error, please contact administrator', 4);
			}
		}
	}
}
