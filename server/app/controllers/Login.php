<?php
namespace controllers;

use models\User;
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
		$error = new \Error('Incorrect username or password',1);
		
		if($_SERVER['CONTENT_TYPE'] === 'application/json'){
			$request = json_decode(trim(file_get_contents("php://input")), true);
		}else{
			$request = $_POST;
		}
		
		$username = $request['username'] ?? null;
		$password = $request['password'] ?? null;
		
		if(empty($username) || empty($password)){
			$error = new \Error('Username or password must not be empty',2);
		}else{
			try{
				$user = DAO::getOne(User::class, 'username = ?', false, [$username]);
				if(!empty($user)){
					
					if($user->verifyPassword($password)){
						$status = 'success';
						$error = null;
						$responseData = ['user' => $user->getPublicOutput()];
					}
				}
			}catch (DAOException $exception){
			
			}
		}
		echo $this::generateResponse($status, $responseData, $error);
	}
}
