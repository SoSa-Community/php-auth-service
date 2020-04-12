<?php

namespace models;

use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;

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
	
	/**
	 * @transient
	 */
	public bool $isNew = false;
	
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
	
	public static function generateSecret(){
		$size = intval(Startup::$config['deviceSecretSize']);
		return substr(bin2hex(random_bytes($size)), 0, $size);
	}
	
	public function generateAndSetSecret(){
		$this->setSecret(self::generateSecret());
	}
	
	public static function checkRegisterDevice($userId=null, $id=null, $name='', $platform='other', $service='other', $token=''){
		if(!empty($id)){
			
			$device = DAO::getById(Device::class, $id);
			if(empty($device)){
				if(empty($name)){
					throw new \Exception('This is the first time we\'ve seen this device, please provide a valid name for it');
				}
				
				$device = new self();
				
				$device->setId($id);
				$device->setUserId($userId);
				$device->setName($name);
				$device->setPlatform($platform);
				
				if(!empty($service)){
					$device->setPushService($service);
					
					if(!empty($token)) {
						$device->setPushServiceToken($token);
					}
				}
				
				$device->generateAndSetSecret();
				DAO::save($device);
				
				$device->isNew = true;
			}else{
				$changes = false;
				
				if($service !== $device->getPushService()){
					$changes = true;
					$device->setPushService($service);
				}
				
				if($token !== $device->getPushServiceToken()){
					$changes = true;
					$device->setPushServiceToken($token);
				}
				
				if($changes)    DAO::save($device);
				
			}
			
			return $device;
		}else{
			throw new \Exception('No device id provided');
		}
	}
}