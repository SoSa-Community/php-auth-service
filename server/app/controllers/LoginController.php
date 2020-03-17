<?php
namespace controllers;

/**
 * Controller LoginController
 **/
class LoginController extends ControllerBase{
	
	/**
	 * @route("login")
	 */
	public function index(){
		return $this->login();
	}
	
	private function login(){
		$response = [];
		
		echo json_encode($response);
	}

}
