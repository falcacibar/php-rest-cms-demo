<?php
/**
 * Counter framework Application Interface
 *
 * @package CounterFramework
 * @subpackage Core	 
 * 
 * @todo Internationalization
 * @todo Modularizated Load
 * @todo Session serializer for applicaion class with wakeup and sleep 
 * @todo Logging 
 * @todo Place regexp on place processor warning error catch 
 * @todo Places could be a method, a php file, or a template.
 */

require_once(dirname(__FILE__)."/../include/cfUtil.inc.php");
require_once(dirname(__FILE__)."/../include/cfPHPCompat.inc.php");

 
#require_once(dirname(__FILE__)."/WidgetsHandler.class.php");
require_once(dirname(__FILE__)."/ErrorHandler.class.php");
#require_once(dirname(__FILE__)."/StringFactory.class.php");
require_once(dirname(__FILE__)."/httpAppAdmin.class.php");
require_once(dirname(__FILE__)."/ArrayHandlerObject.class.php");
require_once(dirname(__FILE__)."/ArrayEnvHandlerObject.class.php");
require_once(dirname(__FILE__)."/Users.class.php");
require_once(dirname(__FILE__)."/ABSP.class.php");
require_once(dirname(__FILE__)."/Model.class.php");
require_once(dirname(__FILE__)."/AdminModel.class.php");
require_once(dirname(__FILE__)."/db.class.php");
require_once(dirname(__FILE__)."/ModelAutogen.class.php");	
	
#require_once(dirname(__FILE__)."/cttpl.class.php");


/**
* Do not does nothing about the trailing slash.
*/
define('cfAppendSlashNone', 0);

/**
* When the application find the PCRE match for a place, this modifier if not found the trailing slash, appends the slash to a place with a redirect, this is useful when you are trying to simulate a directory.
*/
define('cfAppendSlashRedirectOnly', 1);

/**
* When the application find the PCRE match for a place, this modifier appends the slash via redirect to the match of the regexp.
*/
define('cfAppendSlashRegexp', 2);
 
 /**
 * This modifier do an ignore case comparation in requestAssert application's method.
 */
define('cfApplicationRequestCompareIgnoreCase', 0);

 /**
 * This modifier do an sensitive case comparation in requestAssert application's method.
 */
