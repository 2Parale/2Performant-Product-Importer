<?php

class TPException extends Exception {
	private $_tp = null;
	private $_data = null;
	
	public function __construct($tp, $message, Exception $previous = null, $data = null) {
		if(phpversion() >= '5.3.0')
			parent::__construct($message, 0, $previous);
		else
			parent::__construct(
			is_null($previous) ? $message : ($message . ': ' . $previous)
			, 0);
		$this->_tp = $tp;
		$this->_data = $data;
	}
	
	private function assertTPerformant() {
		if(!isset($this->_tp))
			return 'TPerformant instance not set';
		if(!is_object($this->_tp))
			return 'TPerformant instance is not an object';
		if(get_class($this->_tp) != 'TPerformant')
			return 'TPerforant instance is corrupt';
		return false;
	}
	
	private $_assertError = null;
	public function getAssertError() {
		return $this->_assertError;
	}
	
	public function getAPI() {
		if($this->assertTPerformant())
			return false;
		
		return $this->_tp;
	}
	
	public function getData() {
		return $this->_data;
	}
}