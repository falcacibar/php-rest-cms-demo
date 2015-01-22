<?php
/**
 * Counter Framework ACL Based Security Policy
 *
 * @package CounterFramework
 * @subpackage Security	 
 * 
 */
require_once(dirname(__FILE__).'/ErrorHandler.class.php');

define('cfSecurityNone', 0);
define('cfSecurityDefault', 1);
define('cfSecurityUsersOnly', 2);
define('cfSecurityCustom', 3);

define('cfSecurityActionSee', 0);

class cfABSP {	
	const __version = "1.0";
	final public function __toString() {
		$type = ((get_class($this) == 'cfABSP') ? 'default' : 'custom');		
		return 'Counter Framework '.$type.' ACL Based Security Policy (ABSP), Version '.self::__version;	
	}
	
	public $users;
	public $application;	
	
	protected $resourcesACL			= array();	
	protected $resourceGroupsACL	= array();
	
	protected $actions				= array('see');
	
	protected $resourceGroups		= array();
	protected $resources			= array();
		
	protected $hierarchy			= array();
	public $policy					= 'deny';
	
    final public function __construct(cfApplication &$application, cfUsers &$users) {
    	$this->users		= $users;
    	$this->application	= $application;    	
    	$this->constructor();    	
    	$this->aclLoader();    
    }

	public function aclLoader() {
		global $cfConfig;
		
		if(isset($cfConfig['security'])) {
			$aclPass	= false;			
			if(isset($cfConfig['security']['acl']) && is_array($cfConfig['security']['acl'])) {
				if(isset($cfConfig['security']['acl']['resources']) && is_array($cfConfig['security']['acl']['resources'])) {
					$aclPass = true;
					$this->parseAcl($cfConfig['security']['acl']['resources'], 0);					
				}
				if(isset($cfConfig['security']['acl']['resource_groups']) && is_array($cfConfig['security']['resource_groups'])) {
					$aclPass = true;
					$this->parseAcl($cfConfig['security']['acl']['resource_groups'], 1);
				}				
			}
			else cfError::create(
				/* EMSG */			"To use security i need a resource ACL or a resource group ACL.",
									cfErrorEngine
			);
			
			if(isset($cfConfig['security']['actions']) && is_array($cfConfig['security']['actions'])) {
				$this->actions		= array_merge($this->actions, $cfConfig['security']['actions']);		
			}
			
			if(isset($cfConfig['security']['resources'])) {
				if(is_array($cfConfig['security']['resources'])) {
					$this->resources	= $cfConfig['security']['resources'];
				} else return cfError::create(
					/* EMSG */	"If you want to use pre defined resources, define it as a array list",
								cfErrorEngine
				);
			}
			
			if(isset($cfConfig['security']['resource_groups'])) {
				if(is_array($cfConfig['security']['resource_groups'])) {
					$this->resourceGroups	= $cfConfig['security']['resource_groups'];
				} else return cfError::create(
					/* EMSG */	"If you want to use pre defined resources, define it as a array list",
								cfErrorEngine
				);
			}
			
		}
		else
			cfError::create(
		/* EMSG*/			"You are using security but is not have defined a minimal security struct in the config file.",
							cfErrorEngine
			);
	}
    
	final private function parseAcl($aclStruct, $type){
		/* TODO: ACL structure parser */
		$destinyAcl = array();
		
		if(is_array($aclStruct)) {
			reset($aclStruct);
			while($myAcl = &$aclStruct[($myResource = key($aclStruct))]) {
				$destinyAcl[$myResource] = array();
				
				// loop for users				
				reset($myAcl);				
				while(!is_bool($myAclUser = key($myAcl))) {					
					if(!is_int($myAclUser) || (is_int($myAclUser) && $myAclUser > 0))
						$myUser = $this->entityMap($myAclUser, 1, $this->users->getTable());
					else
						$myUser = 0;
					
					if(is_int($myUser)) {												
						$destinyAcl[$myResource][$myUser] = array();
						
						//loop for actions
						if(is_array($myAcl[$myAclUser])) {
							reset($myAcl[$myAclUser]);														
							while(is_int($myAclAction = key($myAcl[$myAclUser]))) {							
								if($myAclAction > 0)
									$myAction	= $this->entityMap($myAclAction, 1, $this->actions);
								else
									$myAction	= 0; 
								
								if(is_int($myAction)) {
									array_push($destinyAcl[$myResource][$myUser], $myAction);																																																								
								} else return cfError::create(
										/* EMSG */	sprintf('ACL Parser: Does not exists action %s supplied for the user %s in the resource %s', $myAclAction, $myAclUser, $myResource),
													cfErrorEngine
								);
								
								if(!next($myAcl[$myAclUser])) break;
							}
						}
					} else return cfError::create(
						/* EMSG */	sprintf('ACL Parser: Does not exists user %s supplied for the resouerce %s', $myAclUser, $myResource),
									cfErrorEngine
					);
					
					if(!next($myAcl)) break;
				}
				
				if(!next($aclStruct)) break;
			}
			
			if($type == 0)	{
				$this->resourcesACL			= $destinyAcl;
			} else {
				$this->resourceGroupsACL	= $destinyAcl;
			}
		}
	}
	
