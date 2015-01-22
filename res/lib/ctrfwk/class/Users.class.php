<?php
/**
 * Counter Framework Users Management
 *
 * @package CounterFramework
 * @subpackage Security	 
 * 
 */

require_once(dirname(__FILE__).'/ErrorHandler.class.php');

define('cfUsersLoggedIn', -1);
define('cfUsersAny', 0);

class cfUsers {
	const __version = "1.0";
	final public function __toString() {
		$type = ((get_class($this) == 'cfASBP') ? 'default' : 'custom');		
		return 'Counter Framework '.$type.' Users Class, Version '.self::__version;	
	}

	public		$users			= array(array('', ''));
	public		$application;
	public 		$loggedIn		= null;
	public		$loginPlace		= 'index';
	protected	$passwordEncryptFunction;
	
	final public function __construct(cfApplication &$application) {
		$this->users[0][0] = chr(0).chr(9).md5(microtime()).chr(13).chr(255); 
		$this->application = $application;
		$this->recoverLoggedInUser();	
		$this->loadUsers();
	}
	
	public function loadUsers() {		
		$cfConfig = &$this->application->conf;
				
		if(isset($cfConfig['security'])) {
			if(!isset($cfConfig['security']['password_encrypt_function']))
				$this->passwordEncryptFunction = 'sprintf';				
			elseif(!function_exists($cfConfig['security']['password_encrypt_function']))
				$this->passwordEncryptFunction = 'sprintf';
			else	
				$this->passwordEncriptFunction = $cfConfig['security']['password_encrypt_function'];
			
			if(isset($cfConfig['security']['users']) && is_array($cfConfig['security']['users'])) {
				$this->users		= array_merge($this->users, $cfConfig['security']['users']);		
			} else return cfError::create(
							/* EMSG */ 		'Users Load: Cannot load users from the config structure.'
			);
		}
		else
			cfError::create(
				/* EMSG*/		"Users Load: Security was defined buf but is not have defined a minimal security struct in the configuration file.",
								cfErrorEngine
			);		
	}
	
	public function setLoggedInUser($id) {
		@session_start();
		$_SESSION['___cfUsers_loggedIn_'] = $id;
		$this->loggedIn = $id;
	}
	
	public function recoverLoggedInUser() {
		@session_start();
		if(isset($_SESSION['___cfUsers_loggedIn_']))
			$this->loggedIn = $_SESSION['___cfUsers_loggedIn_'];
	}
	
	public function authentication($user, $passwd)	{
		$userType = gettype($user);
		if($userType == 'string')
			$user = $this->getId($user);
		elseif($userType != 'integer')
			return cfError::create(
				/* EMSG */	'Wrong parameter $user for authentication.',
							cfErrorEngine
			);
				
		if($user > 0) {			
			$pef	= $this->passwordEncryptFunction;
			$myUser	= $myPasswd = null;
			
			reset($this->users);
			while($myUserArray = &$this->users[($myUserId = key($this->users))]) {								
				list($myUser, $myPasswd) = $myUserArray;
								
				if($myUserId == $user && $myPasswd == $pef($passwd)) {
					$this->setLoggedInUser($myUserId);
					return $this->authorized();					
				}
				
				if(!next($this->users)) break;					
			}
		} 
		
		return $this->unauthorized();
	}
	
	public function getId($user) {
		foreach($this->users as $key => $value) {			
			if($value[0] == $user)
				return $key; 
		}
		
		return false;
	}
	
	public function auth($user, $passwd) {
		return $this->authentication($user, $passwd);		
	}
	
	public function getTable() {
		$outTable	= array();
		foreach($this->users as $key => $value) {			
			$outTable[$key] = $value[0];
		}
		
		return $outTable;
	}
	
	public function getName($idUser) {
		if(($idUser == cfUsersLoggedIn && $idUser = $this->loggedIn) && isset($this->users[$idUser]) && $idUser > 0) {
			return $this->users[$idUser][0];
		} else return false;
	}
	
	public function unauthorized()		{ return false;}
	public function authorized()		{ return true;}
	
	public function logout() {
		@session_destroy();
	}
}
