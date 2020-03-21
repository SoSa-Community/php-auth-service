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
	private $userId;
	
	private $token='';
	private $expiry='';
	private $transient='';
	
	public function getUserId(){return $this->userId;}
	public function setUserId($userId){$this->userId = $userId;}
	
	public function getToken(){return $this->token;}
	public function setToken($token){$this->token = $token;}
	
	public function getExpiry(){return $this->expiry;}
	public function setExpiry($expiry){$this->expiry = $expiry;}
	
	public function getTransient(){return $this->transient;}
	public function setTransient($transient){$this->transient = $transient;}
	
	public function generateToken(){
		$this->setToken(bin2hex(random_bytes(254)));
	}
	
	public function generateTransient(){
		$this->setTransient(bin2hex(random_bytes(45)));
	}
	
	public function generateExpiry(){
		$this->setExpiry(date('Y-m-j H:i:s', strtotime('+15 minutes')));
	}
	
	
	
}