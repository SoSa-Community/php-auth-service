<?php


class APIError extends Error {
	
	private $field = null;
	
	public function __construct($message = "", $code = 0, $field=null, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->field = $field;
	}
	
	public function getField(){
		return $this->field;
	}
	
	public function forJSON() {
		return ['message' => $this->getMessage(), 'code' => $this->getCode(), 'field' => $this->getField()];
	}
	
}