define('cfApplicationRequestCompareSensitiveCase', 1);

	/**
	 * Counter Framework Application interface.
	 * Application class contains all the aplication issues and functionality.
	 * @package CounterFramework
	 * @subpackage Core	 	 
	 */
	class cfApplication 
	{
			/**
			* @ignore
			*/
			const __version = "1.4";
			
			/**
			* @ignore
			*/
			final public function __toString() {		
				return 'Counter Framework Application interface "'.get_class($this).'", Version '.self::__version;	
			}

			/**
			* Current base path of the application
			* @var string
			*/
			public $base	= null;
			
			/**
			* Current server path of the application.
			* @var string 
			*/
			public $path;
			
			/**
			* Current script name.
			* @var string 
			*/
			public $script	= null;
			
			/**
			* Current request path of the application.
			* @var string 
			*/
			public $request;
			
			/**
			* Current action of the application.
			* @var string 
			*/
			public $action	= null;					
			
			/**
			* Current config of the application
			* @var array
			*/
			public $conf;
			
			/**
			* Current application is logging.
			* @var boolean
			*/
			public $logging		= false;
			
			/**
			* Current application log filename.
			* @var string
			*/
			private $logFile	= '';
		
			/**
			* application's errors handler and container.
			* @var cfErrorHandler
			*/
			public	$errors;
			
			/**
			* Output errors inmediatly
			* @var boolean
			*/
			public	$outputErrorsInmediatly = false;
			
			/**
			* @ignore
			*/
			private	$dbs;
			
			/**
			* Application's selected database connection
			* @var cfDBConnection
			*/
			public	$databaseConnection;		
			
			/**
			* Alias for <i>databaseConnection</i>.
			* @var cfDBConnection			
			* @see $databaseConnection
			*/
			public	$DBConnection;
			
			/**
			* Alias for <i>databaseConnection</i>.
			* @var cfDBConnection			
			* @see $databaseConnection
			*/
			public	$dbConnection;
			
			/**
			* Alias for <i>databaseConnection</i>.
			* @var cfDBConnection			
			* @see $databaseConnection
			*/
			public	$dbConn;
			
			/**
			* @ignore
			*/
			private $placesCont = array();
			
			/**
			* Current selected application's place name that have requested.
			*/			
			private $currentPlace;
			
			/**
			* Current selected application's place request info
			*/
			public  $place;
			
			
			public $chunkCount;

			/**
			* @ignore
			*/
			private $redirectCont = array();
			
			/**
			* @ignore
			*/
			protected	$registerPlacesResources = false;
			
			/**
			* The name of security object that uses the application. This must be an inherited class of cfABSP
			* @see		cfABSP
			* @var		string
			*/
			protected	$securityObject		= 'cfABSP';
			
			/**
			* Security type			
			* @see cfSecurityNone
			* @see cfSecurityDefault
			* @see cfSecurityUsersOnly
			* @see cfSecurityCustom						
			* @var integer
			*/
			public		$securityType;
			
			/**
			* Current application's security object
			* @see		cfABSP
			* @var		cfABSP
			*/
			public		$security;

			/**
			* The name of users object that uses the application. This must be an inherited class of cfUsers
			* @see		cfUsers
			* @var		string
			*/
			protected	$usersObject		= 'cfUsers';
			
			/**
			* Current application's users object.
			* @see		cfUsers
			* @var		cfUsers
			*/
			public		$users;

			/**			
			* The name of the templating object that uses the application. This should be an inherited class of cttpl
			* @see		cttpl
			* @var		string
			*/			
			protected	$templatingObject	= 'cttpl';			
			
			/**
			* Current application's templating object.
			* @see		cttpl
			* @var		cttpl
			*/						
			public		$templating;
			
			/**
			* A $_GET object wrapper.
			* @var		stdObject;
			*/
			public		$get;
			
			/**
			* A $_POST object wrapper.
			* @var		stdObject;
			*/			
			public		$post;
			
			/**
			* A $_SESSION object wrapper.
			* @var		stdObject;
			*/			
			public		$session;
			
			/**
			* A $_COOKIES object wrapper.
			* @var		stdObject;
			*/			
			public		$cookies;

			/**
			* A $GLOBALS object wrapper.
			* @var		stdObject;
			*/			
			public		$globals;
			
			
			public		$sessionAutostart = true;
			
				
			public 		$templateEnvironment	= array();
			
			/**
			 * Initializes a Counter Framework application 
			 *
			 * @param array $conf The main configuration array.
			 */
			final public function __construct($conf=array()) {
				cfUtil::getRoutes($this);
				
				// Initialize Configuration
				$this->confProcessor($conf);
				
				$this->DBConnection			= &$this->dbConn;
				$this->dbConnection			= &$this->dbConn;
				$this->databaseConnection	= &$this->dbConn;
				
				// Initialize Error Handler
				$this->errors		= new cfErrorHandler($this);				
				if(isset($this->conf['defaultErrorFormat'])) {
					$this->setCoreErrorFormat($this->conf['defaultErrorFormat']);
				}
				
				// Initialize Widget Handler.
				// $this->widgets		= new cfWidgetsHandler($this);

				// Initialize Templating system (gcjlpc Parser)
				// $this->templating	= new cfgcjlpc(array('myApplication' => &$this));
				
				// Initialize session (wherever it is configured!)
				if($this->sessionAutostart)
					@session_start();
				
				// Initialize handlers of GET, POST, Cookies, Session, Globals and Server variables.

				$this->server		= cfArrayEnvHandlerObject::createStaticForGlobal('_SERVER');
				$this->post			= cfArrayHandlerObject::createStaticForGlobal('_POST');
				$this->session		= cfArrayHandlerObject::createStaticForGlobal('_SESSION');
				$this->get			= cfArrayHandlerObject::createStaticForGlobal('_GET');
				$this->cookies		= cfArrayHandlerObject::createStaticForGlobal('_COOKIE');
				$this->globals		= cfArrayHandlerObject::createStaticForGlobal('GLOBALS');

				
				// Initialize Security (Users, ACL)
				$this->securityProcessor();
				
				// User-Defined Constructor
				$this->constructor();
								
				// Initialize Place Engine
				$this->placeProcessor();
			}

			/**
			 * Configuration checker, procesor, parser and manager.
			 * @ignore
			 * @param array $conf Configuration array
			 */
			final private function confProcessor(&$conf) {
				// Get the default conf from $cfConfig global variable
				if(!$this->base) {
					if(!file_exists($this->file))
						cfError::create(
							/* EMSG */	"I'm not knoww what is the base application file, set the property file for the main Application class maybe with the __FILE__ macros.'",
										cfErrorEngine
						);
						
					$this->base = realpath(dirname($this->file));	
				}
				
				if(!$conf) {
					if(!isset($GLOBALS['cfConfig']))
						cfError::create(
						/* EMSG */	"Cannot load a default configuration.",
								cfErrorEngine
						);
					else
						$conf = &$GLOBALS['cfConfig'];					
				}
			
				
				// Databases
				if(!isset($conf['databases']))	$conf['databases'] = array();
				if(gettype($conf['databases']) != 'array')	$conf['databases'] = array();
				
				if($conf['databases']) {
					if(!function_exists("NewADOConnection"))
						cfError::create(
							/* EMSG */ "The configuration uses database but adodb is not loaded yet, please load before your application class declaration.",
							cfErrorEngine
						);					
						
					reset($conf['databases']);					
					while($dbConf = &$conf['databases'][($dbId = key($conf['databases']))]) {
						if(!isset($dbConf['autoconnect']) || (isset($dbConf['autoconnect']) && $dbConf['autoconnect'])) 
							$this->registerDatabase($dbId, $dbConf);
						
						if(!next($conf['databases'])) break;					
					}
				}

				// Security (ACL, Users)
				if(isset($conf['security'])) {
					if(is_array($conf['security'])) {
						if(isset($conf['security']['acl']))
							$this->securityType = cfSecurityDefault;
						elseif(
							isset($conf['security']['users'])
						)
							$this->securityType = cfSecurityUsersOnly;
						else
							$this->securityType = cfSecurityNone;
					}
					else
						$this->securityType = cfSecurityCustom;
				}
				else
					$this->securityType = cfSecurityNone;				
				
				// Widgets
				if(!isset($conf['extra_widgets'])) {
						$conf['extra_widgets'] = array();
				}

				// Set current config
				$this->conf = $conf;
			}
			
			/**
			 * Register a database connection into application
			 *
			 * @param mixed $dbId Database identifier to call later.
			 * @param array|string $URI_Conf An array with a conf of a database, or a ADODB URI for database connection.
			 */
			final public function registerDatabase($dbId, $URI_Conf) {	
				if(gettype($URI_Conf) != 'string') {
					if(gettype($URI_Conf) == 'array')
						$URI_Conf = $URI_Conf['type'].'://'.$URI_Conf['user'].':'.$URI_Conf['password'].'@'.$URI_Conf['host'].'/'.$URI_Conf['db'];
					else cfError::create(
							/* EMSG */		sprintf('Cannot get a suitable data type to register as database.'),
											cfErrorEngine
					);
				}
					
				$this->dbs[$dbId] =  ADONewConnection($URI_Conf);
				if(!$this->dbConn) $this->dbConn = &$this->dbs[$dbId];
				
				end($this->dbs);
				$this->dbs[key($this->dbs)]->application = &$this;
			}
			
			
			/**
			 * Registered database connection getter.
			 *
			 * @param string|int $id
			 * @return cfDBConnection
			 */
			final public function &databases($id) {
				if(isset($this->dbs[$id])) {
					$this->dbConn = &$this->dbs[$id]; 				
					return $this->dbs[$id];
				} elseif(isset($this->conf['databases'][$id])) {
					$this->registerDatabase($id, $this->conf['databases'][$id]);
					return $this->databases($id);
				} else {
					$ret = false;
					return $ret;
				}
			}
			
			/**
			 * Includes an adodb module
			 * 
			 * @param string $module
			 */
			final public function adodbModule($module) {
				global $cfConfig;
				$______modulePath_ = $cfConfig['adodb_path']."/".$module.".inc.php";
								
				eval('global $'.join(',$',array_keys($GLOBALS)).";");
				return require_once($______modulePath_);
			}			
			
			
			/**
			* Register a redirect.
			* When the application does not match any place, starts to search redirects, if not match redirects, send to index, forbidden or unauthorized depends on the security.
			* @param string $regexp Perl-Compatible (PCRE) Regular expression to search in the request.
			* @param string $path Path to redirect if match the request
			* @see cfUtil::pcrePathFix()
			*/
			final public function registerRedirect($regexp, $path) {
				$regexp = cfUtil::pcrePathFix($regexp);
				
				if(!isset($this->redirectCont[$regexp])) {
					if(is_string($path) || is_array($path)) {
						$this->redirectCont[$regexp] = $path;
					} else return cfError::create(
								/* EMSG */	sprintf('In the redirect regexp match %s the path must be have a string or an array to a random redirect.', $regexp),
											cfErrorEngine
					);
				} else return cfError::create(
							/* EMSG */		sprintf('There have registered a previous redirect for the regexp %s', $regexp),
											cfErrorEngine
				);
			}
			
			/**
			* @ignore
			*/
			final protected function securityProcessor() {				
				if($this->securityType != cfSecurityNone) {
					$usersObject = &$this->usersObject;
					
					if(class_exists($usersObject)) {
						if(
							$usersObject == 'cfUsers' ||
							($usersObject != 'cfUsers' && is_subclass_of($usersObject, 'cfUsers'))
						) {							
							$this->users = new $usersObject($this);							
							
							if($this->securityType != cfSecurityUsersOnly) {																
								$securityObject = &$this->securityObject;
								
								if(class_exists($securityObject)) {
									if(
										$securityObject == 'cfABSP' ||
										($securityObject != 'cfABSP' && is_subclass_of($securityObject, 'cfABSP'))
									) {											
										$this->security = new $securityObject($this, $this->users);
									}
									else return cfError::create(
											/* EMSG */ 'The custom security object "'.$securityObject.'" is not a inherited class of cfABSP.',
											cfErrorEngine						
									);
								}
								else return cfError::create(
									/* EMSG */ 'The custom security object "'.$securityObject.'" is not yer defined.',
												cfErrorEngine
								);
							}
							
							if($this->securityType == cfSecurityDefault) {
								$this->registerPlacesResources = true;
							}
						}
						else return cfError::create(
								/* EMSG */	'The custom users object "'.$usersObject.'" is not a inherited class of cfUsers.',
											cfErrorEngine						
						);
					}
					else return cfError::create(
						/* EMSG */ 'The custom users object "'.$usersObject.'"is not yet defined.',
									cfErrorEngine
					);					
				}				
			}
			
			/**
			* Do a string comparision with a chunk of the application's request
			* @param integer $chunkNo	Chunk's index number of application's request to compare, starts in 1.
			* @param string  $maybeIs	String to compare.
			* @param integer $case		Type of comparision.
			* @returns boolean true if comparision is correct or false otherwise.
			* @see cfApplicationRequestCompareIgnoreCase
			* @see cfApplicationRequestCompareSensitiveCase
			*/			
			final public function requestAssert($chunkNo, $maybeIs, $case=cfApplicationRequestCompareIgnoreCase) {
				$asserts	= func_get_args();
				$chunkNo	= array_shift($asserts);				
				$chunk		= $this->requestChunk($chunkNo);
				
				if(is_int(end($asserts)))	$case = array_pop($asserts);
				else						$case = cfApplicationRequestCompareIgnoreCase;
				
				
				if($chunk != false) {
					while($maybeIs = array_shift($asserts)) {
						if($case == cfApplicationRequestCompareIgnoreCase) { 
							$chunk		= strtolower($chunk);
							$maybeIs	= strtolower($maybeIs);					
						}
						
						if($chunk == $maybeIs) {
							return true; break;
						}
					}
				}
				return false;
			}
			
			/**
			* Get a chunk of the application's request
			* @param integer $chunkNo Chunk's index number of application's request to compare, starts in 1.
			* @returns string The requested chunk string.
			*/
			final public function requestChunk($chunkNo) {	
				$list = explode('/', $this->request);				
				$chunk = false;
				
				if($chunkNo > count($list))
					$chunkNo = count($list);	
				elseif($chunkNo < 1) 
					$chunkNo = 1;
					
				$chunkNo--;				
				if(isset($list[$chunkNo])) 
					$chunk = preg_replace('/(.*)(:.*)?/', '$1', $list[$chunkNo]);
				
				return $chunk;
			}

			/**
			* Get the base http request path, a file into the base path, or a complete application's request with script if needed.
			*
			* * If $path is null, returns the base http request path.<br />
			* * If $path is an existing file into the base path of the application, it returns* <br />
			* * If $path is a miscelaneous path, if have script, not rewrite, returns the base http request path + script + $path, if does not uses script, does not append, but appends $path.<br />
			* @param string $path 
			* @returns string 
			*/
			final public function basePath($path=null) {
				if ($this->script && $path === '') {
					return preg_replace('#/+#', '/', $this->path.'/'.$this->script.'/');
				} elseif(
					$this->script 
					&& ( (
						!is_null($path) 
						&& !file_exists(preg_replace('#/+#', '/', $this->base."/".$path))
					) || ($path == '/' && !($path = ''))
					) 
				) {
					return preg_replace('#/+#', '/', $this->path.'/'.$this->script.'/'.$path);
				}
				  
				return preg_replace('#/+#', '/', $this->path.'/'.$path);
			}

			/**
			* Redirect to $path sending an HTTP redirect header or a <meta> redirect HTML/XHTML tag.
			* @param $path $path to redirect
			* @see	cfUtil::refresh()
			*/
			final public function redirect($path) {			
				if(substr($path, 0, 1) != '/' && !preg_match('#^[\w+.-]+\://#', $path))	
					$path = $this->basePath($path);

				cfUtil::refresh($path);				

			}

			/**
			* Register place to match
			* @param string		$name The name of the place could be a method from the application class, a static class of the class name in $placesPack, or a filename of the res/templates or res/places php file (without .php).
			* @param string		$regexp Perl-Compatible (PCRE) Regular expression to search in the request.
			* @param integer	$appendSlash The final slash manage
			* @param boolean	$isManager If this place manage actions
			* @see cfUtil::pcrePathFix()
			* @see cfAppendSlashNone
			* @see cfAppendSlashRedirectOnly
			* @see cfAppendSlashRegexp
			*/
			final public function registerPlace($name, $regexp, $appendSlash=cfAppendSlashNone, $isManager=false) {
				$regexp 	= trim($regexp);
				$regexp		= cfUtil::pcrePathFix($regexp);
				$delimiter	= substr($regexp, 0, 1);
				if($delimiter == '#') $delimiter = '\#';
				
				if($isManager) {
					$pdelimiter = str_replace("\\", '', $delimiter);	
					$regexp = preg_replace(
									"#^".$delimiter."(.*?)([\/\$]{1,2})?".$delimiter."([a-z]*)$#i", 
									($pdelimiter)."$1(:.*?)?$2".$pdelimiter."$3", 
									$regexp
					);
				} 
				
				if($appendSlash == cfAppendSlashRegexp) {
					$regexp = preg_replace('#^(.*?)(\$)?((?<!^)'.$delimiter.'.*)$#', '$1(/)?$2$3', $regexp);
				}
				
				if(!isset($this->placesCont[$name])) {
					if($this->registerPlacesResources) 
						$this->security->registerResource($name);
				
					$this->placesCont[$name] = array();
				}
								
				array_push($this->placesCont[$name], array(
															'regexp' 	=> $regexp,
															'slash'		=> (bool) $appendSlash,
															'matches'	=> array()
														));
			}

			/**
			* Get a place by name
			* @param $name Name of the application's place, the place must be registered before.
			* @return array The place info
			*/
			final public function place($name) {
				if(isset($this->placesCont[$name])) {
					$r =  &$this->placesCont[$name];
					return $r;
				} else {
					$ret = false;
					return $ret;
				}				
			}
			
			/**
			* Get a list of the registered application's places names.
			* @return array Names list.
			*/
			final public function getPlaces() {
				return array_keys($this->placesCont);
			}
			
			/**
			* @ignore
			*/
			final public function placeProcessor() {				
				if(
					is_array($this->placesCont) &&
					$this->placesCont
				) {
					reset($this->placesCont);
					while($nPlace = &$this->placesCont[($name = key($this->placesCont))]) {
						reset($nPlace);
						while($place = &$nPlace[key($nPlace)]) {
							try {								
								if(preg_match_all(
											$place['regexp'], 
											$this->request, 
											$place['matches'], 
											PREG_PATTERN_ORDER
								)) {
									if($place['slash'] && substr($this->request, -1) != '/'){
										$this->redirect($this->request.'/'.($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : ''));
										break;
									}elseif(method_exists($this, $name)) {
										$place['matches'] = $place['matches'][0];														
										$this->place = &$this->placesCont[$name];										
										if( $this->securityType == cfSecurityDefault && 
										    !$this->security->onResourceCan(cfUsersLoggedIn, 0, $name)
										) {											
											if(!$this->security->onResourceCan(cfUsersAny, 0, $name)) {
												if($this->users->loggedIn)
													return $this->forbidden();
												else 
													return $this->unauthorized();			
											}
										} 

										return $this->$name();
									} else return cfError::create(
											"A place is defined but the handler is not created in the main application class.",
											cfErrorEngine
									);
								}
							}

							catch(Exception $e) {
								return cfError::create(
									/* EMSG */ 'PHP thows an error when procesing the places: '.$e->getMessage(),
											cfErrorEngine
								);
							}
							if(!next($nPlace)) break;
						}							
						
						if(!next($this->placesCont)) break;
					}
				}					
				reset($this->redirectCont);
				while($redirect = &$this->redirectCont[($regexp = key($this->redirectCont))]) {
					if(preg_match(
								$regexp, 
								$this->request
					)) {
						if(is_string($redirect))
							$redirectTo = $redirect;
						else 
							$redirectTo = $redirect[array_rand($redirect)];

						$this->redirect($redirect);
						return true;
					}
					
					if(!next($this->redirectCont)) break;
				}
				
				return $this->index();
			}

			public function template($name, $templateEnvironment=array()) {
				$this->tempTemplateVars = $templateEnvironment;
				unset($templateEnvironment);
				
				foreach(array_keys($this->templateEnvironment) as $____cfte_var___)
					$$____cfte_var___ = &$this->templateEnvironment[$____cfte_var___];
				
				foreach(array_keys($this->tempTemplateVars) as $____cfte_var___)
					$$____cfte_var___ = &$this->tempTemplateVars[$____cfte_var___];
				
				unset($this->tempTemplateVars, $___cfte_var___);	
				include(cfLocalPath.DS."templates".DS.$name.".php");
			}
			
			/**
			*  Counter Framework default page inherit.
			*/
			final public function defaultPage() {
				if(preg_match_all('#^cfimg/(.*)$#',$this->request, $matches, PREG_PATTERN_ORDER)) {
					readfile($this->conf['cf_path'].'/images/'.$matches[1][0]); 	
				} else 
					print(str_replace(
								"images/", 
								"cfimg/", 
								file_get_contents($this->conf['cf_path'].'/template/index.html')
					));
				
			}

			/**
			* When the application, does not match a place,a redirect and the security are not deny anything, the application goes here.
			*/
			public function index() {
				return $this->defaultPage();
			}			
			
			/**
			* When application's security says forbidden then applies this method.
			*/
			public function forbidden() {
				die("You cannot have acces to acces here.");
			}
			
			/**
			* When application's security says unauthorized then applies this method.
			*/			
			public function unauthorized() {
				die("You are not authorized to acces here.");
			}

			/**
			* This sent an error when you try to use JSON but you does not have the JSON in the corresponding methods.
			* @see JSONSerializer
			* @see JSONUnserializer
			*/
			public function JSONNotFoundError() {
				return cfError::create(
					/* EMSG */		'For serialize to JSON you may need extend the cfApplication class method JSONSerializer and JSONUnserializer',
									cfErrorEngine
				);				
			}
			
			/**
			* This should be a wrapper of your favorite JSON serialize function, by default try with the json php module.
			* @return string JSON encoded string
			* @link http://www.json.org/ The JSON Especification
			*/
			public function JSONSerializer($var) {
				if(function_exists('json_encode'))
					json_encode($var);
				else
					$this->JSONNotFoundError();
			}
			
			/**
			* This should be a wrapper of your favorite JSON parse function, by default try with the json php module.
			* @return mixed JSON decoded string
			* @link http://www.json.org/ The JSON Especification			
			*/
			public function JSONUnserializer($var) {
				if(function_exists('json_decode'))
					json_decode($var);
				else
					$this->JSONNotFoundError();
			}

			/**
			* Sent a formed JSON-RPC procedure.
			* @param mixed		$result	Result of JSON-RPC procedure.
			* @param string		$error	Error string.
			* @param integer	$id		Identifier for the JSON-RPC procedure.
			* @link http://json-rpc.org/wiki/specification The JSON-RPC Specification.
			*/
			public function JSONRPCResponse($result, $error=null, $id=null) {
				if(!function_exists('json_encode'))
					return new cfError('{"result":false,"error":"the function json_encode does not exists",id:null}');
              			
	       			foreach(explode(',','result,error,id') as $prop) {
					if(is_string($$prop) && !cfUtil::is_utf8($$prop))
						$$prop = utf8_encode($$prop);

					$out = json_encode($$prop);
					if($out == 'null' && $$prop !== null) 
						$$prop = '"json_encode have troubles, try changing all strings to utf-8 encoding."';
					else 	$$prop = $out;
				}
                
				cfUtil::noCacheHeaders();
				header("Content-Type: text/plain; charset=utf-8");
				header("Content-Disposition: inline");
				header("Content-Length: ".(26+strlen($result)+strlen($error)+strlen($id)));

				print('{"result":'.$result.',"error":'.$error.',"id":'.$id."}");
			}

			/**
			* Set the application's error format to use, This must be a inherited class of cfError.
			* @param string $type A string with the name of the inherited class of cfError.
			* @see cfError			
			*/
			public function setErrorFormat($type) {
				@ini_set('html_errors', 'off');
				$this->errors->registerErrorObject($type);
			}			
			
			/**
			* Set the core error format to use, this errors is applicable when the errors does not have managed with the application's error handler
			* @param string type A string with the name of the name of the inherited class of cfError.
			* @see cfError
			*/
			public function setCoreErrorFormat($type) {				
				global $CTRFWK_ERROR_DEFAULT_FORMAT;
				$CTRFWK_ERROR_DEFAULT_FORMAT = $type;
				$this->errors->registerErrorObject($type);
			}			
			
			/**
			* Set the application's error format to the JSON-RPC error output.
			* @see cfError
			* @see cfErrorJSONRPC
			*/		
			public function JSONRPCErrors() {
				$this->setErrorFormat('cfErrorJSONRPC');
			}
			
			/**
			* Set the application's error format to the HTML error output.
			* @see cfError
			* @see cfErrorHTML
			*/		
			public function HTMLErrors() {
				$this->setErrorFormat('cfErrorHTML');
			}			

			/**
			* Set the application's error format to the HTML Script alert error output.
			* @see cfError
			* @see cfErrorHTMLScript			
			*/		
			public function HTMLScriptErrors() {
				$this->setErrorFormat('cfErrorHTMLScript');
			}			
	}

?>
