<?php
namespace controllers;

use models\User;
use providers\WhoisProvider;
use Ubiquity\controllers\Startup;
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
		
		$username = trim($request['username']) ?? null;
		$email = trim($request['email']) ?? null;
		$password = trim($request['password']) ?? null;
		$usernameLength = !empty($username) ? strlen($username) : 0;
		
		if($usernameLength < Startup::$config['usernameMinLength'] || $usernameLength > Startup::$config['usernameMaxLength']){
			$error = new \Error('Username must be between '.Startup::$config['usernameMinLength'].' and '. Startup::$config['usernameMaxLength'] . ' characters');
		}
		else if(empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)){
			$error = new \Error('Please provide an e-mail address or register using a social account');
		}
		else if(empty($password) || strlen($password) < Startup::$config['passwordMinLength']){
			$error = new \Error('Password must be at least '.Startup::$config['passwordMinLength'].' characters long');
		}
		else if(preg_replace(Startup::$config['usernameReplaceRegex'],'', $username) !== $username){
			$error = new \Error('Username can only contain the characters a-z, 0-9, "-" and "_"');
		}
		else{
			$emailSplit = explode('@', $email);
			$emailWhois = WhoisProvider::retrieve($emailSplit[1]);
			
			if(empty($emailWhois) || !$emailWhois->getExists()){
				$error = new \Error('The e-mail you provided is invalid');
			}else{
				try{
					$emailHash = User::generateEmailHash($email);
					$existingUser = DAO::getOne(User::class, 'username LIKE ? OR email_hash LIKE ?', false, [$username, $emailHash]);
					
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
									list($session, $device) = $this->createSessionFromRequest($user, $request, true);
									
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
		}
		
		echo $this::generateResponse($status, $responseData, $error);
	}
}