	final private function entityMap($data, $type, $array) {
		if(is_array($array)) {
			$inputType = (($type == 0) ? 'integer' : 'string');
			$outputType = (($type == 0) ? 'string' : 'integer');
			
			if(gettype($data) == $inputType) {				 
				return cfUtil::indexOrValueMapper($data, $array);
			} elseif(gettype($data) == $outputType) 
				return $data;
		}
		
		return false;
	}
	
	public function registerResource($resourceName) {
		array_push($this->resources, $resourceName);	
	}
	
	public function resource($resourceName) {
		return $this->entityMap($resourceName, 1, $this->resources);
	}
	
	public function resourceName($resourceId){
		return $this->entityMap($resourceId, 0, $this->resources);
	}
    
	public function resourceGroup($resourceGroupName) {
		return $this->entityMap($resourceGroupName, 1, $this->resourceGroups);
	}
    
	public function resourceGroupName($resourceGroupId) {
		return $this->entityMap($resourceGroupId, 0, $this->resourceGroups);		
	}
	
	final public function onACLCan($aclType, $user, $action, $aclTypeName, $specId=null) {
		
		if($user == cfUsersLoggedIn && $this->users->loggedIn)
			$user = $this->users->loggedIn;
		elseif($user != cfUsersAny)
			$user = $this->entityMap($user, 1, $this->users->getTable());
		
		if(is_int($user)) {
			if($action > 0)
				$action			= $this->entityMap($action, 1, $this->actions);				
			else				
				$action = 0;
				
			$aclTypeNameId	= (int) $this->entityMap($aclTypeName, 1, $this->resources);
			$aclTypeName	= $this->entityMap($aclTypeName, 0, $this->resources);
			$myResource		= false;
			
			if($aclType == 0 || $aclType == 2)
				$myAcl = $this->resourcesACL;
			elseif($aclType == 1 || $aclType == 3)
				$myAcl = $this->resourceGroupsACL;
			
			if(isset($myAcl[$aclTypeNameId])) 
				$myResource = $myAcl[$aclTypeNameId];	
			elseif(isset($myAcl[$aclTypeName])) 
				$myResource = $myAcl[$aclTypeName];	
			
			if($myResource) {				
				$myAccess = false;
				 
				if(isset($myResource[$user]))
					$myAccess = &$myResource[$user];
				elseif(isset($myResource[cfUsersAny]))
					$myAccess = &$myResource[cfUsersAny];
							
				if(!is_bool($myAccess)) {												
					if(in_array($action, $myAccess)) 
						return true;			
				}
			}
			
			if($this->resourceGroups && $aclType < 2) {
				reset($this->resourceGroups);
			
				if($aclType == 0) {
					while($myResourceGroup = &$this->resourceGroups[($myResourceGroupName = key($this->resourceGroups))]) {						
						if(	in_array($aclTypeNameId, $myResourceGroup, true) ||
							in_array($aclTypeName, $myResourceGroup, true) 
						)   							
							return $this->onACLCan(3, $user, $action, $myResourceGroupName);
						
						if(!next($this->resourceGroups)) break;
					}
				} else {					
					while($myResource = key($this->resourceGroups[$aclTypeName])) {						
						if(!$this->onACLCan(2, $user, $action, $myResource))
							return false;
						
						if(!next($this->resourceGroups[$aclTypeName])) break;
					}
					
					return true;
				}
			}
		}
					
		return false;
	}
	
	public function onResourceCan($user, $action, $resource, $specId=null) {			
		if($this->onACLCan(0, $user, $action, $resource))
			return true;
		else
			return $this->defaultAccess();
	}	
	
	public function onResourceGroupCan($user, $action, $resourceGroup, $specId=null) {
		if($this->onACLCan(1, $user, $action, $resourceGroup))
			return true;
	else		
		return $this->defaultAccess();
	}
	
	final public function defaultAccess() {
		if($this->policy == 'halt')
			return $this->halt();
		elseif($this->policy == 'allow')
			return true;
		else 
			return false;
	}
	
	public function halt() {
		cfError::create(
			/* EMSG */ 'ACL Request: The last request for acl is loose, could not find the components you request it.'
		);
		exit();
		return false;
	}
	
	public function constructor() {}
}
?>
