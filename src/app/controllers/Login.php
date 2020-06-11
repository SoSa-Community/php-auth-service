<?php
namespace controllers;

use models\Device;
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
		
		$user = null;
		$userVerified = false;
		
		if((!empty($username) && !empty($password))){
			
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
					$error = new \Error('System error, please contact administrator', 3);
				}
			}
		}
		
		if($userVerified && !empty($user)){
			try{
				//
				list($session, $device) = $this->createSessionFromRequest($user, $request, true);
				
				$responseData = ['user' => $user->getPublicOutput(), 'session' => $session->getPublicOutput()];
				$responseData['device_id'] = $device->getId();
				$status = 'success';
				$error = null;
				
			}catch(\Exception $e){
				$error = new \Error($e->getMessage(), $e->getCode());
			}
		}
		
		echo $this::generateResponse($status, $responseData, $error);
	}
	
	

	
}
