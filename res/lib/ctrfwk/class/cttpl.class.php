<?php
/**
 * Counter Framework Templating Wrapper
 *
 * @package CounterFramework
 * @subpackage Core	 
 * 
 */

require_once(dirname(__FILE__)."/cttpl/cttpl.class.php");

class cfcttpl extends cttplEngine {		
	static	$appVar			= 'myApplication';
	public	$application	= null;

	final public function construct() {
		if(!isset($this->environment)) {
			return cfError::create(
					/* EMSG */  sprintf('In the main environment variables i cannot found the "%s" variable for the current application.', $this->appVar),			
								cfErrorEngine
				);
		}
						
		$this->application	= $environmentVariables['myApplication'];
	}
	
	final public function errorHandler($message, $type) {				
		return cfError::create(
								$message,
								$type								
		);		
	}
}

?>
