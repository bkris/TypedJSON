<?php

	class CastClass {
		
		public static function cast( $object, $class ) {
			return unserialize(preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', serialize($object)));
		}
		
	}

?>