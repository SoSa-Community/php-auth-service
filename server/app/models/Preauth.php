<?php

namespace models;

use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;
use \Firebase\JWT\JWT;
/**
 * @table("name"=>"preauth")
 **/
class Preauth{
	/**
	 * @id
	 */
	private string $id = '';
	
	/**
	 * @column("name"=>"device_name")
	 */
	private string $deviceName = '';
	
	/**
	 * @column("name"=>"device_platform")
	 */
	private string $devicePlatform = '';
	
	/**
	 * @column("name"=>"device_secret")
	 */
	private string $deviceSecret = '';
	
	private string $created = '';
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getDeviceName(){return $this->deviceName;}
	public function setDeviceName($name){$this->deviceName = substr($name, 0 ,100);}
	
	public function getDevicePlatform(){return $this->devicePlatform;}
	public function setDevicePlatform($platform){
		if($platform !== 'android' && $platform !== 'ios')  $platform = 'other';
		$this->devicePlatform = $platform;
	}
	
	public function getDeviceSecret(){return $this->deviceSecret;}
	public function setDeviceSecret($secret){$this->deviceSecret = $secret;}
	
	public function getCreated(){return $this->created;}
	public function setCreated($created){$this->created = $created;}
	
	public static function generateId(){
		$size = intval(Startup::$config['preauthIdSize']);
		return substr(bin2hex(random_bytes($size)), 0, $size);
	}
	
	public function generateAndSetId(){
		$this->setId(self::generateId());
	}
	
	public static function registerPreauth($secret=null, $name='', $platform='other'){
		if(empty($secret)) {
			throw new \Exception('No device secret provided');
		}
		
		if(empty($name)){
			throw new \Exception('Please provide a valid name for this device');
		}
		
		$preauth = new self();
		
		$preauth->generateAndSetId();
		$preauth->setDeviceSecret($secret);
		$preauth->setDeviceName($name);
		$preauth->setDevicePlatform($platform);
		
		DAO::save($preauth);
		return $preauth;
	}
}