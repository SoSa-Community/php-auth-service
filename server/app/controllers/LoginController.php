<?php
namespace controllers;

use models\User;
use Ubiquity\exceptions\DAOException;
use Ubiquity\orm\DAO;

/**
 * Controller LoginController
 **/
class LoginController extends ControllerBase{
	
	public function index(){return [];}
	
	/**
	 * @post("login", "methods" =>["post"])
	 */
	public function login(){
		$response = ['status' => 'failure', 'error' => 'invalid credentials'];
		
		$username = $_POST['username'] ?? null;
		$password = $_POST['password'] ?? null;
		
		if($username && $password){
			try{
				$user = DAO::getOne(User::class, 'username = ?', false, [$_POST['username']]);
				if(!empty($user)){
					
					if($user->verifyPassword($_POST['password'])){
						unset($user->_rest['password']);
						unset($response['error']);
						
						$response['user'] = $user->_rest;
						$response['status'] = 'success';
					}
				}
			}catch (DAOException $exception){
			
			}
		}
		echo json_encode($response);
	}
}
