<?php
namespace controllers;
use Blocktrail\CryptoJSAES\CryptoJSAES;
use Firebase\JWT\JWT;
use models\Device;
use models\Session;
use models\Bot;
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
				
				if(isset($tokenData['token'])){
					
					if(isset($tokenData['bot_id'])){
						$bot = DAO::getOne(Bot::class, 'unique_id = ?', false, [$tokenData['bot_id']]);
						
						if(!empty($bot)){
							try{
								$tokenData = $bot->validateAndDecodeToken($tokenData['token']);
								if(!empty($tokenData)){
									$user = $bot->getUser();
									if(!empty($user)){
										$session = Session::generateNewSession($user->getId(), $bot->getUniqueId(), true);
										$responseData = ['user' => $user->getPublicOutput(), 'session' => $session->getPublicOutput()];
										
										$error = null;
										$status = 'success';
									}
									
								}
							}catch(\Exception $e){
								$error = new \Error('Token corrupted', 4);
							}
							
						}
					}
					elseif(isset($tokenData['device_id'])){
						
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
			}else{
				$error = new \Error('Server to server connection broken');
			}
		}
		
		echo $this::generateResponse($status, $responseData, $error);
		
		
	}
	
	/**
	 * @get("generate_bots")
	 */
	
	public function generateBots(){
		for($x=1;$x<=250;$x++){
			
			$bot = new Bot();
			
			$bot->generateAndSetUniqueId();
			$bot->generateAndSetSecret();
			$bot->setOwnerId(1);
			$bot->setName('Bot'.$x);
			
			if(DAO::save($bot)){
				$user = new User();
				$user->setUsername('Bot'. $x);
				$user->setBotId($bot->getId());
				DAO::save($user);
			}
			
			
		}
	}
}
