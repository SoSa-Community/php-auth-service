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
	 * @column("name"=>"bot_id")
	 */
	private int $botId = 0;
	
	/**
	 * @column("name"=>"device_id")
	 */
	private ?string $deviceId = '';
	
	/**
	 * @manyToOne
	 * @joinColumn("className"=>"models\\Device","name"=>"device_id","nullable"=>true)
	 **/
	private $device = null;
	
	/**
	 * @column("name"=>"refresh_token")
	 */
	private string $refreshToken = '';
	
	private $expiry = '';
	
	private string $created = '';
	private string $updated = '';
	private $verified = false;
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getUserId(){return $this->userId;}
	public function setUserId($userId){$this->userId = $userId;}
	
	public function getBotId(){return $this->botId;}
	public function setBotId($botId){$this->botId = $botId;}
	
	public function getDeviceId(){return $this->deviceId;}
	public function setDeviceId($deviceId){$this->deviceId = $deviceId;}
	
	public function getDevice(){
		return DAO::getOne(Device::class, '(id = ?)', false, [$this->getDeviceId()]);
		return $this->device;
	}
	public function setDevice($device){$this->device = $device;}
	
	
	public function getRefreshToken(){return $this->refreshToken;}
	public function setRefreshToken($refreshToken){$this->refreshToken = $refreshToken;}
	
	public function getExpiry(){return $this->expiry;}
	public function setExpiry($expiry){$this->expiry = $expiry;}
	
	public function getCreated(){return $this->created;}
	public function setCreated($created){$this->created = $created;}
	
	public function getUpdated(){return $this->updated;}
	public function setUpdated($updated){$this->updated = $updated;}
	
	public function getVerified(){
		return boolval($this->verified);
	}
	
	public function setVerified($verified){
		$this->verified = boolval($verified);
	}
	
	
	
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
		if(strtotime($this->getExpiry()) > time()){
			return false;
		}
		return true;
	}
	
	public function shouldUpdateExpiry(){
		$diff = strtotime($this->getExpiry()) - time();
		if($diff >= intval(Startup::$config['sessionUpdateInterval'])) return false;
		return true;
	}
	
	public static function getSession($id, $idType='device_id'){
		$session = DAO::getOne(Session::class, $idType.'=?', false, [$id]);
		if(!empty($session)){
			if($session->hasExpired()){
				DAO::remove($session);
				unset($session);
			}else{
				if(!$session->shouldUpdateExpiry()){
					return $session;
				}
			}
		}
	}
	
	public static function generateNewSession($userId=null, $deviceId=null,  $verified=false){
		if(!empty($userId)){
			
			$session = Session::getSession($deviceId);
			
			if(!isset($session) || empty($session)){
				$session = new self();
				$session->generateAndSetId();
				$session->generateAndSetRefreshToken();
				$session->setUserId($userId);
				
				$session->setVerified($verified);
				$session->setDeviceId($deviceId);
			}
			
			$session->generateAndSetExpiry();
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