<?php

namespace models;

use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;
use \Firebase\JWT\JWT;
/**
 * @table("name"=>"bots")
 **/
class Bot{
	/**
	 * @id
	 */
	private ?int $id = 0;
	
	/**
	 * @column("name"=>"unique_id")
	 */
	private string $uniqueId = '';
	
	private string $name = '';
	
	/**
	 * @column("name"=>"owner_id")
	 */
	private int $ownerId = 0;
	private string $secret = '';
	
	private string $created = '';
	private string $modified = '';
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getUniqueId(){return $this->uniqueId;}
	public function setUniqueId($id){$this->uniqueId = $id;}
	
	public function getName(){return $this->name;}
	public function setName($name){$this->name = substr($name, 0 ,100);}
	
	public function getOwnerId(){return $this->ownerId;}
	public function setOwnerId($ownerId){$this->ownerId = $ownerId;}
	
	public function getSecret(){return $this->secret;}
	public function setSecret($secret){$this->secret = $secret;}
	
	public function getCreated(){return $this->created;}
	public function setCreated($created){$this->created = $created;}
	
	public function getModified(){return $this->modified;}
	public function setModified($modified){$this->modified = $modified;}
	
	public static function generateUniqueId(){
		$size = intval(Startup::$config['botIdSize']);
		return substr(bin2hex(random_bytes($size)), 0, $size);
	}
	
	public function generateAndSetUniqueId(){
		$this->setUniqueId(self::generateUniqueId());
	}
	
	public static function generateSecret(){
		$size = intval(Startup::$config['botSecretSize']);
		return substr(bin2hex(random_bytes($size)), 0, $size);
	}
	
	public function generateAndSetSecret(){
		$this->setSecret(self::generateSecret());
	}
	
	public function validateAndDecodeToken($token){
		JWT::$leeway = 60;
		
		$token = JWT::decode($token, $this->secret, array('HS256'));
		if(!empty($token)){
			if($token->id !== $this->getId()){
				throw new \Exception("Bot ID doesn't match");
			}
		}
		return $token;
	}
	
	public function getUser(){
		return DAO::getOne(User::class, '(bot_id = ?)', false, [$this->getId()]);
	}
	
}