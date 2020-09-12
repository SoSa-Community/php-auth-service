<?php

namespace models;

use providers\WhoisProvider;
use Ubiquity\controllers\Startup;
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
	private $welcome = false;
	
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
	
	public function getWelcome(){return $this->welcome;}
	public function setWelcome($welcome){$this->welcome = $welcome;}
	
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
		
		if(!$this->welcome){
			$return['welcome'] = [
					'haveEmail' => !empty($this->emailHash)
			];
		}
		
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
	
	public static function isEmailValid($email){
		if(!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)){
			$emailSplit = explode('@', $email);
			$emailWhois = WhoisProvider::retrieve($emailSplit[1]);
			
			if(!empty($emailWhois) && $emailWhois->getExists()){
				return true;
			}
		}
		throw new \Exception('Please provide a valid e-mail address');
	}
	
	public static function isUsernameValid($username) {
		$usernameLength = !empty($username) ? strlen($username) : 0;
		if ($usernameLength >= Startup::$config['usernameMinLength'] && $usernameLength <= Startup::$config['usernameMaxLength']) {
			return true;
		}
		throw new \Exception('Username must be between ' . Startup::$config['usernameMinLength'] . ' and ' . Startup::$config['usernameMaxLength'] . ' characters');
	}
	
	public static function checkUsersExist($username=null, $emailHash=null, $existingUser=null) {
		
		$errors = [];
		
		$idCheck = '';
		$query = [];
		$criteria = [];
		if(!empty($existingUser)){
			$idCheck = 'id != ? AND ';
			$criteria[] = $existingUser->getId();
		}
		
		if (empty($existingUser) || $username !== $existingUser->getUsername()) {
			$query[] = 'username = ?';
			$criteria[] = $username;
		}
		
		if ($emailHash !== null && (empty($existingUser) || $emailHash !== $existingUser->getEmailHash())) {
			$query[] = 'email_hash = ?';
			$criteria[] = $emailHash;
		}
		
		if (!empty($criteria)) {
			$users = DAO::getAll(User::class, $idCheck . '(' . implode(' OR ', $query) . ')', false, $criteria);
			if (!empty($users)) {
				foreach ($users as $user) {
					if ($user->getUsername() === $username) {
						$errors['username'] = new \APIError('Someone with that username already exists', 0, 'username');
					}
					if ($user->getEmailHash() === $emailHash) {
						$errors['email'] = new \APIError('Someone with that e-mail already exists', 0, 'email');
					}
					if (isset($errors['username']) && isset($errors['email'])) break;
				}
			}
		}else{
			$errors = [new \APIError('Something else went wrong')];
		}
		return $errors;
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