<?php
namespace controllers;

use models\User;
use Ubiquity\exceptions\DAOException;
use Ubiquity\orm\DAO;

/**
 * Registration Controller
 **/
class Registration extends ControllerBase{
	
	public function index(){return [];}
	
	/**
	 * @post("register")
	 */
	public function register(){
		$responseData = null;
		$status = 'failure';
		$error = new \Error('Unknown Error', 1);
		
		$request = $_POST;
		
		$username = $request['username'] ?? null;
		$email = $request['email'] ?? null;
		$password = $request['password'] ?? null;
		
		
		if(empty($username)){
			$error = new \Error('Please provide a username');
		}
		else if(empty($email)){
			$error = new \Error('Please provide an e-mail address');
		}
		else if(empty($password)){
			$error = new \Error('Please provide a password');
		}
		else{
			try{
				$emailHash = User::generateEmailHash($email);
				$existingUser = DAO::getOne(User::class, 'username = ? OR email_hash = ?', false, [$username, $emailHash]);
				
				if(!empty($existingUser)) {
					$error = new \Error('a user with that username or e-mail address already exists');
				}else{
					
					$user = new User();
					$user->setUsername($username);
					$user->hashPasswordAndSet($password);
					$user->setEmailHash($emailHash);
					
					if(DAO::save($user)){
						$responseData = ['user' => $user->getPublicOutput()];
						$status = 'success';
						$error = null;
						
						if(isset($request['login']) && $request['login'] == true){
							
							try{
								list($session, $device) = $this->createSession($user, $request);
								
								$responseData['session'] = $session->getPublicOutput();
								$responseData['device_id'] = $device->getId();
								
							}catch(\Exception $e){
								$status = 'failure';
								$error = new \Error($e->getMessage(), $e->getCode());
							}
						}
						
					}
				}
			}catch (DAOException $exception){
				$error = new \Error('Unknown Error', 2);
			}
		}
		
		echo $this::generateResponse($status, $responseData, $error);
	}
}
