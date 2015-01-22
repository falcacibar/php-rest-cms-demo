<?php
/**
 * Counter Templating Engine Classes
 *
 * @package CounterTemplating
 * @subpackage Engine
 * 
 */
foreach(explode(',','EngineXML,LanguageIphp,Reader') as $___include)
require_once(dirname(__FILE__)."/cttpl".$___include.".class.php");

/*
	TODO: Kid templating model http://www.kid-templating.org/language.html
	TODO: Template cache system
*/ 
define('cttplEngineMatchCustom'		, 0);
define('cttplEngineMatchLanguage'	, 1);
define('cttplEngineMatchXML'		, 2);
define('cttplEngineMatchXMLPlugin'	, 3);

define('cttplError'			, 0);
define('cttplErrorCritical'	, 1024);
define('cttplErrorEngine'	, 1025);

/**
 * cttpl model or Counter Template Model, used by The Counter Framework. The templating system 
 * was a own XML elements into XML namespace 'ctf' (Counter Template Feature) to create features, 
 * it have their own php subprocessor in a user-defined environment, read php tags <?= and <?php,   
 * and have attributes based in Kid templating http://www.kid-templating.org/.
 *
 **/
class cttplEngine {

	/**
	 * XML Namespace
	 *
	 * @var string <a href="http://www.w3.org/TR/REC-xml-names/#ns-decl">XML Namespace</a>
	 * @link http://www.w3.org/TR/REC-xml-names/#ns-decl XML Namespace
	 */
	public		$xmlns		= 'ctf';	
	
	/**
	 * XML charset
	 *
	 * @var string  <a href="http://www.iana.org/assignments/character-sets">IANA charset</a>
	 * @link http://www.iana.org/assignments/character-sets IANA charset
	 */
	public		$charset	= 'iso-8859-1';

	/**
	 * Default language here is php because this is maded in php
	 *
	 * @var string
	 */	
	public		$defaultLanguage = 'php';
	
	/**
	 * Set of languages used in this template  
	 *	 
	 * @var stdObj An stdObj Containing only properties with the corresponding language interface.  
	 */	
	public		$languages;

	/**
	 * XML reader
	 *
	 * @var cttplEngineMatchXML
	 */
	public		$xml;
	
	/**
	 * Custom matches
	 *
	 * @var array An array list with the custom matches	 
	 */		
	public		$customMatches = array();
	
	/**
	 * Custom functions to use in this environment
	 *
	 * @var stdObj An stdObj with public methods to use in this environment
	 */
	public	$functions = null; 

	/**
	 * Custom variables to use in this environment
	 *
	 * @var array An stdObj with the variables to use in this environment
	 */	
	public	$environment;
	
	/**
	 * First character regular expression for the entities that have in the templates
	 *
	 * @var string 
	 */
	public $entityFirstCharRegexp		= '[a-zA-Z_\x7f-\xff]';

	/**
	 * Non-first character regular expression for the entities that have in the templates
	 *
	 * @var string 
	 */	
	public $entityCharRegexp			= '[a-zA-Z0-9_\x7f-\xff]';


	/**
	 * The constructor for cttpl templating system main Class
	 *
	 * @param array $environmentVariables
	 * @final 
	 */
	final public function __construct($environmentVariables=array()) {
		$this->environment		= $environmentVariables;		
		$this->entityRegexp		= '/(?<!->)'.$this->entityFirstCharRegexp.$this->entityCharRegexp.'*\(?/';
		$this->xml				= new cttplEngineXML($this);
		
		$this->setLanguages();		
		$this->constructor();		
	}
	
	/**
	 * Set the active languages for the cttpl engine, you must specify 
	 * a list with the languages supported.
	 *
	 * @param array $languages
	 * @final
	 */
	final private function setLanguages($languages=array()) {
		$langs = array();
		
		foreach(array_unique(array_merge(array($this->defaultLanguage), $languages)) as $lang) {
			$langClass = 'cttplLanguageI' . $lang;
			
			if(class_exists($langClass)) {				
				$langs[$lang] = new $langClass($this);				 
			} else {								
				return $this->errorHandler(
					/* EMSG */  sprintf('The interface for the language %1$s (cttpLanguageI%1$s) does not exists.', $lang),			
								cttplErrorEngine
				);
			}
		}
		
		$this->languages = (object) $langs;
		unset($langs);
	}
	
	/**
	 * Language getter
	 *
	 * @param string language	 
	 * @return cttplLanguageI
	 * @final
	 */
	final private function language($language) {
		return $this->languages->$language;
	}
	
