<?php
/**
 * Counter Framework Error Handling Classes
 *
 * @package CounterFramework
 * @subpackage Core	 
 * 
 */
 
define('cfError'			, 0);
define('cfErrorCritical'	, 1024);
define('cfErrorEngine'		, 1025);
define('cfErrorADOdbConn'	, 1026);
define('cfErrorADOdb'		, 1023);

$CTRFWK_ERROR_DEFAULT_FORMAT = 'cfError';

/**
 * The Counter framework Error Handler, is a class to handle the errors, 
 * and manage his output.
 */
class cfError
{

	const __version = "1.3";
	final public function __toString() {
		$type = ((get_class($this) == 'cfError') ? 'default' : 'custom');
		return 'Counter Framework '.$type.' Error handler '.self::__version;	
	}

	public	$showed = false;
	private $onlyFormat = false;
	private $format;		
	private $types = array(
							cfError		=> '%s',
			/* EMSG */			cfErrorCritical	=> '%s\nPongase en contacto con su administrador.',
			/* EMSG */			cfErrorEngine	=> 'Counter Framework Engine Error: %s',
			/* EMSG */			cfErrorADOdbConn	=> 'Counter Framework ADOdb Connection Error: %s',					
			/* EMSG */			cfErrorADOdb	=> 'Counter Framework ADOdb Error: %s'								
		);	
		
	/**
	 * Error Container of current Error object.	 
	 * @var object
	 */	
	public $container;
	
	/**
	 * Current message of the error
	 * @var string
	 */	
	public $message;
	
	/**
	 * Error Type	 * 
	 * @var constant(cfError,cfErrorCritical,cfErrorEngine,cfErrorADOdbConn,cfErrorADOdb)
	 */	
	public $type;
	
	final public static function create($message, $type=cfError, $output=true, cfErrorHandler &$container=null) {
	 	global $CTRFWK_ERROR_DEFAULT_FORMAT;
		return new $CTRFWK_ERROR_DEFAULT_FORMAT($message, $type, $output, $container);
	}
	
	/**
	 * Error contructor
	 *
	 * @param string $message
	 * @param constant(cfError,cfErrorCritical,cfErrorEngine,cfErrorADOdbConn,cfErrorADOdb) $type
	 * @param boolean $output
	 */
	final public function __construct($message, $type=cfError, $output=true, cfErrorHandler &$container=null)
	{	
		$this->container	= $container;
		$this->message		= $message;
		$this->constructor();
		
		$this->toType($type);
		
		if($output === 2) 
			$this->onlyFormat = true;
	
		if(($output && ((int) $output) < 2)|| $type < 1025) { 
			$this->_output();
		}
		
	}
	
	/**
	 * Set the current error to a specified type.
	 *
	 * @param constant(cfError,cfErrorCritical,cfErrorEngine,cfErrorADOdbConn,cfErrorADOdb) $type
	 */
	final public function toType($type) { 
		if(isset($this->types[$type])) {
			$this->type		= $type;
			$this->format	= $this->types[$type];
			
			if($type > 1024)
				$this->_output();
		}		
	}

	/**
	 * Set the current error to a critical type.
	 */
	final public function critical()	{ $this->toType(cfErrorCritical); }
	
	/**
	 * Set the current error to a engine type.
 	 */
	final public function engine()		{ $this->toType(cfErrorEngine); }
		
	final public function _output()	{
			if(!$this->showed)
			$this->output();
			$this->showed = true;
	}
	
	/**
	 * Extensible method for output the error.
	 *
	 */
	public function output() {	
		@ini_set('html_errors', 'off');
		print($this->message);								
	}
	
	/**
	 * Extensible method for constructor.
	 *
	 */	
	public function constructor() {
		
	}	
}
/**
* @todo: JSONSerializer function 
*/
class cfErrorJSONRPC extends cfError {
	public function output() {
		if(!property_exists($this, 'id'))
			$this->id = null;

		if(!function_exists('json_encode'))
			return new cfError('{"result":false,"error":"the function json_encode does not exists",id:null}');

//		 if(is_string($this->message) && !cfUtil::is_utf8($this->message))
//			$this->message = utf8_encode($this->message);


		$out = json_encode($this->message);
		if($out == 'null' && $this->message !== null) 
			$out = '"json_encode have troubles, try changing all strings to utf-8 encoding."';

		header("Content-Type: text/plain");
		header("Content-Disposition: inline");

		print '{"result":false,"error":';
		print $out;
//		print ((!cfUtil::is_utf8($out)) ? preg_replace('/"(.*?(?<!\\\))"/e', '"\"".utf8_encode("$1")."\""', $out) : $out); 
		print ',"id":'.json_encode($this->id).'}';

		die(); 
	}
}

