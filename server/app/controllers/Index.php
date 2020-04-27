<?php
namespace controllers;

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
		$loggedIn = false;
		if(isset($_REQUEST['_user']) && !empty($_REQUEST['_user'])){
			$loggedIn = true;
		}
		echo $this::generateResponse('success', ['logged_in' => $loggedIn], null);
	}
}
