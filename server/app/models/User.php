<?php

namespace models;

/**
 * @table("name"=>"user")
 **/
class User{
	/**
	 * @id
	 */
	private $id = 0;
	private $username = '';
	private $password = '';
	
	/**
	 * @column("name"=>"email_hash")
	 */
	private $emailHash = '';
	
	public function verifyPassword($password){
		if(password_verify($password, $this->password)){
			return true;
		}
		return false;
	}
	
	public function generatePasswordHash($password){
		return password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
	}
	
	public function hashPasswordAndSet($password){
		$this->setPassword($this->generatePasswordHash($password));
	}
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getUsername(){return $this->username;}
	public function setUsername($username){$this->username = $username;}
	
	public function getPassword(){return $this->password;}
	public function setPassword($password){$this->password = $password;}
	
	public static function generateEmailHash($email){
		return md5($email);
	}
	
	public function getEmailHash(){return $this->emailHash;}
	public function setEmailHash($hash){$this->emailHash = $hash;}
	
	/**
	 * Returns an array of elements for public consumption
	 * @return array
	 */
	public function getPublicOutput(){
		return array(
				"id" => $this->id,
				"username" => $this->username
		);
	}
	
	public static function isPasswordValid($password){
		return !empty($password) && strlen($password) >= 8;
	}
	
}