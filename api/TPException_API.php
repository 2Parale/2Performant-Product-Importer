<?php

class TPException_API extends TPException {
	public function __construct($tp, $message, $previous = null, $data = null) {
		parent::__construct($tp, $message, $previous, $data);
	}
}
