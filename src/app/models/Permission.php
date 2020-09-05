<?php

namespace models;

use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;

/**
 * @table("name"=>"permissions")
 **/
class Permission{
	/**
	 * @id
	 */
	private int $id = 0;
	
	/**
	 * @column("name"=>"name")
	 */
	private string $name = '';
	
	/**
	 * @column("name"=>"description")
	 */
	private string $description = '';
	
	/**
	 * @column("name"=>"enabled")
	 */
	private bool $enabled = false;
	
	private string $created = '';
	private string $updated = '';
	
	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	
	public function getName(){return $this->name;}
	public function setName($name){$this->name = $name;}
	
	public function getDescription(){return $this->description;}
	public function setDescription($description){$this->description = $description;}
	
	public function getEnabled(){return $this->enabled;}
	public function setEnabled($enabled){$this->enabled = $enabled;}
	
	
	public function getCreated(){return $this->created;}
	public function setCreated($created){$this->created = $created;}
	
	public function getUpdated(){return $this->updated;}
	public function setUpdated($updated){$this->updated = $updated;}
	
	
	
	
}