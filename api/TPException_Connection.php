<?php

class TPException_Connection extends TPException {
	public function __construct($tp, $message, $previous = null, $data = null) {
		parent::__construct($tp, $message, $previous, $data);
	}
}