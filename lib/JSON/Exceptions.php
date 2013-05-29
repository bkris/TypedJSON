<?php

class JSONException extends Exception {
	
	public function __construct($message = null, $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
	}
	
}

class JSONParseException extends JSONException {}

class JSONTypeCastException extends JSONException {
	
	public function __construct($typeFrom = null, $typeTo = null) {
		parent::__construct("Object of class ".$typeFrom." could not be converted to ".$typeTo."!");
	}
	
}

class JSONParameterTypeException extends JSONException {
	
	public function __construct($class = null) {
		parent::__construct("Could not find definition for class ( " . $class . " )! ");
	}
	
}

class JSONMissingParameterException extends JSONException {
	
	public function __construct($paramName = null) {
		parent::__construct("Required parameter missing ( " . $paramName . " )! ");
	}
}