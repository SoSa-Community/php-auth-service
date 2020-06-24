<?php
namespace controllers;

use models\PasswordReset;
use models\User;
use providers\EmailProvider;
use Ubiquity\exceptions\DAOException;
use Ubiquity\orm\DAO;

/**
 * Forgot Controller
 **/
class Forgot extends ControllerBase{
	
	public function index(){return [];}
	
	/**
	 * @post("forgot")
	 */
	public function forgot(){
		$responseData = null;
		$status = 'failure';
		$error = new \Error('Please provide an e-mail');
		
		$email = $_POST['email'] ?? null;
		
		if($email){
			try{
				$emailHash = User::generateEmailHash($email);
				$user = DAO::getOne(User::class, 'email_hash = ?', false, [$emailHash]);
				if(!empty($user)){
					
					$reset = DAO::getOne(PasswordReset::class, 'user_id = ?', false, [$user->getId()]);
					
					if(empty($reset)){
						$reset = new PasswordReset();
						$reset->setUserId($user->getId());
					}
					
					$reset->generateAndSetExpiry();
					$reset->generateAndSetToken();
					$reset->generateAndSetPin($emailHash);
				
					if(DAO::save($reset)){
						try{
							$username = $user->getUsername();
							$pinSplit = explode('-', $reset->getPin());
							
							$emailBody = EmailProvider::renderTemplate('forgot_password', ['username' => $username, 'pin' => $pinSplit[0]]);
							EmailProvider::send($email, $username, 'Did you forget something?', $emailBody);
							
							$status = 'success';
							$error = null;
						}catch (\Exception $e){
							error_log('Forgot Password e-mail went wrong', $e->getMessage());
							$error = new \Error('Problem sending you your pin, please contact the administrator');
						}
						
					}else{
						$error = new \Error('System error, please contact administrator');
					}
				}else{
					$error = new \Error('E-mail not found');
				}
			}catch (DAOException $exception){
				$error = new \Error('System error, please contact administrator');
			}
		}
		
		echo $this::generateResponse($status, $responseData, $error);
		
	}
	
	/**
	 * @get("forgot/validate")
	 */
	public function validate(){
		$responseData = null;
		$status = 'failure';
		$error = new \Error('Please provide a valid token');
		
		$pin = $_GET['pin'] ?? null;
		$email = $_GET['email'] ?? null;
		$expiryCheck = date('Y-m-j H:i', strtotime('-15 minutes'));
		$usingPin = false;
		
		if(!empty($pin)){
			if(!empty($email)) {
				$usingPin = true;
				$emailHash = User::generateEmailHash($email);
				$token = $pin . "-" . $emailHash;
			}
		}else{
			$token = $_GET['token'] ?? null;
		}
		
		if(!empty($token)){
			try{
				$sql = ($usingPin ? 'pin':'token') . ' = ? AND expiry >= ?';
				
				$reset = DAO::getOne(PasswordReset::class, $sql, false, [$token, $expiryCheck]);
				if(!empty($reset)){
					$reset->generateAndSetTransient();
					
					$ret = DAO::save($reset);
					if($ret){
						$error = null;
						$status = 'success';
						
						$responseData = array('transient' => $reset->getTransient());
						
						if($usingPin) $responseData['token'] = $reset->getToken();
					}else{
						error_log($ret);
						$error = new \Error('System error, please contact administrator');
					}
				}
			}catch (DAOException $exception){
				error_log($exception->getMessage());
				$error = new \Error('System error, please contact administrator');
			}
		}else{
			$error = new \Error('Please provide a valid token or pin');
		}
		echo $this::generateResponse($status, $responseData, $error);
	}
	
	/**
	 * @post("forgot/reset")
	 */
	public function reset(){
		$responseData = null;
		$status = 'failure';
		$error = new \Error('Unknown Error');
		
		$token = $_POST['token'] ?? null;
		$transient = $_POST['transient'] ?? null;
		$password = $_POST['password'] ?? null;
		
		if(empty($token)){
			$error = new \Error('Please provide a valid token');
		}elseif(empty($transient)){
			$error = new \Error('Please provide a valid transient');
		}
		else{
			if(!User::isPasswordValid($password)){
				$error = new \Error('Please provide a valid password');
			}else{
				try{
					$reset = DAO::getOne(PasswordReset::class, 'token = ? AND transient = ? AND expiry >= ?', false,
							[$token, $transient, date('Y-m-j H:i')]
					);
					
					if(!empty($reset)){
						$user = DAO::getOne(User::class, 'id = ?', false, [$reset->getUserId()]);
						if(!empty($user)){
							$user->hashPasswordAndSet($password);
							
							if(DAO::save($user)){
								$error = null;
								
								$responseData['username'] = $user->getUsername();
								
								$status = 'success';
								DAO::remove($reset);
							}
						}
					}else{
						$error = new \Error('Token expired');
					}
				}catch (DAOException $exception){
				
				}
			}
		}
		
		echo $this::generateResponse($status, null, $error);
	}
}
