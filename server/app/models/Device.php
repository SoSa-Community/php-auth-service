<?php

namespace models;

use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;
use \Firebase\JWT\JWT;
/**
 * @table("name"=>"devices")
 **/
class Device{
	/**
	 * @id
	 */
	private string $id = '';
	
	private string $name = '';
	
	/**
	 * @column("name"=>"user_id")
	 */
	private string $userId = '';
	
	/**
	 * @column("name"=>"push_service")
	 */
	private ?string $pushService = '';
	
	/**
	 * @column("name"=>"push_service_token")
	 */
	private ?string $pushServiceToken = '';
	
	private ?string $platform = '';
	private ?string $extra = '';
	private string $secret = '';
	
	private string $created = '';
	private string $updated = '';
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getName(){return $this->name;}
	public function setName($name){$this->name = substr($name, 0 ,100);}
	
	public function getUserId(){return $this->userId;}
	public function setUserId($userId){$this->userId = $userId;}
	
	public function getPushService(){return $this->pushService;}
	public function setPushService($service){
		if($service !== 'android' && $service !== 'ios')  $service = 'other';
		$this->pushService = $service;
	}
	
	public function getPushServiceToken(){return $this->pushServiceToken;}
	public function setPushServiceToken($token=''){$this->pushServiceToken = $token;}
	
	public function getPlatform(){return $this->platform;}
	public function setPlatform($platform){
		if($platform !== 'android' && $platform !== 'ios')  $platform = 'other';
		$this->platform = $platform;
	}
	
	public function getExtra(){return $this->extra;}
	public function setExtra($extra){$this->extra = $extra;}
	
	public function getSecret(){return $this->secret;}
	public function setSecret($secret){$this->secret = $secret;}
	
	public function getCreated(){return $this->created;}
	public function setCreated($created){$this->created = $created;}
	
	public function getUpdated(){return $this->updated;}
	public function setUpdated($updated){$this->updated = $updated;}
	
	public static function generateId(){
		$size = intval(Startup::$config['deviceIdSize']);
		return substr(bin2hex(random_bytes($size)), 0, $size);
	}
	
	public function generateAndSetId(){
		$this->setId(self::generateId());
	}
	
	public static function registerDevice($userId=null, $secret=null, $name='', $platform='other', $pushService='other', $pushToken=''){
		if(empty($secret)) {
			throw new \Exception('No device secret provided');
		}
		
		if(empty($name)){
			throw new \Exception('This is the first time we\'ve seen this device, please provide a valid name for it');
		}
		
		$device = new self();
		
		$device->generateAndSetId();
		$device->setSecret($secret);
		$device->setUserId($userId);
		$device->setName($name);
		$device->setPlatform($platform);
		
		if(!empty($service)){
			$device->setPushService($pushService);
			
			if(!empty($token)) {
				$device->setPushServiceToken($pushToken);
			}
		}
		
		DAO::save($device);
		return $device;
	}
	
	public function validateAndDecodeToken($token){
		JWT::$leeway = 60;
		
		$token = JWT::decode($token, $this->secret, array('HS256'));
		if(!empty($token)){
			if($token->device_id !== $this->getId()){
				throw new \Exception("Device ID doesn't match");
			}
		}
		return $token;
	}
}