<?php

namespace models;

/**
 * @table("name"=>"users")
 **/
class User{
	/**
	 * @id
	 */
	private $id;
	private $username;
	private $password;
	
	/**
	 * @column("name"=>"email_hash")
	 */
	private $emailHash = '';
	
	public function verifyPassword(string $password){
		if(password_verify($password, $this->password)){
			return true;
		}
		return false;
	}
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getUsername(){return $this->username;}
	public function setUsername(string $username){$this->username = $username;}
	
	public function getPassword(){return $this->password;}
	public function setPassword(string $password){$this->password = $password;}
	
	public function getEmailHash(){return $this->emailHash;}
	public function setEmailHash(string $hash){$this->emailHash = $hash;}
	
	
	
}