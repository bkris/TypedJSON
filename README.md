TypedJSON
=========

JOSN encoder decoder with typecast using type annotations in the phpDoc comment

Usage
-----

You have to define @JSON() annotation to run the lib. 


You can use regular phpDoc type annotations
/**
 * @var string
 */

/*
 * @type int
 */

Typecast is working for all built in types and custom classes.

Example
-------

    class Something {
	/**
	 * @JSON(name='changed')
	 * @var string
	 */
	public $param;
    }

    class Test {
	
	/**
	 * @JSON(name='test', type='string', required=true)
	 * @var string
	 */
	public $param1 = null;
	
	/**
	 * @JSON(type='int[]', required=false)
	 * @type int[]
	 */
	public $param2 = null;
	
	/**
	 * @JSON()
	 */
	public $param3 = null;
	
	/**
	 * @JSON(type='Something[]')
	 */
	public $param4 = null;
	
	/**
	 * @JSON(required=false)
	 * @var unknown_type
	 */
	public $param6 = null;
	
    }

    $data = '{
	"test" : "aaa",
	"param2" : [1, 2, 3, 4, 5],
	"param3" : {
		"changed" : "test"
	},
	"param4" : [
		{"changed" : "test1"},
		{"changed" : "test2"},
		{"changed" : "test3"}
	],
	"param5" : "asdasdasda"
    }';

    $dataParsed = TypedJSON::decode($data, 'Test');

    print_r($dataParsed);
    echo "\n";
    echo $data;
    echo "\n";
    echo TypedJSON::encode($dataParsed);
    echo "\n";
