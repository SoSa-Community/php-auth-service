<?php

namespace models;

use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;

/**
 * @table("name"=>"users_roles")
 **/
class UserRole{
	/**
	 * @id
	 * @column("name"=>"user_id")
	 */
	private int $userId = 0;
	
	/**
	 * @id
	 * @column("name"=>"role_id")
	 */
	private int $roleId = 0;
	
	
	private string $created = '';
	private string $modified = '';
	
	public function getUserId(){return $this->userId;}
	public function setUserId($userId){$this->userId = $userId;}
	
	public function getRoleId(){return $this->roleId;}
	public function setRoleId($roleId){$this->roleId = $roleId;}
	
	
	public function getCreated(){return $this->created;}
	public function setCreated($created){$this->created = $created;}
	
	public function getModified(){return $this->modified;}
	public function setModified($modified){$this->modified = $modified;}
	
	
	
	
}