<?php
namespace controllers;

use Ubiquity\controllers\Controller;
use Ubiquity\utils\http\UResponse;


/**
 * ControllerBase.
 **/
abstract class ControllerBase extends Controller{
	public function initialize() {
		UResponse::asJSON();
	}
	public function finalize() {}
	
	public static function generateResponse(string $status='failure', $data=null, \Error $error=null){
		$response = ['status' => $status];
		
		if((!empty($data) && !empty($error)) || !empty($data))  $response['response'] = $data;
		if(!empty($error)){
			
			if(is_a($error, '\Error')){
				$error = ['message' => $error->getMessage(), 'code' => $error->getCode()];
			}
			
			$response['error'] = $error;
			
		}
		
		return json_encode($response);
	}
}

