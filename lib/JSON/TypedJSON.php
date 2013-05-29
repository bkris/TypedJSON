<?php

include_once "lib/JSON/TypedJSONAnnotationParser.php";
include_once 'lib/misc/CastClass.php';
include_once 'lib/JSON/Exceptions.php';

class TypedJSON {
	
	private static $BUILT_IN_TYPES = array( 'int', 		'integer' , 'Integer' ,
											'double', 	'Double', 	'float',
											'Float', 	'number', 	'Number',
											'real', 	'Real', 	'bool',
											'boolean', 	'Bolean', 	'string',
											'String', 	'array', 	'Array',
											'object', 	'Object', 	'unknown_type');

	/**
	 * Decode JSON string and cast to defined class
	 * @param string $data
	 * @param string $type
	 * @throws JSONParseException
	 * @return {$type}
	 */
	public static function decode($data, $type = null) {
		
		$decodedData = json_decode(str_replace("\\", "", $data));
		
		if (!$decodedData) {
			throw new JSONParseException();
		}
		
		if ($type != null) {
			$decodedData = self::castAll($type, $decodedData);
		}

		return $decodedData; 
	}
	
	/**
	 * Returns JSON representation of object
	 * @param Object $data
	 * @return string
	 */
	public static function encode($data) {
		
		$encoded =  json_encode( $data );
		
		$calssName = get_class($data);
		foreach ( self::getListOfNamesDifferFromParameterNames($calssName) as $key => $name) {
			// have to surround with " because have to find the exact variable name in the json string 
			$encoded = str_replace("\"".$key."\"", "\"".$name."\"", $encoded);
		}
		
		return $encoded;
	}
	
	/**
	 * Get list of parameter names from class which differ from defined name in the type annotation
	 * @param string $type
	 * @param array $listOfParamNames
	 * @return array
	 */
	private static function getListOfNamesDifferFromParameterNames($type, $listOfParamNames = array()) {
		
		if ( !self::isBuiltInType($type) ) {
			
			if( class_exists($type,true) ) {
				$annotation = new TypedJSONAnnotationParser($type);
					
				foreach ( $annotation->getProperties() as $paramType ) {
				
					if (!self::isBuiltInType($paramType->type)) {
							
						$listOfNamesTmp = self::getListOfNamesDifferFromParameterNames($paramType->type, $listOfParamNames);
							
						if (count($listOfNamesTmp) > 0) {
							$listOfParamNames = array_merge($listOfParamNames, $listOfNamesTmp);
						}
					}
				
					if ($paramType->name != false && $paramType->nameOfProperty != $paramType->name) {
						$listOfParamNames[$paramType->nameOfProperty] = $paramType->name;
					}
				}
			}
		}
		
		return $listOfParamNames;
	}

	/**
	 * Recursive function for casting object structure to defined type
	 * 
	 * @param string $type
	 * @param unknown_type $value
	 * @throws JSONTypeCastException
	 * @throws JSONParameterTypeException
	 * @throws JSONMissingParameterException
	 * @return unknown_type
	 */
	private static function castAll($type, $value) {
		
		if (self::isBuiltInType($type)) {
			
			if (gettype($value) === 'object' && $type !== 'object' && $type !== 'unknown_type' ) {
				throw new JSONTypeCastException(get_class($value), $type);
			}
			 
			if (gettype($value) === 'array' &&  $type !== 'array' && $type !== 'unknown_type' ) {
				throw new JSONTypeCastException(gettype($value), $type);
			}
			
			return self::castToType($type, $value);
			
		} else {
				
			if( !class_exists($type,true) ) {
				throw new JSONParameterTypeException($type);
			}
			
			$annotation = new TypedJSONAnnotationParser($type);
			$out = new $type();
			
			foreach ( $annotation->getProperties() as $paramType ) {
				
				$tmp = $paramType;
					
				if ($tmp->name != false) {
					$valTml = $value->{$tmp->name}; // in case of name property defined in the annotation
				} else {
					
					if (isset($value->{$tmp->nameOfProperty}) || property_exists($value, $tmp->nameOfProperty)) {
						$valTml = $value->{$tmp->nameOfProperty}; // in case of name dont defined
					} else {
						if ($tmp->required) {
							throw new JSONMissingParameterException($tmp->nameOfProperty);
						}
						$valTml = null;
					}	
				}
				
				if ($valTml != null) {
					
					if ($tmp->isArray) {
						
						for ($i = 0; $i < count($valTml); $i++) {
							$valTml[$i] = self::castAll($tmp->type, $valTml[$i]);
						}
						$out->{$tmp->nameOfProperty} = $valTml;
						
					} else {
						$out->{$tmp->nameOfProperty} = self::castAll($tmp->type, $valTml);
					} 
				}
			}
			return $out;
		} 
	}
	
	/**
	 * Cheks is built in type
	 * @param string $type
	 * @return bool
	 */
	private static function isBuiltInType($type) {
		if (in_array($type, self::$BUILT_IN_TYPES)) {
			return true;
		}
		return false;
	}
	
	/**
	 * Casts value to built in type
	 * @param string $type
	 * @param unknown_type $value
	 * @return {@type}
	 */
	private static function castToType($type, $value) {
	
		switch ($type) {
			//integer type alternatives
			case 'int':
			case 'integer' :
			case 'Integer' :
				return (int)$value;
				break;
					
			//double type alternatives
			case 'double':
			case 'Double' :
			case 'float' :
			case 'Float' :
			case 'number' :
			case 'Number' :
			case 'real' :
			case 'Real' :
				return (float)$value;
				break;
					
			//logical type alternatives
			case 'bool':
			case 'boolean' :
			case 'Bolean' :
				return (bool)$value;
				break;
					
			//string type alternatives
			case 'string':
			case 'String' :
				return (string)$value;
				break;
					
			//array type alternatives
			case 'array':
			case 'Array' :
				return (array)$value;
				break;
					
			//object type alternatives
			case 'object':
			case 'Object' :
				return (object)$value;
				break;

			//no tipe definition added
			case 'unknown_type' :
				return $value;
				break;
				
			default:
				return null;
		}
	
	}
	
}
