<?php

namespace models;

use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;
use \Firebase\JWT\JWT;
/**
 * @table("name"=>"whois")
 **/
class Whois{
	/**
	 * @id
	 */
	private ?int $id = 0;
	
	private string $domain = '';
	private string $data = '';
	private int $exists = 0;
	private string $created = '';
	private string $modified = '';
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getDomain(){return $this->domain;}
	public function setDomain($domain){$this->domain = $domain;}
	
	public function getData(){return $this->domain;}
	public function setData($data){$this->data = $data;}
	
	public function getExists(){return $this->exists;}
	public function setExists($exists){$this->exists = intval($exists);}
	
	public function getCreated(){return $this->created;}
	public function setCreated($created){$this->created = $created;}
	
	public function getModified(){return $this->modified;}
	public function setModified($modified){$this->modified = $modified;}
}