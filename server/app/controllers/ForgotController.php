<?php
namespace controllers;

use models\PasswordReset;
use models\User;
use Ubiquity\exceptions\DAOException;
use Ubiquity\orm\DAO;

/**
 * Controller ForgotController
 **/
class ForgotController extends ControllerBase{
	
	public function index(){return [];}
	
	/**
	 * @post("forgot")
	 */
	public function forgot(){
		$response = ['status' => 'failure', 'error' => 'unknown error'];
		
		$email = $_POST['email'] ?? null;
		
		if($email){
			try{
				$user = DAO::getOne(User::class, 'email_hash = ?', false, [md5($email)]);
				if(!empty($user)){
					
					$reset = DAO::getOne(PasswordReset::class, 'user_id = ?', false, [$user->getId()]);
					
					if(empty($reset)){
						$reset = new PasswordReset();
						$reset->setUserId($user->getId());
					}
					
					$reset->generateExpiry();
					$reset->generateToken();
					
					if(DAO::save($reset)){
						unset($response['error']);
						$response['status'] = 'success';
					}
				}
			}catch (DAOException $exception){
			
			}
		}
		echo json_encode($response);
	}
	
	/**
	 * @get("forgot/validate")
	 */
	public function validate(){
		$response = ['status' => 'failure', 'error' => 'unknown error'];
		if($token = $_GET['token'] ?? null){
			try{
				$reset = DAO::getOne(PasswordReset::class, 'token = ? AND expiry >= ?', false, [$token, date('Y-m-j H:i',strtotime('-15 minutes'))]);
				if(!empty($reset)){
					$reset->generateTransient();
					
					if(DAO::save($reset)){
						unset($response['error']);
						$response['status'] = 'success';
						$response['transient'] = $reset->getTransient();
					}
				}
			}catch (DAOException $exception){
			
			}
		}
		echo json_encode($response);
	}
	
	/**
	 * @post("forgot/reset")
	 */
	public function reset(){
		$response = ['status' => 'failure', 'error' => 'unknown error'];
		
		$token = $_POST['token'] ?? null;
		$transient = $_POST['transient'] ?? null;
		$password = $_POST['password'] ?? null;
		
		if($token && $transient && $password){
			try{
				$reset = DAO::getOne(PasswordReset::class, 'token = ? AND transient = ? AND expiry >= ?', false,
						[$token, $transient, date('Y-m-j H:i',strtotime('-15 minutes'))]
				);
				if(!empty($reset)){
					$user = DAO::getOne(User::class, 'id = ?', false, [$reset->getUserId()]);
					if(!empty($user)){
						$user->hashPasswordAndSet($password);
						
						if(DAO::save($user)){
							unset($response['error']);
							$response['status'] = 'success';
						}
					}
				}
			}catch (DAOException $exception){
			
			}
		}else{
			$response['error'] = 'Please provide a token, transient and password';
		}
		
		echo json_encode($response);
	}
}
