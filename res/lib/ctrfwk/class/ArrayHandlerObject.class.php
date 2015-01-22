<?php
/**
 * Counter Framework Array Handling Wrapper
 *
 * @package CounterFramework
 * @subpackage Utils
 * 
 */

/*
 *  @todo: a real variable parser for cfArrayHandlerObject::createStaticFor, because eval() is always a security risk.
 */
class cfArrayHandlerObject implements Iterator {
	//
	public final static function createStaticForGlobal($varstring) {
		if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $varstring)) {
			cfError::create(
				/* EMSG */ sprintf('cfArrayHandlerObject: "%s" is not a valid global variable.', $varstring)
				, cfErrorEngine
			);
			
			return false;
		}
		
		$cc			= get_called_class();
		$staticName = $cc.$varstring;
		
		$extcc		= '';
		$extname	= '';
		
		if($extcc = get_parent_class(__CLASS__)) {
			$extname	= $extcc.$varstring;
			call_user_func(array($extcc, 'createStaticForGlobal'),$varstring);
		}

		eval(str_replace(
				array('$this->source', $cc, $extcc)
				,array('$GLOBALS["'.addslashes($varstring).'"]', $staticName, $extname)
				, preg_replace(
					array('#(//)(.+?)(//\s+)#ms', '#require(_once?)\((.*?)\);#ms')
					, array('public function __construct() { }'.PHP_EOL, '')
					, substr(file_get_contents(dirname(__FILE__).'/'.substr($cc, 2).'.class.php'), 5, -4)
		)));
		
		return new $staticName();
	}
	
	protected $source;	
	public function __construct(&$source) {
		$this->source = $source;			
	}//

	public function __get($property) {
		if(isset($this->source[$property])) {		
			$toReturn = &$this->source[$property];
			return $toReturn;
		} else 
			return null;
	}
	
	public function __set($property, $value) {
		$this->source[$property] = null;				
		return $this->source[$property] = $value;		
	}	
	
	public function __unset($property) {
		unset($this->source[$property]);
	}
	
	public function __isset($property) {
		return isset($this->source[$property]);
	}

	public function rewind() {
		reset($this->source);
		return key($this->source);
	}

	public function current() {
		return current($this->source);
	}

	public function key() {
		return key($this->source);
	}

	public function next() {
		next($this->source);
		return key($this->source);
	}
	
	public function valid() {
		return !is_null(key($this->source));
	}
}
?>