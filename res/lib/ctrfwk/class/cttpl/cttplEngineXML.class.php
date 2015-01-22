<?php
/**
 * Counter Templating Engine XML Parser Classes
 *
 * @package CounterTemplating
 * @subpackage Engine
 * 
 */

class cttplEngineXML {
	public $match = array(cttplEngineMatchXML,'xml','<','>');
	
	

	/**
	 * XML Plugins
	 *
	 * @var array An array list with the XML Plugins
	 * @see cttplEngineXMLPlugin 
	 */	
	private $plugins = array();

	/**
	 * The cttpl main Engine from where is called this XML Engine
	 *
	 * @var cttplEngine
	 */
	private $engine;
	
	final public function __construct($engine) {
		$this->engine = $engine;
	}
		
	final public function reader(&$environmentVariables, cttplReader &$cttplReader, &$match) {
		$c = substr($cttplReader->buff, -1);
		
		if(!property_exists($cttplReader, 'xml'))
			$cttplReader->xml	= (object) array(		
													'underAttribute' => false
								);
			
		if(preg_match('/<[^\w\d._-]/', substr($cttplReader->buff, 0, 2))) {
			$cttplReader->xml->underAttribute = false;			
			return true;				
		} elseif($c == '"') 
			$cttplReader->xml->underAttribute = !$cttplReader->xml->underAttribute;
		elseif((!$cttplReader->xml->underAttribute) && $cttplReader->buffMatch('>')) {			
			$XMLElement	= preg_match('#^<([\w\d:._-]+)#', $cttplReader->buff, $matches);
			if($XMLElement)	$XMLElement = $matches[1];
			else {
				$cttplReader->xml->underAttribute = false;
				return true;
			}
			
			if($XMLElement == 'html') {
				$cttplReader->xml->underAttribute = false;
				return true;
			}
			
			$nslen				= strlen($this->engine->xmlns);
			$cttplElement		= ((substr($XMLElement, 0, $nslen) == $this->engine->xmlns) ? substr($XMLElement, ($nslen + 1)) : '');
			$cttplAttributes	= array();
			$XMLAttributes		= array();
			
			if(preg_match_all('#[\t\n\r ]+([\w\d:._-]+)="([^"]*)"#sxm', $cttplReader->buff, $matches, PREG_SET_ORDER)) {				
				while($match = array_shift($matches)) {
					$cttplAttribute = false;
					if($cttplAttribute = (substr($match[1], 0, $nslen) == $this->engine->xmlns)) {
						$array		= 'cttplAttributes';
						$attribute	= substr($match[1], ($nslen + 1));
						$value		= $match[2];						
					} else {
						$array		= 'XMLAttributes';
						$attribute	= $match[1];
						$value		= $this->engine->parseString(
																	$match[2], 
																	$environmentVariables, 
																	$cttplReader->line, 
																	((isset($cttplReader->file)) ? $cttplReader->file : $cttplReader->fromFile), 
																	false
						);
					}
					
					array_push($$array, array($attribute, $value));
				}
			}
			
			
			if($cttplElement || $cttplAttributes) {
				$XMLEndTag = $XMLStartTag = $XMLSimpleTag = null;
				if($cttplReader->buffMatch('/>')) {				
					$XMLSimpleTag	= $cttplReader->buff;
					$cttplReader->buff = '';
						 
					$XMLContent = null;
				} else {
					$XMLEndTag		= '</'.$XMLElement.'>';
					$XMLStartTag	= $cttplReader->buff;
					$cttplReader->buff = '';
					
					while(!$cttplReader->buffMatch($XMLEndTag)) {
						if($cttplReader->getc() === false) {
							new $this->engine->error(
							/*EMSG*/ 					sprintf('Cannot find the end tag for the element %s in %s line %d', $XMLElement, $cttplReader->name, $cttplReader->line),
														cttplErrorEngine
							);
							
							break;
						}
					}
					
					$XMLContent = substr($cttplReader->buff, 0, (0 - (strlen($XMLEndTag))));
				}
						
				var_dump(array(
							'xmle'	=> $XMLElement
							,'cte'	=> $cttplElement
							,'xmla'	=> $XMLAttributes
							,'cta'	=> $cttplAttributes
							,'st'	=> $XMLStartTag
							,'xc'	=> $XMLContent
							,'et'	=> $XMLEndTag
				));
			}
			
			$cttplReader->xml->underAttribute = false;
			$cttplReader->buffFlush();
			return true;			
		}
/**/	
		return false;
	}		
}

class cttplEngineXMLPlugin {
	public $attributes	= array();
	public $elements	= array();
	
	private $XMLEngine = null;
	
	final public function __construct(cttplEngineXML $cttplEngineXML) {
		$this->XMLEngine = $cttplEngineXML;
		$this->constructor();
	}
	
	public function constructor() {
		
	}
	
	public function attribute($attribute, $value, $tag) {
		
	}
	
	public function proccess($declaration, $XMLElement, $cttplElement, $XMLAttributes, $cttplAttributes) {
		echo $declaration;
	}
}

class cttplEngineXMLPluginMaster {
	public $attributes	= array('if','for','if','replace','strip','content');
	public $elements	= array();
	
	public function attribute($attribute, $value, $declaration) {
		 
	}
	
	public function element() {
	}
}

/*
 * 			if(preg_match('#/>$#', $cttplReader->buff)) {
				if($cttplAttribs || $cttplElement) {
					$this->xmlProcessor($cttplReader->buff, $environmentVariables, $cttplReader->file, $cttplReader->line);					
			
					$cttplReader->buff = '';
					return true;
				} 
			} else {
				if($cttplAttribs || $cttplElement) {
					$element = preg_match('#^<([\w\d.:_-]+)#', $cttplReader->buff, $matches);
										
					if($element) {						
						$element = $matches[1];						
					
						$cttplReader->xml->parseContent	= true;
						$cttplReader->xml->currentTag	= $element;
						$cttplReader->xml->cttplElement	= $cttplElement;
						$cttplReader->xml->cttplAttribs	= $cttplAttribs;
						
						return false;
					}
			}

 */
?>
