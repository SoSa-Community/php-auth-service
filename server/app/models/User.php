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
	
	public function hashPasswordAndSet($password){
		$this->setPassword(password_hash($password, PASSWORD_DEFAULT, ['cost' => 15]));
	}
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getUsername(){return $this->username;}
	public function setUsername($username){$this->username = $username;}
	
	public function getPassword(){return $this->password;}
	public function setPassword($password){$this->password = $password;}
	
	public function getEmailHash(){return $this->emailHash;}
	public function setEmailHash($hash){$this->emailHash = $hash;}
	
	
	
	
	
}