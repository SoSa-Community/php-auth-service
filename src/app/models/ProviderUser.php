<?php

namespace models;

/**
 * @table("name"=>"provider_user")
 **/
class ProviderUser{
	/**
	 * @id
	 */
	private $id = 0;
	private $provider = '';
	
	/**
	 * @column("name"=>"unique_id")
	 */
	private $uniqueId = '';
	
	/**
	 * @column("name"=>"user_id")
	 */
	private $userId = '';
	
	/**
	 * @column("name"=>"access_token")
	 */
	private $accessToken = '';
	
	/**
	 * @column("name"=>"access_token_secret")
	 */
	private $accessTokenSecret = '';
	
	/**
	 * @column("name"=>"refresh_token")
	 */
	private $refreshToken = '';
	
	/**
	 * @column("name"=>"access_token_expiry")
	 */
	private $accessTokenExpiry = '';
	
	private $created = '';
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getProvider(){return $this->provider;}
	public function setProvider($provider){$this->provider = $provider;}
	
	public function getUniqueId(){return $this->uniqueId;}
	public function setUniqueId($uniqueId){$this->uniqueId = $uniqueId;}
	
	public function getUserId(){return $this->userId;}
	public function setUserId($userId){$this->userId = $userId;}
	
	public function getAccessToken(){return $this->accessToken;}
	public function setAccessToken($accessToken){$this->accessToken = $accessToken;}
	
	public function getAccessTokenSecret(){return $this->accessTokenSecret;}
	public function setAccessTokenSecret($accessTokenSecret){$this->accessTokenSecret = $accessTokenSecret;}
	
	public function getRefreshToken(){return $this->refreshToken;}
	public function setRefreshToken($refreshToken){$this->refreshToken = $refreshToken;}
	
	public function getAccessTokenExpiry(){return $this->accessTokenExpiry;}
	public function setAccessTokenExpiry($accessTokenExpiry){$this->accessTokenExpiry = $accessTokenExpiry;}
	
	public function getCreated(){return $this->created;}
	public function setCreated($created){$this->created = $created;}
	
	public function setFromJSON(array $jsonObject){
		$this->setUniqueId($jsonObject['account_id']);
		$this->setAccessToken($jsonObject['access_token']);
		$this->setRefreshToken($jsonObject['refresh_token']);
		$this->setAccessTokenExpiry(date('Y-m-j H:i',time() + $jsonObject['expires_in']));
	}
	
}