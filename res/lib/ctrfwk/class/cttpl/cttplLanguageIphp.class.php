<?php
/**
 * Counter Templating Language Interpreter for php
 *
 * @package CounterTemplating
 * @subpackage LanguageInterpreter
 * 
 */

class cttplLanguageIphp {
	private $engine;
	
	public $match	= array(
								array(cttplEngineMatchLanguage, 'php', '<?=', '?>'),
								array(cttplEngineMatchLanguage, 'php', '<?php', '?>')
	);

	public function __construct($engine) {
		$this->engine = $engine;	
	}
	
	final public function expressionReader(&$environmentVariables, cttplReader &$cttplReader, &$match) {
		if(!property_exists($cttplReader, 'expression'))
			$cttplReader->expression	= (object) array('type' => '');
		
		if($cttplReader->buffLen() > 1) {
			$c = substr($cttplReader->buff, -1);
			if(!$cttplReader->expression->type) {				
				if($c == '{') 
					$cttplReader->expression->type = 'exp';
				elseif(preg_match('/'.$this->engine->entityFirstCharRegexp.'/', $c)) 
					$cttplReader->expression->type = 'var';
				else {
					$cttplReader->buffFlush();
					return true;
				}
			}		
	
			if( ($cttplReader->expression->type == 'var' && !preg_match('/'.substr($this->engine->entityCharRegexp, 0, -1).'">:\(\)-]/', $c)) ||
				($cttplReader->expression->type == 'exp' && $c == '}')
			) {	
					 
				if($cttplReader->expression->type == 'var')  					
					$expression	= $this->convertEntities(
															$environmentVariables, 
															array(), 
															array(), 
															substr($cttplReader->buffGetRest($cttplReader->buffLen() - 1), 1), 
															$cttplReader->file, 
															$cttplReader->line
					);									
				else   
					$expression	= $this->convertEntities(
															$environmentVariables,
															$this->engine->functions,
															array('and','or','xor','new'),
															substr(substr($cttplReader->buffGetRest(0), 2), 0, -1),
															$cttplReader->file, 
															$cttplReader->line
					);

				
				$this->subprocessor(
										
										'echo $this->engine->expressionOutput('.$expression.')'	, 
										$environmentVariables, 
										$cttplReader->file,
										$cttplReader->line
				);				
				
				if($cttplReader->expression->type == 'var')
					$cttplReader->buffFlush();
				$cttplReader->expression->type = '';					
							
				return true; 
			}
		}
				 
		return false;		 
	}
	
