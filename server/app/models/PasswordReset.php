<?php

namespace models;

use Ubiquity\controllers\Startup;

/**
 * @table("name"=>"password_reset")
 **/
class PasswordReset{
	/**
	 * @id
	 * @column("name"=>"user_id")
	 */
	private $userId = 0;
	
	private $token = '';
	private $pin = '';
	private $expiry = '';
	private $transient = '';
	
	private static $tokenSize = 255;
	private static $pinSize = 45;
	private static $transientSize = 100;
	
	public function getUserId(){return $this->userId;}
	public function setUserId($userId){$this->userId = $userId;}
	
	public function getToken(){return $this->token;}
	public function setToken($token){$this->token = $token;}
	
	public function getPin(){return $this->pin;}
	public function setPin($pin){$this->pin = $pin;}
	
	public function getExpiry(){return $this->expiry;}
	public function setExpiry($expiry){$this->expiry = $expiry;}
	
	public function getTransient(){return $this->transient;}
	public function setTransient($transient){$this->transient = $transient;}
	
	public static function generateToken(){
		return substr(bin2hex(random_bytes(256)), 0, self::$tokenSize);
	}
	
	public function generateAndSetToken(){
		$this->setToken($this::generateToken());
	}
	
	public static function generatePin($emailHash=''){
		return substr(strtoupper(bin2hex(random_bytes(8))),0, self::$pinSize) . "-" . $emailHash;
	}

	public function generateAndSetPin($emailHash=''){
		return $this->setPin($this::generatePin($emailHash));
	}
	
	public static function generateTransient(){
		return substr(bin2hex(random_bytes(64)), 0, self::$transientSize);
	}
	
	public function generateAndSetTransient(){
		$this->setTransient($this::generateTransient());
	}
	
	public static function generateExpiry(){
		return date('Y-m-j H:i:s', strtotime('+'.intval(Startup::$config['passwordResetTimeout']).' minutes'));
	}
	
	public function generateAndSetExpiry(){
		$this->setExpiry($this::generateExpiry());
	}
	
	
	
}