class cfErrorHTMLScript extends cfError {
	public function output() {		
		@ini_set('html_errors', 'off');
		$this->message = str_replace("\n", '\n', $this->message);
		$this->message = str_replace("\r", '\r', $this->message);				
		$this->message = str_replace('"', '\"', $this->message);
	?>
<script type="text/javascript">
	alert("<?= $this->message; ?>");
</script>
<?php
	}
}

class cfErrorHTML extends cfError {
	public function output() {
	@ini_set('html_errors', 'off');
	?>
<div style="background-color: #ffbbbb; color: #000; font-size: 10pt; border: 1pt dashed #444; margin: 5px">
	<div style="padding: 10px 5px">
	<div style="font-size: 12pt; float: left; font-weight: bold; padding-right: 5px; font-family: sans-serif">
		<span style="background-color: #ff4; font-size: 16pt;padding: 3px; border: 1px solid #000">&nbsp;(&nbsp;!&nbsp;)&nbsp;</span>
	</div>
	<span style="font-size: 6pt"><br /></span>
	<?php echo nl2br(htmlentities($this->message)); ?>
	</div>
</div>
<?php
	}
}

class cfErrorHandler
{
	const __version = "1.2";
	final public function __toString() {
		$type = ((get_class($this) == 'cfErrorHandler') ? 'default' : 'custom');
		return 'Counter Framework '.$type.' Error container and handler, Version '.self::__version;	
	}
	
	public 	$application;	
	private $errorObject = '';
	
	protected $_errors	= array();
	protected $_morphers	= array();

	final public function __construct(cfApplication &$application=null) {
		if($application) {
			$this->application = &$application;
		}

		global $CTRFWK_ERROR_DEFAULT_FORMAT;
		$this->registerErrorObject($CTRFWK_ERROR_DEFAULT_FORMAT);
	}
	
	final public function registerErrorObject($errorObjectName) {
		if(class_exists($errorObjectName)) {
			if($errorObjectName == 'cfError' || is_subclass_of($errorObjectName, 'cfError'))
				$this->errorObject = $errorObjectName;
			else return cfError::create(
				/* EMSG */ 'The custom error object "'.$errorObjectName.'" is not a inherited class of cfError.',
				cfErrorEngine
			);
		}
		else return cfError::create(
				/* EMSG */ 'The custom error object "'.$errorObjectName.'" does not exists.',
				cfErrorEngine
			);		
	}
	
	
	final public function register($message, $type=cfError, $output=false) {
		if($this->application->outputErrorsInmediatly) 
			$output = true;
			
		$errorObject = &$this->errorObject;
		$this->messageMorpher($type, $message);
;
		if($output) 
			$myError = new $errorObject($message, $type, $output, $this);
		else {
			$this->_errors[]	= new $errorObject($message, $type, $output, $this);
				
			end($this->_errors);
			$this->_errors[key($this->_errors)]->container = &$this;
			
			return $this->_errors[key($this->_errors)];
		}
	}
	
	final public function output(){
		while($error = array_shift($this->_errors)) {
			$error->_output();
		}		
	}
	
	final public function registerMorpher($type, $pcre, $toType, $replace, $last=false) {
		try {
			preg_replace($pcre, '', '');
		}
		
		catch(Exception $e) {
			return cfError::create(
					/* EMSG */	'Cannot register error message morpher. "'.$e->getMessage().'"',
							cfErrorEngine	
			);
		}
		
		if(!isset($this->_morphers[$type])) 
			$this->_morphers[$type] = array();

		array_push($this->_morphers[$type], array($pcre, $toType, $replace, $last));		
	}

	final private function messageMorpher(&$type, &$message) {
		if($this->_morphers) {
			reset($this->_morphers);
			
			while(is_int($rType = key($this->_morphers))) {
				$morphers = &$this->_morphers[$rType];
				
				for($i=0,$cnt=count($morphers);$i<$cnt;$i++) {
					$morpher = &$morphers[$i];

					if($rType == $type) {
						try {
							if(preg_match($morpher[0], $message)) {
								$message	= preg_replace($morpher[0], $morpher[2], $message);
								$type		= $morpher[1];
							}
						}

						catch(Exception $e) {
							return cfError::create(
								/* EMSG */	'The error message morpher cannot proceed. "'.$e->getMessage().'".',
										cfErrorEngine
							);
						}
					
						if($morpher[2]) break;
					}

					if($morpher[2]) break;
				}
				
				next($this->_morphers);
			}
		}
	}
	
	
	final public function __destruct() {
		$this->destructor();
	}
	
	public function destructor() { 
		$this->output();
	}
} 
?>
