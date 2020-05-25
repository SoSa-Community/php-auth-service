<?php
namespace controllers;

use models\Device;
use models\Session;
use models\User;
use Ubiquity\controllers\Startup;
use Ubiquity\exceptions\DAOException;
use Ubiquity\orm\DAO;

/**
 * Devices Controller
 **/
class Devices extends ControllerBase{
	
	public function index(){return [];}
	
	/**
	 * @post("device/login")
	 */
	public function login(){
		
		$responseData = null;
		$status = 'failure';
		$error = new \Error('Invalid token', 1);
		
		$request = $_POST;
		
		$token = $request['token'] ?? null;
		$deviceId = $request['device_id'] ?? null;
		
		if(empty($deviceId)){
			$error = new \Error('Please provide a device_id', 2);
		}elseif(empty($token)){
			$error = new \Error('Please provide a token', 3);
		}
		else{
			$device = DAO::getOne(Device::class, 'id = ?', false, [$deviceId]);
			if(!empty($device)){
				try{
					$tokenData = $device->validateAndDecodeToken($token);
					if(!empty($tokenData)){
						$user = DAO::getById(User::class, $device->getUserId());
						if(!empty($user)){
							$error = null;
							
							$session = Session::generateNewSession($user->getId(), $deviceId, true);
							
							$status = 'success';
							$responseData = ['user' => $user->getPublicOutput(), 'session' => $session->getPublicOutput()];
						}
					}
				}catch(\Exception $e){
					$error = new \Error('Token corrupted', 4);
				}
				
			}
		}
		
		echo $this::generateResponse($status, $responseData, $error);
	}
}
