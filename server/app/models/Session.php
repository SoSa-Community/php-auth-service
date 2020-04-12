<?php

namespace models;

use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;

/**
 * @table("name"=>"sessions")
 **/
class Session{
	/**
	 * @id
	 */
	private string $id = '';
	
	/**
	 * @column("name"=>"user_id")
	 */
	private int $userId = 0;
	
	/**
	 * @column("name"=>"refresh_token")
	 */
	private string $refreshToken = '';
	
	private $expiry = '';
	
	private string $created = '';
	private string $updated = '';
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getUserId(){return $this->userId;}
	public function setUserId($userId){$this->userId = $userId;}
	
	public function getRefreshToken(){return $this->refreshToken;}
	public function setRefreshToken($refreshToken){$this->refreshToken = $refreshToken;}
	
	public function getExpiry(){return $this->expiry;}
	public function setExpiry($expiry){$this->expiry = $expiry;}
	
	public function getCreated(){return $this->created;}
	public function setCreated($created){$this->created = $created;}
	
	public function getUpdated(){return $this->updated;}
	public function setUpdated($updated){$this->updated = $updated;}
	
	public static function generateId(){
		return substr(bin2hex(random_bytes(100)), 0, intval(Startup::$config['sessionIdSize']));
	}
	
	public function generateAndSetId(){
		$this->setId(self::generateId());
	}
	
	public static function generateRefreshToken(){
		return substr(bin2hex(random_bytes(100)), 0, intval(Startup::$config['refreshTokenSize']));
	}
	
	public function generateAndSetRefreshToken(){
		$this->setRefreshToken(self::generateRefreshToken());
	}
	
	public static function generateExpiry(){
		return date('Y-m-j H:i:s', strtotime('+'.intval(Startup::$config['sessionTimeout']).' minutes'));
	}
	
	public function generateAndSetExpiry(){
		$this->setExpiry($this::generateExpiry());
	}
	
	/**
	 * Returns an array of elements for public consumption
	 * @return array
	 */
	public function getPublicOutput(){
		return array(
				"id" => $this->id,
				"expiry" => $this->expiry,
				"refresh_token" => $this->refreshToken
		);
	}
	
	public function hasExpired(){
		$maxExpiry = strtotime('-'.intval(Startup::$config['sessionTimeout']).' minutes');
		if(strtotime($this->getExpiry()) > $maxExpiry){
			return true;
		}
		return false;
	}
	
	public function isSessionValid(){
		return true;
	}
	
	public static function generateNewSession($userId=null){
		if(!empty($userId)){
			$session = new self();
			$session->generateAndSetId();
			$session->generateAndSetRefreshToken();
			$session->generateAndSetExpiry();
			$session->setUserId($userId);
			if(DAO::save($session)){
				return $session;
			}else{
				throw new \Exception('Could not generate a session, a system error occurred');
			}
		}else{
			throw new \Exception('Could not generate a session, no user id provided');
		}
	}
	
	
}