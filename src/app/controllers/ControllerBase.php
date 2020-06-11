<?php
namespace controllers;

use models\Device;
use models\Session;
use models\User;

use Ubiquity\controllers\Controller;
use Ubiquity\exceptions\DAOException;
use Ubiquity\orm\DAO;
use Ubiquity\utils\http\UResponse;


/**
 * ControllerBase.
 **/
abstract class ControllerBase extends Controller {
	
	public function initialize() {
		UResponse::asJSON();
		
		/*  We want to allow both POST bodies and JSON bodies in a unified way
			so we check to see if the content type header is application/json
			and then we merge the JSON body with the $_POST variable
		*/
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] === 'application/json') {
			$request = json_decode(trim(file_get_contents("php://input")), true);
			$_POST = array_merge($_POST, $request);
		}
		
		$_REQUEST = [];
		
		$headers = getallheaders();
		$sessionId = $headers['session-id'] ?? null;
		$refreshToken = $headers['refresh-token'] ?? null;
		$deviceToken = $headers['token'] ?? null;
		
		if(!empty($sessionId)){
			try{
				list($user, $session, $device, $sessionRefreshed) = $this->loginWithSession($sessionId, $refreshToken, $deviceToken);
				if(!empty($session) && !empty($user)){
					if(!empty($device)){
						$_REQUEST['_device'] = $device;
					}
					$_REQUEST['_session'] = $session;
					$_REQUEST['_user'] = $user;
					$_REQUEST['_sessionRefreshed'] = $sessionRefreshed;
				}
			}catch(\Exception $e){
				$error = new \Error($e->getMessage(), $e->getCode());
			}
		}
		$_REQUEST['_headers'] = $headers;
	}
	
	public function finalize() {}
	
	public static function generateResponse(string $status='failure', $data=null, \Throwable $error=null){
		$response = ['status' => $status];
		
		if((!empty($data) && !empty($error)) || !empty($data))  $response['response'] = $data;
		if(!empty($error)){
			
			if(is_a($error, '\Throwable')){
				$error = ['message' => $error->getMessage(), 'code' => $error->getCode()];
			}
			
			$response['error'] = $error;
		}
		
		$sessionRefreshed = $_REQUEST['_sessionRefreshed'] ?? false;
		
		if($sessionRefreshed)   $response['session'] = $_REQUEST['_session']->getPublicOutput();
		
		return json_encode($response);
	}
	
	private function loginWithSession($sessionId='', $refreshToken='', $deviceToken=''){
		if(!empty($sessionId)){
			try{
				$sessionRefreshed = false;
				$criteria = [$sessionId, date('Y-m-j H:i')];
				$refreshTokenCheck = '';
				
				if(!empty($refreshToken)){
					$refreshTokenCheck = ' OR (id = ? AND refresh_token = ?)';
					$criteria[] = $sessionId;
					$criteria[] = $refreshToken;
				}
				
				$session = DAO::getOne(Session::class, '(id = ? AND expiry >= ?)'. $refreshTokenCheck, false, $criteria);
				if(!empty($session)){
					$device = null;
					
					if(!empty($session->getDeviceId())){
						$device = $session->getDevice();
						if(empty($device) || !$device->validateAndDecodeToken($deviceToken)){
							throw new \Exception('Session belongs to another device');
						}
					}
					
					$user = DAO::getById(User::class, $session->getUserId());
					if(!empty($user)){
						/** if a refresh token is passed, we should generate a new session even if the current one hasn't expired **/
						if($refreshToken === $session->getRefreshToken()){
							DAO::remove($session);
							$session = Session::generateNewSession($user->getId(), (!empty($device) ? $device->getId() : null), $session->getVerified(), );
							$sessionRefreshed = true;
						}
						else if(!$session->hasExpired()){
							if($session->shouldUpdateExpiry()){
								$session->generateAndSetExpiry();
								if (!DAO::save($session)) {
									throw new \Exception('Could not update session, a system error occurred');
								}
							}
						}
						return [$user, $session, $device, $sessionRefreshed];
					}else{
						throw new \Exception('Session invalid, user not found');
					}
				}else{
					throw new \Exception('Session invalid');
				}
			}catch (DAOException $exception){
				throw new \Exception('System error, please contact administrator', 4);
			}
		}
	}
	
	public function createSessionFromRequest($user, $request, $verifySession=false){
		
		$deviceName = $request['device_name'] ?? null;
		$devicePlatform = $request['device_platform'] ?? null;
		$devicePushService = $request['device_push_service'] ?? null;
		$devicePushServiceToken = $request['device_push_service_token'] ?? null;
		$secret = $request['device_secret'] ?? null;
		
		$device = null;
		if(empty($_REQUEST['_device'])){
			$device = Device::registerDevice($user->getId(), $secret, $deviceName, $devicePlatform, $devicePushService, $devicePushServiceToken);
		}else{
			$device = $_REQUEST['_device'];
			if($device->getUserId() !== $user->getId()){
				$device->setUserId($user->getId());
				DAO::save($device);
			}
		}
		
		$session = Session::generateNewSession($user->getId(), $device->getId(), $verifySession);
		
		$_REQUEST['_user'] = $user;
		$_REQUEST['_device'] = $device;
		$_REQUEST['_session'] = $session;
		
		return [$session, $device];
	}
}

