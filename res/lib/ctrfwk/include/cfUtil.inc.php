<?php
/**
 * Counter Framework Auxiliary and Utility functions
 *
 * @package CounterFramework
 * @subpackage Utils
 * 
 */

/* export to a global context */
$__cfUtil_EXPORT__ = array(
								'refresh',
								'indexOrValueMapper',
								'rmdirr',
								'htmlout',
								'bool2str',
								'bool2int',								
								'int2bool',
								'miniSwitch',
								'checkRut',
								'hexscape',
								'htmlout',
								'is_date',
								'is_datetime',
								'daySwapYear',
								'is_utf8',
								'rand_float',
								'array_melt',
								''
); 
	
abstract class cfUtil {
	/**
	 * Export cfUtil functions to a global Context
	 *
	 */
	public static function export() {
		global $__cfUtil_EXPORT__;
		$export = $__cfUtil_EXPORT__;
			
		while($funcDef = array_shift($export)) {
			$funcCode		= "";
								
			eval('
			if(!function_exists("'.$funcDef.'")) {
				function '.$funcDef.'(){
					$arguments			= func_get_args();
					call_user_func_array(array("cfUtil", "'.$funcDef.'"), $arguments);
				};
			};');	
		}		
	}
	
	/**
	 * Refreshs or redirects to another url.
	 *
	 * @param string $url
	 */
	public static function refresh($url) {
		@header('Location: '.$url);
		die('<meta http-equiv="Refresh" content="0; url=&quot;'.$url.'&quot;" />');
	}

	/**
	 * Takes text, look the last word near the given length and replaces 
	 * it with the replacement, the result given is a length less or equal 
	 * than provided.
	 * 
	 * @param string $text
	 * @param int $length
	 * @param string $replacement
	 * @return string
	 */
	public static function truncateText($text, $length, $replacement = '...' ) {
		$length += strlen($replacement);
		
		if(isset($text{$length})) {
			return preg_replace('/\s\S+$/smx', $replacement, substr($text, 0, $length));
		} else return $text;
	}

	/**
	 * Map a array (or list) string or his index.
	 *
	 * @param int,string $data
	 * @param array $array
	 * @return int,string
	 */
	public static function indexOrValueMapper($data, &$array) {
		$type = gettype($data); 
		if($type == "string") {
			return array_search($data, $array);
		} else if ($type == "integer" && isset($array[$data])){
			return $array[$data];
		}
	}
	
	// filesystem functions
	public static function rmdirr($dir) 
	{
	   if($objs = glob($dir."/*")) {	   	
	       foreach($objs as $obj) {
	           is_dir($obj) ? rmdirr($obj) : unlink($obj);
	       }
	   }

	   return rmdir($dir);
	}	
	
	// core functions
	public static function bool2str($bool) {
		if(is_bool($bool)) {
			return (($bool) ? 'true' : 'false');
		} else return $bool;
	}
	
	public static function bool2int($bool) {
		if(is_bool($bool)) {
			return (($bool) ? 1 : 0);
		} else return $bool;		
	}
	
	public static function int2bool($int) {
		if(is_int($int)) {
			return !!$int;
		} else return $int;		
	}
	
	public static function pcrePathFix($regexp) {
		$delimiter	= substr($regexp, 0, 1);
		
		if($delimiter  == "/") {
			$regexp = str_replace('\/', '/',
								preg_replace('#^/(.*?)/([a-z]*)?$#i', '#$1#$2',
									str_replace('#', '\#', $regexp
				)));
			
		} 				
		
		return $regexp;
	}

	function rand_float ($min, $max) {
	   return ($min+lcg_value()*(abs($max-$min)));
	}
		
	public static function noCacheHeaders() {
	    header('Pragma: public');
	    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");                     
	    header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
	    header('Cache-Control: no-store, no-cache, must-revalidate');     
	    header('Cache-Control: pre-check=0, post-check=0, max-age=0');
	    header("Pragma: no-cache");
	    header("Expires: 0");
	}
	
	public static function phpFileUploadErrorFinder($fileId, $errorPrefix, $excludeNotUpload=false, $outputNow=true) {
		$postMaxSize = ini_get('post_max_size');
		
		$mult = substr($postMaxSize, -1);
		$mult = ($mult == 'M' ? 1048576 : ($mult == 'K' ? 1024 : ($mult == 'G' ? 1073741824 : 1)));
		
		if (($_SERVER['CONTENT_LENGTH'] > ($mult * ((int) $postMaxSize))) && $postMaxSize) {
			return new cfError(
				/* EMSG */		'The POST size exeeds the post_max_size php variable.',
								cfErrorEngine
			);
		} elseif(isset($_FILES[$fileId])) {			
			if($_FILES[$fileId]['error'] != 0) {	
				return cfError::create(
										$errorPrefix.eval('return cfUtil::miniSwitch(
											'.$_FILES[$fileId]['error'].',
												1	, "The uploaded file exceeds the upload_max_filesize directive in php.ini",
												2	, "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
												3	, "The uploaded file was only partially uploaded.",'.
												($excludeNotUpload ? '' : '
												4	, "No file was uploaded.",'
												).'
												6	, "Missing a temporary folder.",
												7	, "Failed to write file to disk.",
												8	, "File upload stopped by extension.",
												 	  "Unrecognized upload error."										
										);'), 
										cfError, 
										$outputNow
				);
			}
		} else return cfError::create(
				/* EMSG */		sprintf('Cannot find upload with field name "%s".', $fileId),
								cfErrorEngine
		);
		
		return true;
	}
	
	public static function miniSwitch() {
		$myArgs = func_get_args();
		if(func_num_args() % 2 != 0) {
			return cfError::create(
				/* EMSG */		'Wrong parameter count for miniSwitch, arguments are (input, assert1, return1, assert2, return2[...], default_return).',
								cfErrorEngine
			);			
		} else {
			$input = array_shift($myArgs);
			while(count($myArgs) > 1  && $assert = array_shift($myArgs))  {
				$return = array_shift($myArgs);
				if($assert == $input)
					break;
			}
			
			return (($assert == $input) ? $return : array_pop($myArgs));  
		}
	}

	public static function dv($r) {
		$s = 1;

		for($m = 0; $r != 0; $r/= 10)$s = ($s+$r%10 * (9-$m++%6))%11;
		return chr($s?$s+47:75);
	}

    public static function checkRut($rut)
	{
		if(preg_match("/^\d{7,9}\-(\d{1,2}|k|K)$/", $rut))
		{
			list($numrut, $dv)=explode("-", $rut);
			$cm_dv=self::dv($numrut);
			if($cm_dv==strtoupper($dv)) return true;
			else return false;
		}
		else return null;
	}
	
	// string functions
	public static function hexscape($string)
	{
		$buf	= "";
		for($i=0;$i<strlen($string);$i++)	{	
			$buf .= '\0x'.sprintf("%x", ord(substr($string, $i, 1)));
		}
		
		return $buf;
	}	
	
	public static function htmlout($string){
		if(gettype($string) != "string") return $string;		
		return nl2br(htmlentities($string));
	}	
	
	// date functions
	public static function is_date($dateStr)
	{
		if(gettype($dateStr)!="string") return $dateStr;
		
		if(preg_match("/^\d{1,2}(\/|-)\d{1,2}(\/|-)\d{4}$/", $dateStr))
		{	
		  list($day, $month, $year) = preg_split("/(\/|-)/", $dateStr);		   
		  if(checkdate((int) $month, (int) $day, (int) $year)) return true;		  	      
		}
		
		return false;
	}	
	
	public static function is_datetime($dateStr)
	{
		if(gettype($dateStr)!="string") return $dateStr;
		
		if(preg_match("/^\d{1,2}(\/|-)\d{1,2}(\/|-)\d{4}[ ]\d{1,2}[:]\d{1,2}[:]\d{1,2}$/", $dateStr))
		{	
		  @list($date, $time) = split(' ', $dateStr);
		  
		  if(isset($date) && isset($time))
		  {
		  	list($day, $month, $year) = preg_split("/(\/|-)/", $date);
		  	
		  	if(checkdate((int) $month, (int) $day, (int) $year))
		  	{
		  		@list($hour, $minutes, $seconds)	= explode(":", $time);
		  		
		  		if(
		  			isset($hour) &&
		  			isset($minutes) &&
		  			isset($seconds)
		  			)
		  		{	
			  		if(
			  			(
			  				$hour >= 0 && 
			  				$hour <= 24	
			  			)	&&
			  			(
			  				$minutes >= 0 &&
			  				$minutes <= 59
			  			)	&&
			  			(
			  				$seconds >= 0 &&
			  				$seconds <= 59
			  			)			
			  		)
			  			return true;
		  		}
		  	}	
		  }	  	      
		}
		
		return false;
	}	
	
	public static function daySwapYear($Date, $time=false)
	{
		if($time)
		@list($Date, $time)	= explode(" ", $Date);
		
		if(strpos($Date, "-")!==false) $sep="-";
		else if(strpos($Date, "/")!==false) $sep="/";     
		else return $Date;
		
		list($dat1, $dat2, $dat3)	= explode($sep, "$Date");
		$Date = $dat3 . $sep . $dat2 . $sep . $dat1.(($time) ? ' '.$time : '');
		return $Date;
	}	

	public static function &reftrim(&$string)
	{
		if(is_string($string) || is_object($string)) {
			$string = trim($string);
		}

		return $string;
	}		

    /**
     * Returns true if $string is valid UTF-8 and false otherwise.
     *
     * @since        1.14
     * @param [mixed] $string     string to be tested
     * @subpackage
     */
    static function is_utf8($string) {
      	if(is_string($string)) {
	        // From http://w3.org/International/questions/qa-forms-utf-8.html
	        return preg_match('%^(?:
	              [\x09\x0A\x0D\x20-\x7E]            # ASCII
	            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
	            |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
	            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	            |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
	            |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
	            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	            |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
	        )*$%xs', $string);
      	}
      
    } 	

	static function debug() {
		for($c=func_num_args(), $i=0;$i<$c;$i++) {
			ob_start();
			var_dump(func_get_arg($i));
			
			print(
					'<pre>' 
					. htmlentities(ob_get_clean())
					. '</pre>'
			);
		}		
	}

	function is_assoc($var) {
	        return is_array($var) && array_diff_key($var,array_keys(array_keys($var)));
	}
	
	function array_phagocyte($phagocyter, $phagocyted) {
		while(!is_null(key($phagocyted))) {
			array_push($phagocyter, array_shift($phagocyted));
		}
		
		unset($phagocyted);
		return $phagocyter;
	}     

	/**
	 * 
	 */
	function array_phagocyte_assoc($phagocyter, $phagocyted) {
		while(!is_null($k=key($phagocyted))) {
			$phagocyter[$k] = $phagocyted[$k];
			unset($phagocyted[$k]);
		}
		
		unset($phagocyted);
		return $phagocyter;
	}
    
	// Internal use functions
	public static function getRoutes(cfApplication &$application) {
		if(!isset($_SERVER['PATH_INFO'])) $_SERVER['PATH_INFO'] = '';
		
		if(isset($_SERVER['REDIRECT_CTRFWK_APP'])) {
			$application->path		= substr($_SERVER['REDIRECT_CTRFWK_APP'], 0, -1);
			$application->request		= $_SERVER['REDIRECT_CTRFWK_APP_REQUEST'];
			$application->script		= null;			
		} else {
			$application->path			= dirname($_SERVER['SCRIPT_NAME'])."/";
			$application->request		= substr($_SERVER['PATH_INFO'], 1);
			$application->script		= ((strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0) ? basename($_SERVER['SCRIPT_NAME']) : null);						
		}
		
		if(preg_match("#(.*?):([^\/]+)(/)?(.*)$#", $application->request, $matches)) {
			if(!isset($matches[3])) $matches[3] = '';
			if(!isset($matches[4])) $matches[4] = '';
			$application->request = $matches[1].$matches[3].$matches[4];
			$application->action = $matches[2];
		}
		
		$application->chunkCount		= (count(explode('/', $application->request)) - 1); 
	}
}
?>
