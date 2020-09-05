<?php

namespace models;

use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;

/**
 * @table("name"=>"roles_permissions")
 **/
class RolePermission{
	/**
	 * @id
	 * @column("name"=>"role_id")
	 */
	private int $roleId = 0;
	
	/**
	 * @id
	 * @column("name"=>"permission_id")
	 */
	private string $permissionId = '';
	
	private string $created = '';
	private string $modified = '';
	
	public function getRoleId(){return $this->roleId;}
	public function setRoleId($roleId){$this->roleId = $roleId;}
	
	public function getPermissionId(){return $this->permissionId;}
	public function setPermissionId($permissionId){$this->permissionId = $permissionId;}
	
	public function getCreated(){return $this->created;}
	public function setCreated($created){$this->created = $created;}
	
	public function getModified(){return $this->modified;}
	public function setModified($modified){$this->modified = $modified;}
	
	
	
	
}