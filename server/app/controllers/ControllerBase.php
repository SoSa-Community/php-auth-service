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
		
		/*  We want to allow both POST bodies and JSON bodies in a unified way
			so we check to see if the content type header is application/json
			and then we merge the JSON body with the $_POST variable
		*/
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] === 'application/json') {
		$request = json_decode(trim(file_get_contents("php://input")), true);
			$_POST = array_merge($_POST, $request);
		}
		
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
		
		return json_encode($response);
	}
}

