<?php
namespace controllers;

use Ubiquity\exceptions\DAOException;

/**
 * Preauth Controller
 **/
class Preauth extends ControllerBase{
	
	public function index(){return [];}
	
	/**
	 * @post("preauth/create")
	 */
	public function create(){
		
		$responseData = null;
		$status = 'failure';
		$error = new \Error('Please provide a device_name and device_secret', 1);
		
		$request = $_POST;
		
		$deviceName = $request['device_name'] ?? null;
		$devicePlatform = $request['device_platform'] ?? null;
		$deviceSecret = $request['device_secret'] ?? null;
			
		if(!empty($deviceName) && !empty($deviceSecret)){
			try{
				$preauth = \models\Preauth::registerPreauth($deviceSecret, $deviceName, $devicePlatform);
				$responseData = $preauth->getId();
				$status = 'success';
				$error = null;
				
				
			}catch (DAOException $exception){
				$error = new \Error('System error, please contact administrator', 3);
			}
		}
		
		echo $this::generateResponse($status, $responseData, $error);
	}
	
	

	
}
