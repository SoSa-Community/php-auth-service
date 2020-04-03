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
		$response = ['status' => 'failure', 'error' => 'unknown error'];
		
		$username = $_POST['username'] ?? null;
		$email = $_POST['email'] ?? null;
		$password = $_POST['password'] ?? null;
		
		
		if(empty($username)){
			$response['error'] = 'Please provide a username';
		}
		else if(empty($password)){
			$response['error'] = 'Please provide a password';
		}
		else if(empty($email)){
			$response['error'] = 'Please provide an e-mail address';
		}
		else{
			try{
				$emailHash = User::generateEmailHash($email);
				$existingUser = DAO::getOne(User::class, 'username = ? OR email_hash = ?', false, [$username, $emailHash]);
				
				if(!empty($existingUser)) {
					$response['error'] = 'a user with that username or e-mail address already exists';
				}else{
					
					$user = new User();
					$user->setUsername($username);
					$user->hashPasswordAndSet($password);
					$user->setEmailHash($emailHash);
					
					if(DAO::save($user)){
						unset($user->_rest['password']);
						unset($response['error']);
						
						$response['status'] = 'success';
						$response['user'] = $user->_rest;
						
					}
				}
			}catch (DAOException $exception){
			
			}
		}
		echo json_encode($response);
	}
}