	/**
	 * Expression Output for the ${} and $ expression substitution
	 *
	 * @param mixed $expression Expression to be outputed
	 * @return Output equivalence for the passed expression
	 */
	public function expressionOutput($expression) {
		if(is_bool($expression)) 
			return (($expression) ? 'true' : 'false');
		else 
			return (string) $expression;
	}
	
	/**
	 * Parse a string with the cttpl Engine
	 *
	 * @param string $string cttpl source
	 * @param array $environmentVariables An array to add to the template environment 
	 * @param int $startLine The start line for display errors case
	 */
	final public function parseString($string, &$environmentVariables, $startLine=1, $fromFile=false, $print=true) {
		$cttplReader			= new cttplReaderString($string, $this, $startLine, $fromFile);
		
		if(!$print) ob_start();
		$this->parserEngine($cttplReader, &$environmentVariables);
		if(!$print) return ob_get_clean();
	}
	
	/**
	 * Parse a file with the cttpl Engine
	 *
	 * @param string $file cttpl source file
	 * @param array $environmentVariables An array to add to the template environment
	 * @final
	 */	
	final public function parse($file, &$environmentVariables, $print=true) {
		$cttplReader		= new cttplReaderFile($file, $this);
		
		if(!$print) ob_start();
		$this->parserEngine($cttplReader, &$environmentVariables);
		if(!$print) return ob_get_clean();
	}

	/**
	 * Parse a file with the cttpl Engine
	 *
	 * @param string $file cttpl source file
	 * @param array $environmentVariables An array to add to the template environment
	 * @final
	 */		
	final private function document($file, &$environmentVariables, $print=true) {
		return $this->parse($file, $environmentVariables, $print);
	}
		
	/**
	 * cttpl parser engine
	 *
	 * @param cttplReader $cttplReader cttpl source file
	 * @param array $environmentVariables An array to add to the template environment
	 * @final
	 */
	final public function parserEngine(cttplReader $cttplReader, &$environmentVariables) {
		$matches = array();		
		
		foreach($this->languages as &$lang) {						
			foreach($lang->match as &$match) {
				array_push($matches, &$match);
			}
		}

		array_push($matches, &$this->xml->match);

		foreach($this->customMatches as &$cmatches) {
			foreach($cmatches as &$match) {
				array_push($matches, &$match);
			}
		}
		
		unset($cmatches, $lang, $match);				
		
		$current		= false;
		$maxMatchLen	= 0;		
		
		if(!is_array($environmentVariables)) 
			$environmentVariables = array();
			
		$environmentVariables += $this->environment;
							
		while($cttplReader->getc() !== false) {
			if(!$current) {	
				if(
						($cttplReader->buffLen() == 1 && $cttplReader->buff == '$') || 
						($cttplReader->buffLen() > 1  && preg_match('#[^\$]\$#', substr($cttplReader->buff, -2)))
					) {						
						$current	= true;
						
						if($cttplReader->buffLen() > 1)
							print($cttplReader->buffGetRest(($cttplReader->buffLen() - 1)));
																		
				} else {
					foreach($matches as $match) {
						if(($currentMatchLen = strlen($match[2])) > $maxMatchLen) 
								$maxMatchLen = $currentMatchLen;
													
						if($cttplReader->buffMatch($match[2])) {														
							$current	= $match;
							
							if($cttplReader->buffLen() > strlen($match[2]))
								print($cttplReader->buffGetRest(($cttplReader->buffLen() - strlen($match[2]))));
							
							break;
						}
					}
				}
			} else {		
					$finish 	=	call_user_func(
						(($current[0] === cttplEngineMatchLanguage)	? array($this->language($current[1]), 'reader') :
						(($current[0] === cttplEngineMatchXML)		? array($this->xml, 'reader') : 
						(($current[0] === cttplEngineMatchCustom)	? array($this->customMatch($current[1]), 'reader') : 
							array($this->language($this->defaultLanguage), 'expressionReader') 
						))), &$environmentVariables, $cttplReader, $current
					);
					
					if($finish) {
						$current = false;
					}
			} 
		}
		
		$cttplReader->buffFlush();				
		$cttplReader->end();
	}
	
	/**
	 * The contructor to extend
	 *
	 */
	public function constructor() { }
	
	/**
	 * Error Handling for the cttpl Engine
	 *
	 * @param string $message
	 * @param const(cttplError,cttplErrorCritical,cttplErrorEngine) $type Error type	 
	 */
	public function errorHandler($message, $type) { 
		trigger_error($message,
						(
							($type == cttplErrorEngine) ? E_COMPILE_ERROR : 
						 		(($type == cttplErrorCritical) ? E_CORE_ERROR : E_ERROR						  
						 		)
						 )
		);
	}	
}
?>