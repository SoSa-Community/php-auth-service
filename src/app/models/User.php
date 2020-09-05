<?php

namespace models;

use Ubiquity\exceptions\DAOException;
use Ubiquity\orm\DAO;

/**
 * @table("name"=>"user")
 **/
class User{
	/**
	 * @id
	 */
	private $id = 0;
	private $username = '';
	private $password = '';
	
	/**
	 * @column("name"=>"bot_id")
	 */
	private $botId = null;
	
	/**
	 * @column("name"=>"email_hash")
	 */
	private $emailHash = '';
	
	/**
	 * @transient
	 */
	public array $permissions = [];
	
	/**
	 * @transient
	 */
	public array $roles = [];
	
	/**
	 * @transient
	 */
	private bool $permissionsProcessed = false;
	
	public function verifyPassword($password){
		if(password_verify($password, $this->password)){
			return true;
		}
		return false;
	}
	
	public function generatePasswordHash($password){
		return password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
	}
	
	public function hashPasswordAndSet($password){
		$this->setPassword($this->generatePasswordHash($password));
	}
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getBotId(){return $this->botId;}
	public function setBotId($id){$this->botId = $id;}
	
	public function getUsername(){return $this->username;}
	public function setUsername($username){$this->username = $username;}
	
	public function getPassword(){return $this->password;}
	public function setPassword($password){$this->password = $password;}
	
	public static function generateEmailHash($email){
		return md5($email);
	}
	
	public function getEmailHash(){return $this->emailHash;}
	public function setEmailHash($hash){$this->emailHash = $hash;}

	public function isBot(){
		return !empty($this->botId);
	}
	
	/**
	 * Returns an array of elements for public consumption
	 * @param bool $sendRoles
	 * @param bool $sendPermissions
	 * @return array
	 */
	public function getPublicOutput($sendRoles=false, $sendPermissions=false){
		$return = array(
				"id" => $this->id,
				"username" => $this->username,
				"nickname" => $this->username,
				"is_bot" => $this->isBot()
		);
		
		if($sendRoles) {
			$return['roles'] = array_map(function($role){
				return ['id' => $role->getId(), 'name' => $role->getName()];
			}, $this->roles);
		}
		if($sendPermissions) $return['permissions'] = $this->permissions;
		
		return $return;
	}
	
	public static function isPasswordValid($password){
		return !empty($password) && strlen($password) >= 8;
	}
	
	public function hasPermission($permission=''){
		return (in_array($permission, $this->permissions));
	}

	public function processGroupsAndPermissions($forceRefresh=false){
		
		if(!$this->permissionsProcessed || $forceRefresh){
			$this->permissionsProcessed = true;
			
			$userRoles = DAO::getAll(UserRole::class, 'user_id = ?', false, [$this->id]);
			if(!empty($userRoles)) {
				$roleIds = [];
				foreach($userRoles as $role) $roleIds[] = $role->getRoleId();
				
				if(!empty($roleIds)){
					$params = array_map(function() {return '?';}, $roleIds);
					
					$roles = DAO::getAll(Role::class, 'id in ('.implode(',', $params).') AND enabled = 1', false, $roleIds);
					
					if(!empty($roles)){
						$roleIds = [];
						foreach($roles as $role){
							$this->roles[$role->getId()] = $role;
							$roleIds[] = $role->getId();
						}
						
						if(!empty($roleIds)){
							$params = array_map(function() {return '?';}, $roleIds);
							
							$permissions = DAO::getAll(RolePermission::class, 'role_id in ('.implode(',', $params).')', false, $roleIds);
							if(!empty($permissions)){
								foreach($permissions as $permission){
									$this->roles[$permission->getRoleId()]->permissions[] = $permission->getPermissionId();
									$this->permissions[] = $permission->getPermissionId();
								}
							}
						}
					}
				}
			}
		}
		return [$this->roles, $this->permissions];
	}
	
}