<?php
/**
* Throw exceptions class extented to Exception for bad requests in TPerformant API
* @author Ionut Popescu <ionut.popescu@2parale.ro>
* @version 1.0
*/

class TPException extends Exception
{
	/**
	* construct function
	* @public
	*/
	public function __construct($message,$code = 0)
	{   
		return parent::__construct($message, $code);
	}
    
	/**
	* PHP magic function invoked when displaying a TPException object
	* @public
	*/
    public function __toString(){
    	 $errorMsg = '2Performant API Exception: '.$this->getMessage();
    	 return $errorMsg;
    }
}

?>