	final public function subprocessor($source, &$environmentVariables, $template, $startLine) {			
		
		$___CTTPL_PHP_SUBPROCESSOR = array(
			'deleteMe'		=> true,
			'source'		=> $source,
			'prefix'		=> 'unset($___CTTPL_PHP_SUBPROCESSOR); ',
			'suffix'		=> "\n;"
		);
		
		if(!function_exists(chr(255).'cttpl_lambda_phpsubproc_error')) {
			eval('function '.chr(255).'cttpl_lambda_phpsubproc_error($num_err, $str_err, $file_err, $line_err) {
									if($num_err == E_STRICT) return; 
									$underTemplate	= preg_match(
								    					'."'".'#^'.__FILE__.'\\(\\d+\\)\s+:\s+eval\\(\\)'."'".'."\x27".'."'".'d code$#'."'".',
								    					$file_err
								    );
								    if($underTemplate) 
/* EMSG */								$error  		= "[[ cttpl Template Error => ";
									else
/* EMSG */								$error  		= "[[ cttpl Template External Error => ";
									$etype			= E_USER_ERROR;
								    
									if($num_err == E_NOTICE || $num_err == E_USER_NOTICE) {
								    	$error .= "Notice";
								    	$etype = E_USER_NOTICE;
									} elseif($num_err == E_PARSE) {
								    	$error .= "Parse error";
								    	$etype = E_USER_NOTICE;
									} elseif($num_err == E_WARNING || $num_err == E_USER_WARNING) {
										$error .= "Warning";
										$etype = E_USER_WARNING;
									} elseif($num_err == E_ERROR || $num_err == E_RECOVERABLE_ERROR || $num_err == E_USER_ERROR) 
										$error .= "Fatal error";
									else
								        $error .= "Catastrophical error";
  
								    if($underTemplate) { 
								    	$error .= ": ".$str_err." in '.$template.' on line ".($line_err + '.$startLine.' - 1)."]]";
								    } else {
								    	$error .= ": ".$str_err." in ".$file_err." on line ".($line_err)."]]";
								    }
									
									restore_error_handler();
									
									trigger_error($error, $etype);
									return true;
			}');
		}
		
		if(isset($environmentVariables['___CTTPL_PHP_SUBPROCESSOR']))
			unset($environmentVariables['___CTTPL_PHP_SUBPROCESSOR']);
					
		// unsetting variables that not use or can conflicts with the subprocessor;
		unset($source, $template, $startLine); 

		$___CTTPL_PHP_SUBPROCESSOR_ENVIRON = &$environmentVariables;
		
		extract($environmentVariables, EXTR_OVERWRITE);
		unset($environmentVariables);
		
		$___CTTPL_PHP_SUBPROCESSOR['source']	= preg_replace(
													'#(/\*.*?\*/)#se', 'preg_replace("/[^\n\r]*/", "", '."'".'$1'."'".')', 
													preg_replace(
														'#//[^\n]*\n#', "\n",
														preg_replace(
															'#((?<!\r)\n)|(\r(?!\n))#', "\r\n",
															$___CTTPL_PHP_SUBPROCESSOR['source']
														)
													)
												);

		set_error_handler(chr(255).'cttpl_lambda_phpsubproc_error');		
		
			
		eval(
				$___CTTPL_PHP_SUBPROCESSOR['prefix'] .
				$___CTTPL_PHP_SUBPROCESSOR['source'] .
				$___CTTPL_PHP_SUBPROCESSOR['suffix'] 
		);

		$___CTTPL_PHP_SUBPROCESSOR_ENVIRON += get_defined_vars();
		
		unset($___CTTPL_PHP_SUBPROCESSOR_ENVIRON['___CTTPL_PHP_SUBPROCESSOR_ENVIRON']);
		
		restore_error_handler();
	}

	final public function reader(&$environmentVariables, cttplReader &$cttplReader, &$match) {
		$c = substr($cttplReader->buff, -1);

		if(!property_exists($cttplReader, 'php'))
			$cttplReader->php	= (object) array(
													'underQuotes'	=> false,
													'phpStartLine'	=> $cttplReader->line,
								);
								
		if($cttplReader->php->phpStartLine == -1)
			$cttplReader->php->phpStartLine = $cttplReader->line;
		
		if($c == '"') {
			if($cttplReader->php->underQuotes == '"')	$cttplReader->php->underQuotes = false;
			elseif(!$cttplReader->php->underQuotes)		$cttplReader->php->underQuotes = '"';							
		} elseif($c == "'") {
			if($cttplReader->php->underQuotes == "'")	$cttplReader->php->underQuotes = false;
			elseif(!$cttplReader->php->underQuotes) 	$cttplReader->php->underQuotes = "'";						
		} elseif(preg_match('/<<<([a-z]+).$/i', $cttplReader->buff, $matches)) {
			$cttplReader->php->underQuotes = $matches[1];							
		} elseif(
			$cttplReader->php->underQuotes && 
			preg_match(
    					'/'.addcslashes($cttplReader->lineEnd, "\n\r").$cttplReader->php->underQuotes.';$/',
						$cttplReader->buff
			)
		)
		  																
		$cttplReader->php->underQuotes = false;
		
		if($cttplReader->php->underQuotes == false) {
			if($cttplReader->buffMatch('__LINE__'))
				$cttplReader->buff = str_replace('__LINE__', "'".$cttplReader->line."'", $cttplReader->buff);									
			elseif($cttplReader->buffMatch('__FILE__'))
				$cttplReader->buff = str_replace('__FILE__', "'".$cttplReader->file."'", $cttplReader->buff);								
			elseif($cttplReader->buffMatch('?>')) {		
				
				$php = substr(substr($cttplReader->buff, 0, -2), ((isset($match[2]{4})) ? 5 : 3));
				
				$this->subprocessor(
										((isset($match[2]{4})) ? '' : 'echo ').$php,
										$environmentVariables,
										$cttplReader->file,
										$cttplReader->php->phpStartLine
				);
								 
				$cttplReader->php->phpStartLine = -1;				
				$cttplReader->buff = '';
				return true;				
			}								
		}
		return false;
	}
	
