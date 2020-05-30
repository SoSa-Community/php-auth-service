<?php
namespace controllers;
use Blocktrail\CryptoJSAES\CryptoJSAES;
use Firebase\JWT\JWT;
use models\Device;
use models\Session;
use models\User;
use Ubiquity\orm\DAO;


/**
 * Index Controller
 **/
class Index extends ControllerBase{
	
	/**
	 * @get("test/secure")
	 */
	public function index(){
		$loggedIn = false;
		if(isset($_REQUEST['_user']) && !empty($_REQUEST['_user'])){
			$loggedIn = true;
		}
		echo $this::generateResponse('success', ['logged_in' => $loggedIn], null);
	}
	
	/**
	 * @get("validate")
	 */
	public function validate(){
		$response = ['logged_in' => false];
		if(isset($_REQUEST['_user']) && !empty($_REQUEST['_user'])){
			$response['logged_in'] = true;
			$response['user'] = $_REQUEST['_user']->getPublicOutput();
			if($_REQUEST['_sessionRefreshed']){
				$response['session'] = $response['_session'];
			}
			echo $this::generateResponse('success', $response, null);
		}else{
			echo $this::generateResponse('failure', $response, new \Error('Session invalid'));
		}
		
	}
	
	/**
	 * @post("authenticate")
	 */
	public function authenticate(){
		$responseData = null;
		$status = 'failure';
		$error = new \Error('Invalid Token', 1);
		
		$request = $_POST;
		$token = $request['token'] ?? null;
		
		if(!empty($token)){
			$decrypted = CryptoJSAES::decrypt($token, 'sausage');
			if(!empty($decrypted)){
				$tokenData = json_decode($decrypted, true);
				
				if(isset($tokenData['device_id']) && isset($tokenData['token'])){
					$device = DAO::getOne(Device::class, 'id = ?', false, [$tokenData['device_id']]);
					
					if(!empty($device)){
					
						try{
							$token = JWT::decode($tokenData['token'], $device->getSecret(), array('HS256'));
							
							$session = DAO::getOne(Session::class, 'id=? AND device_id=?', false, [$token->id, $device->getId()]);
							if(!empty($session)){
								if(!$session->hasExpired()){
									
									$user = DAO::getOne(User::class, 'id = ?', false, [$session->getUserId()]);
									if(!empty($user)) {
										$error = null;
										$status = 'success';
										$responseData = ['user' => $user->getPublicOutput(), 'session' => $session->getPublicOutput()];
									}
								}
							}
						}catch (\Exception $e){
						
						}
					}
				}else{
					$error = new \Error('Server to server connection broken');
				}
			}else{
				$error = new \Error('Server to server connection broken');
			}
		}
		
		echo $this::generateResponse($status, $responseData, $error);
		
		
	}
}
