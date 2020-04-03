<?php

namespace models;

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
		return bin2hex(random_bytes(125));
	}
	
	public function generateAndSetToken(){
		$this->setToken($this::generateToken());
	}
	
	public static function generatePin($emailHash=''){
		return strtoupper(bin2hex(random_bytes(3))) . "-" . $emailHash;
	}

	public function generateAndSetPin($emailHash=''){
		return $this->setPin($this::generatePin($emailHash));
	}
	
	public static function generateTransient(){
		return bin2hex(random_bytes(24));
	}
	
	public function generateAndSetTransient(){
		$this->setTransient($this::generateTransient());
	}
	
	public static function generateExpiry(){
		return date('Y-m-j H:i:s', strtotime('+15 minutes'));
	}
	
	public function generateAndSetExpiry(){
		$this->setExpiry($this::generateExpiry());
	}
	
	
	
}