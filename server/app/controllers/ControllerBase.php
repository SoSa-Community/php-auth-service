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
}

