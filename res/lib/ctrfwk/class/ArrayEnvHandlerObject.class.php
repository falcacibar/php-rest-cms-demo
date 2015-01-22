<?php
/**
 * Counter Framework Array Handling Wrapper
 *
 * @package CounterFramework
 * @subpackage Utils
 * 
 */

require_once(dirname(__FILE__).'/ArrayHandlerObject.class.php');

class cfArrayEnvHandlerObject extends cfArrayHandlerObject {
	//
	public function __construct(&$source) {
		$this->source=&$source;
	}//
	
	final private function toUpperUndersocreSeparated($string){
		return strtoupper(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
	}	
	
	final public function __get($property) {
		return (isset($this->source[$this->toUpperUndersocreSeparated($property)]) ? $this->source[$this->toUpperUndersocreSeparated($property)] : null);
	}
	
	final public function __set($property, $value) {
		return $this->source[$this->toUpperUndersocreSeparated($property)] = $value;
	}	
	
	final public function __unset($property) {
		unset($this->source[$this->toUpperUndersocreSeparated($property)]);
	}
	
	final public function __isset($property) {
		return isset($this->source[$this->toUpperUndersocreSeparated($property)]);
	}
}
?>