	final public function convertEntities(&$environment, $functions, $reserved, $code, $template, $line) {				
		if($this->engine->entityRegexp) {
			$code			= preg_replace('/[\s\r\n\t ]+/smx', ' ', $code);
			$buffering		= false;
			$underString	= false;
			$is_object		= false;
			$expression		= '';
			$entity			= '';
			
			for($p=0;$p<strlen($code);$p++) {
				$c = substr($code,$p, 1);

				if($c == '"') {					
					$underString = (!$underString);					
				} elseif($underString && $c == chr(92)) {
					$expression .= $c;
					$p += 1;
					$c = substr($code, $p, 1);					
				} elseif(!$underString) {
						if(!$buffering && preg_match('/'.$this->engine->entityFirstCharRegexp.'/', $c)) {
							$entity .= $c;
							$buffering = true;	
						} elseif($buffering) {
							if(preg_match('/'.$this->engine->entityCharRegexp.'/', $c))
								$entity .= $c;
							elseif (substr($code, $p, 2) == '->' || substr($code, $p, 2) == '::') {
								$entity .= substr($code, $p, 3);
								$p += 2;
								$is_object = true;
							} else {					
								$is_function = (strpos('(', substr($code, $p, 2)) !== false);						
							
								if(!$is_object) {														
									if(
										$is_function && !(
											in_array($entity, $functions)	
											|| function_exists($entity)
										)
									) {
										/* EMSG */ return $this->engine->error(sprintf("In the template %s: The function %s does not exists at line %d.", $template, $entity, $line));								
									} else {
										if(!defined($entity) && !in_array($entity, $reserved)) {
											if(!((
													!in_array($entity, explode(',','this,environment,functions,reserved,code,template,line,buffering,expression,c,p,is_function,is_object')) 
													&& isset($$entity)
												) || isset($environment[$entity])
											)) {									
				 								/* EMSG */ return $this->engine->errorHandler(sprintf("In the template %s: The variable or constant %s does not exists at line %d.", $template, $entity, $line), cttplErrorEngine);										
											} else {
												$entity = '$' . $entity;
											}
										}
									}
								} else {									
									$classEntities	= preg_split('/(-\>|\:\:)/', $entity);
									$classEntity	= $classEntities[0];
												
									if(!(
											class_exists($classEntity) || 
											(	(
													!in_array($classEntity, explode(',','this,environment,functions,reserved,code,template,line,buffering,expression,c,p,is_function,classPieces,classEntity,e,is_object')) 
													&& isset($$classEntity) 
													&& is_object($$classEntity)
												) || (
													isset($environment[$classEntity]) 
													&& is_object($environment[$classEntity])
											)	)
									)) {
		 								/* EMSG */ return $this->engine->errorHandler(sprintf("In the template %s: The entity %s does not exist or is not a object at line %d.", $template, $classEntity, $line), cttplErrorEngine);							 									
									} elseif (!class_exists($classEntity)) {
										$entity = '$' . $entity;
									}
								}
								
								$expression .= $entity	;
								$entity 	= '';
								$is_object	= false;						
								$buffering	= false;
							}
							
						}
						
					if($buffering && $p == (strlen($code) - 1))
						$code .= ' ';
				}
						
				if(!$buffering) $expression .= $c;
			}
			
			if($buffering)
				$expression = $entity;
			
			return $expression;			
		}

		return $code;		
	}
	
	public function eventOnBeforeRead($source, cttplReader $reader) {
			if($reader instanceOf cctplReaderFile) {
				if(function_exists("php_check_syntax"))
					php_check_syntax($this->file);		
					
				if(function_exists("runkit_lint_file"))
					runkit_lint_file($this->file);
			}		
	}	
}
?>