<?php

class TypedJSONAnnotationParser {
	
	/**
	 * @var string
	 */
	private $class = null;
	
	/**
	 * @var TypedJSONAnnotation[]
	 */
	private $properties = array();
	
	/**
	 * @var ReflectionClass
	 */
	private $reflectionClass = null;
	
	/**
	 * @param string $type
	 */
	public function __construct($class) {
		$this->class = $class;
		$this->reflectionClass = new ReflectionClass($class);
		$this->extractPropertyAnnotations();
	}	
	
	/**
	 * Extract annotations from the class and stores in the $this->properties array
	 */
	private function extractPropertyAnnotations() {
		//echo "<pre>";
		$props = $this->reflectionClass->getProperties();
		foreach ($props as $prop) {
			$commentBlock = $prop->getDocComment();
			$this->properties[$prop->getName()] = $this->getPropertyAnnotation($prop->getName(), $commentBlock);
		}
		
		//print_r($this->properties);
		//echo var_dump($this->properties);
	}
	
	/**
	 * Get property comment block
	 * @param string $propName
	 * @param string $commentBlock
	 * @return TypedJSONAnnotation
	 */
	private function getPropertyAnnotation($propName, $commentBlock) {
		// annotation parser regex
		$ANNOTATION_PARSER = '/'.
							 '(?<isJson>@JSON)|'.  										// is JSON annotation
							 '(?<name>(?<=name=\')[a-zA-Z0-9_]*(?=\'))|'. 				// extract the name ( name='value' )
							 '(?<type>(?<=type=\')[a-zA-Z0-9<>\{\}\[\].*_]*(?=\'))|'. 	// extract the type ( type='value' )
							 '(?<required>(?<=required=)true|false)|'.					// extract the required field
							 '(?<phpDocVar>(?<=@var\s)[a-zA-Z0-9<>\{\}\[\].*_]*)|'.		// extract the phpDoc type ( @var )
							 '(?<phpDocType>(?<=@type\s)[a-zA-Z0-9<>\{\}\[\].*_]*)'.		// extract the phpDoc type ( @type )
							 '/';
		
		$annotation = new TypedJSONAnnotation();
		$annotation->nameOfProperty = $propName;
		
		$maches = array();
		$numOfMatches = preg_match_all($ANNOTATION_PARSER, $commentBlock, $maches);
		
		if ($this->getRegexValue($maches['isJson'])) {
			
			$annotation->name = $this->getRegexValue($maches['name']);
			$type = $this->getRegexValue($maches['type']);
			$phpDocVar = $this->getRegexValue($maches['phpDocVar']);
			$phpDocType = $this->getRegexValue($maches['phpDocType']);
			
			if ($phpDocVar) {
				$annotation->type = $phpDocVar;
			} else if ($phpDocType) {
				$annotation->type = $phpDocType;
			} else if ($type) {
				$annotation->type = $type;
			} else {
				$annotation->type = "unknown_type";
			}
			
			// array annotation check
			if ($this->contains("[]", $annotation->type)) {
				$annotation->type = str_replace("[]", "", $annotation->type);
				$annotation->isArray = true;
			} else if ($this->contains("<", $annotation->type) && $this->contains(">", $annotation->type)) {
				$annotation->type = str_replace("<", "", $annotation->type);
				$annotation->type = str_replace(">", "", $annotation->type);
				$annotation->type = str_replace("array", "", $annotation->type);
				$annotation->type = str_replace("Array", "", $annotation->type);
				$annotation->isArray = true;
			}

			$annotation->required = ( $this->getRegexValue($maches['required']) == "true" ? true : false );
			
			return $annotation;
		}
		
		return false;
	}
	
	/**
	 * String contains something
	 * @param String $needle
	 * @param String $haystack
	 * @return Boolean
	 */
	private function contains($needle, $haystack) {
		return strpos($haystack, $needle) !== false;
	}
	
	/**
	 * Get value from regex result
	 * 
	 * @param Array $regexMach
	 * @return String|boolean
	 */
	private function getRegexValue($regexMach) {
		foreach ($regexMach as $value) {
			if($value != null) return $value;
		}
		return false;
	}
	
	/**
	 * @param string $name
	 * @return TypedJSONAnnotation
	 */
	public function getType($name) {
		if (array_key_exists($name, $this->properties)) {
			return $this->properties[$name];
		}
		return false;
	}
	
	/**
	 * Get array of calculated class property annotations
	 * @return TypedJSONAnnotation[]
	 */
	public function getProperties() {
		return $this->properties;
	}
}

/**
 * Representation of the Typed JSON annotation
 */
class TypedJSONAnnotation {
	
	/**
	 * Type of the parameter
	 * @var string
	 */
	public $type = null;
	
	/**
	 * Name of the field in the JSON structure
	 * @var string
	 */
	public $name = null;
	
	/**
	 * Name of the property in php class
	 * @var string
	 */
	public $nameOfProperty = null;
	
	/**
	 * Is the field required
	 * @var bool
	 */
	public $required = false;
	
	/**
	 * Is the type array of type ( type[] or Array<type> )
	 * @var bool
	 */
	public $isArray = false;
	
}
