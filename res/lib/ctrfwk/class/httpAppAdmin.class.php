<?php
/**
 * @todo error at duplicated names
 * @todo former error at not fonund branches/sections
 * @todo errors 
 */

#xdebug_enable();
abstract class cfhttpAppAdminUtil {
	public static $JSExtendFunc	= 'jQuery.extend(true, ';
	
	static public function metadata() {
		$args	= func_get_args();
		$self	= array_shift($args);
				
		if(!is_object($self->metadata))
			$self->metadata = new stdClass();
		
		if($c = count($args) == 1) {
			return $self->metadata->{$args[0]};
		} elseif($c % 2 == 0) {			
			while(is_null(($name = array_shift($args))) && $value = array_shift($args)) {
				$self->metadata->{$name} = $value;
			}
			
			return $self;
		}
	}
	
	static public function addBranch() {
		$args	= func_get_args();
		$self	= array_shift($args);
		
		call_user_func_array(array('cfhttpAppAdminUtil', 'branch'), array_merge(array($self), $args));
		
		return $self;
	}
	
	static public function branchByName($self, $name) {
		$i = 0;
		foreach($self->branches as &$branch) {
			if(	($branch->name === $name)
				|| (property_exists($branch, 'table') && ($branch->table->name === $name))
			)
			
			return $i;
			++$i;		
		}	
	}
	
	static public function branch() {
		$args	= func_get_args();
		$self	= array_shift($args);

		if(count($args) == 1 && (is_numeric($args[0]) || is_string($args[0]))) {
			return $self->branches[((is_numeric($args[0])) ? $args[0] : self::branchByName($self, $args[0]))];		
		} else {
			$lb = null;
					
			while(($branch = array_shift($args)) instanceof cfhttpAppAdminTreeBranch) {
				array_push($self->branches, $branch);
				$self->branchMap[$branch->name] = count($self->branches) - 1;
				$lb = $branch;
			}
			
			return $lb;
		}
	}

	static public function removeBranch($self, $branch) {		
		$idx 	 = ((is_numeric($branch) || is_int($branch)) ? $branch : $self->branchMap[$branch]);
		$name	 = ((is_string($branch)) ? $branch : $self->branches[$branch]->id);
		
		array_splice($self->branches, $idx, 1);
		unset($self->branchMap[$name]);
		
		return $self;
	}
}

class cfhttpAppAdminSection  {	
	public $name;
	public $label;
	
 	public $branches	= array();	
	public $branchMap	= array();	
	
	final public function __construct($name, $label, $branches=array(), $metadata=array()) {
		$this->name 		= $name;
		$this->label		= $label;
		
		if($branches) {
			array_unshift($branches, $this);
			call_user_func_array(array('cfhttpAppAdminUtil', 'branch'), $branches);
		}
		
		if($metadata) {
			array_unshift($metadata, $this);
			call_user_func_array(array('cfhttpAppAdminUtil', 'metadata'), $metadata);			
		}		
	}
	
	/**
	 * @return cfhttpAppAdminSection
	 */
	public function addBranch() {
		$args = func_get_args();
		return call_user_func_array(array('cfhttpAppAdminUtil', 'branch'), array_merge(array($this), $args));		
	}	
	
	/**
	 * @return cfhttpAppAdminBranch
	 */ 
	public function branch() {
		$args = func_get_args();
		return call_user_func_array(array('cfhttpAppAdminUtil', 'branch'), array_merge(array($this), $args));		
	}
	
	public function removeBranch($branch) {
		return call_user_func(array('cfhttpAppAdminUtil', 'removeBranch'), $this, $branch);		
	}
	
	public function metadata() {
		$args = func_get_args();
		return call_user_func_array(array('cfhttpAppAdminUtil', 'metadata'), array_merge(array($this), $args));		
	}	
}


class cfhttpAppAdminTreeBranch {
	public $name;	
	public $label;
	
	public $autoform			= 'edit';
	public $contentJS 			= '{}';	
	public $contentJSProxyTo	= null;
	
	/**
	 * @var cfhttpAppAdminTreeBranch
	 */
	public $branch		= null;
	
	public $metadata;
	public $data		= array(); 
	
	public function __construct($name, $label, $data = array(), $metadata = array()) {
		$this->name 		= $name;
		$this->label		= $label;
		
		$this->data			= $data;
		
		if($metadata) {
			array_unshift($metadata, $this);
			call_user_func_array(array(cfhttpAppAdminUtil, 'metadata'), $metadata);		
		}	
	}	
	public function contentJSTemplate($environment) {		
		extract($environment);
		
		ob_start();			
		include(cfLocalPath.DS.'templates'.DS.'admin'.DS.'js'.DS.'content'.DS.$this->name.'.js');
		$this->contentJS = ob_get_clean();
		
		return $this;
	}	
		
	public function sprout() {
		return $this->data;
	}
	
	public function sproutBranches() {
		if($this->branch)
			return $this->branch->sprout();
	}
	
	public function contentJS() {				
		
		if(!is_null($this->contentJSProxyTo)) {
			if($this->contentJSProxyTo instanceOf cfhttpAppAdminTreeBranch)
				return $this->contentJSProxyTo->contentJS();
			else {
				throw 'No branch for specified proxied javascript branch content.';
			}
		} else {
			if($this->autoform)
				echo cfhttpAppAdminUtil::$JSExtendFunc.'{
					"'.$this->autoform.$this->name.'"	: {
								 "label" 	: "'.ucfirst($this->autoform).'"
								,"content"	: function(parent) {
									cfAdmin.autoform(parent, "'.$this->name.'", "'.$this->autoform.'", {
										"type" 		: "ajax"
									});
								}
					}
				}, ';
			
			echo $this->contentJS;
		
			if($this->autoform) echo ');';
			exit();
		}
	}
	
	public function metadata() {
		$args = func_get_args();
		return call_user_func_array(array('cfhttpAppAdminUtil', 'metadata'), array_merge(array($this), $args));		
	}	
}


class cfhttpAppAdminTreeBranchFromTable extends cfhttpAppAdminTreeBranch {
	/**
	 * @var cfAdminModel
	 */
	public $model;

	/**
	 * @var cfDBConnection 
	 */
	public $DBConnection;

	/**
	 * @var cfDBConnection 
	 */
	public $dbConn;
	
	/**
	 * @var cfAdminModelTable
	 */
	public $table;
	
	/**
	 * @var cfdbQuery
	 */	
	public $query;
	
	public $nameColumn		= 'name';
	public $nameColumnMod	= null;
	
	public function __construct($name, $label, cfAdminModelTable $table, $metadata=array()) {
		$this->table			= $table;
		$this->model			= $table->model;
		$this->DBConnection		= 
		$this->dbConn				= $this->model->DBConnection;
		
		parent::__construct($name, $label, null, $metadata);
		
		$this->query = cfdb::query($this->DBConnection);
		$this->query
			->select()
				->{$this->table->name};
		
		if($this->table->identity instanceof cfModelField)
				$this->query->fields
						->{$this->table->identity->name}(null, 'i');
		else
				$this->query->fields
						->param('0 as i');
		
		$this->query->fields
					->{$this->nameColumn}($this->nameColumnMod, 'n')
					->param("'".$this->name."' as t")
			->_
			->order
				->{$this->nameColumn}($this->nameColumnMod, asc)
		;
	} 
	
	
	public function sprout() {
		$pfm	= $this->dbConn->SetFetchMode(ADODB_FETCH_NUM);		
		$marrow	= $this->dbConn->GetAll($this->query);
		$this->dbConn->SetFetchMode($pfm);
		
		return $marrow;
	}
	
	public function sproutBranches($data) {
		if(is_object($this->branch)) {
			$s = (object) (((array) $this->metadata) + $data);
			  
			if(property_exists($s, 'value')) {
				if(property_exists($this, 'branch') && property_exists($this->branch, 'query')) {
					$query = clone $this->branch->query;
					
					if(!(property_exists($s, 'field') && $field = $s->field)) {
						foreach($this->branch->table->fields as &$pfield) {
							if(is_object($pfield->reference) && $pfield->reference->referenceField->table === $this->table) {
								$field = $pfield->name;
								break;
							}
						}
					}

					if(isset($field)) {
						$this->branch->query->
								where()
									->{$field}(eq, $s->value);
					} else {
						return cfError::create(
							sprintf("Not suitable field for the branch asociated to the table '%s'.", $this->branch->table->name)
							, cfErrorCritical
						);
					}
				}
			}
					
			$r =  $this->branch->sprout();
			
			if(isset($query)) {
				unset($this->branch->query);
				$this->branch->query = $query;			
			}
			
			return $r;
		}
	}	 
}

class cfhttpAppAdmin {
	public	$namePcreReplace	= array('/(^|_)?(\w).*?(_|$)/sx', '$2');
	public	$application		= null;

	public 	$sections			= array();
	public 	$sectionMap			= array();
	
	public	$metadata			= null;
	
 	public	$branches			= array();	
	public	$branchMap			= array();	
	
	public	$model				= null;

	public function __construct(cfApplication $application, $auto=true) {
		xdebug_enable();	
		$this->application = $application;		
		
		$automodel = cfdb::modelAutogen($application->dbConn, 'cfAdminModel');
		
		$application->adminModel	= 
		$this->model				= $automodel->generateModel();		
		
		unset($automodel);
		
		if($auto)
			return $this->autobuild();
	}

	public function __set($name, $value) {
		if(cfUtil::miniSwitch(
					 $name
					, 'tabs'		   , true
					, 'application'	   , true
					, false
		)) return false;

		$this->$name = $value;
	}
	
	public function autobranches(array $whitelist=null,array $blacklist=null) {
		return $this->autobuild($whitelist, $blacklist, true, false);
	}
	
	public function autosections(array $whitelist=null,array $blacklist=null) {
		return $this->autobuild($whitelist, $blacklist, false, true);
	}
	
	public function autobuild(array $whitelist=null,array $blacklist=null, $branches=true, $sections=true) {
		$names = array();
		foreach($this->model->tables as &$table) {
			if(
				(is_null($whitelist) || (!is_null($whitelist) && in_array($table->name, $whitelist))) 
				|| (is_null($blacklist) || (!is_null($blacklist) && !in_array($table->name, $blacklist)))
			) { 
				$name	= preg_replace($this->namePcreReplace[0], $this->namePcreReplace[1], $table->name);
				$i		= 0;
				
				$oname	= $name;
				while(in_array($name, $names)) {
					$name	= $oname.(++$i);
				}
				
				array_push($names, $name);
				// var_dump($table->name, $name);
				if($branches) {
					$root		  = $this->branch(new cfhttpAppAdminTreeBranch($name.'_rr', utf8_encode($table->label), array(
						array(0, utf8_encode($table->label), $name.'_rr')
					)));
					
					$root->table	= $table;
					$root->autoform	= 'add';			
					$root->branch	= $this->branch(new cfhttpAppAdminTreeBranchFromTable($name, utf8_encode($table->label), $table)); 
				}
				
				if($sections) {						
					if(($s = $this->section($name, utf8_encode($table->label))) && $branches) 
						$s->addBranch($root);
				}
			}
		}
		
		return $this;
	}
	
	private function registerSection(cfhttpAppAdminSection $section) {
		array_push($this->sections, $section);
		$this->sectionMap[$section->name] 	= count($this->sections) - 1;		
	}
	
	public function addSection($name, $label, $branches = array(), $metadata=array()) {
		$this->section($name, $label, $branches, $metadata);
		return $this;
	}
	
	public function removeSection($in) {
		$idx = null;
		
		if(is_string($in) && !is_numeric($in))
			$in = $this->section($in); 
			
		if(is_object($in) && ($in instanceof cfhttpAppAdminSection)) {
			$i = 0;
			
			foreach($this->sections as &$section) {
				if($in === $section) {
					$idx = $i;
					break;
				}		
			}
		} 
		
		if((!is_null($idx))) 						
			array_splice($this->sections, $idx, 1);
		
		return $this;
	}
	
	/**
	 * @return cfhttpAppAdminSection
	 */
	public function section() {
		$args = func_get_args();

		if(count($args) == 1 && (is_numeric($args[0]) || is_string($args[0]))) {
			if(!is_numeric($args[0])) {
				$i = 0;
				
				foreach($this->sections as &$section) {					
					if($section->name == $args[0]) {
						break;
					}
					
					++$i;
				}
				
				$args[0] = &$i;
			}
			
			return 	$this->sections[$args[0]];
		} elseif (count($args) == 1 && ($args[0] instanceof cfhttpAppAdminSection)) {
			$section = $args[0];	
		} else {
			$name		= array_shift($args);
			$label		= array_shift($args);
			
			$branches	= (count($args) ? array_shift($args) : array());
			$metadata	= (count($args) ? array_shift($args) : array());
			
			$section   = new cfhttpAppAdminSection($name, $label, $branches, $metadata);
		}
		
		$this->registerSection($section);
		
		return $section;		
	}

	/**
	 * @return cfhttpAppAppAdmin
	 */
	public function addBranch() {
		$args = func_get_args();
		return call_user_func_array(array('cfhttpAppAdminUtil', 'branch'), array_merge(array($this), $args));		
	}	

	/**
	 * Get a branch in this administrator.
	 * 
	 * @return cfhttpAppAdminBranch
	 */
	public function branch() {
		$args = func_get_args();		
		return call_user_func_array(array('cfhttpAppAdminUtil', 'branch'), array_merge(array($this), $args));		
	}
	
	public function removeBranch($branch) {
		return call_user_func(array('cfhttpAppAdminUtil', 'removeBranch'), $this, $branch);		
	}
	
	public function ControlDBOps($table, $action=null) {
		if(is_null($action))
			$action = $this->application->action;
				
		$identityField	= $this->model->table($table)->identity;

		if(cfUtil::miniSwitch($action, 'add', true, 'edit', true, false)) {
			$fieldMapper = array();
			if($this->application->server->requestMethod == 'POST') {
				$fields			= $values = array();
				$firstField 	= true;
        
				foreach($this->application->post as $field => $value) {
					
					if($field != '_id') {
						$modelField = $this->model->getFieldFromClientAlias($field); 

						if($modelField->validation !== null) {
							$modelField->validation->errors = $this->application->errors;
							$modelField->validation->proccess($value);
						}

						$fieldMapper[$modelField->name] = $field;
        
						array_push($fields, $modelField->name);
						array_push($values, $value);
					}
				}
        
				$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
				if($action == 'add') {
					$this->model->dbConn->Execute("
						INSERT		INTO `".$table."` (`".join("`,`", $fields)."`) 
						 			VALUES(".substr(str_repeat('?,', count($fields)), 0, -1).")
						"
						, $values
					);
					
					array_unshift($values, $this->model->dbConn->GetOne('SELECT LAST_INSERT_ID() as id'));
				} elseif($action == 'edit') {
					array_push($values, $_POST['_id']);
					($this->model->dbConn->Execute("
						UPDATE		`".$table."` 
								SET `".join("` = ?, `", $fields)."` = ? 
						WHERE 		`".$identityField->name."` = ?", 
						$values
					));
				}

				$fieldsvalues = array();
				
				$fieldMapper[$identityField->name] = $this->model->getClientAliasFromField($identityField);
				array_unshift($fields, $identityField->name);
				
				while($field = array_shift($fields)) {
					$value  = array_shift($values);
					$opts	= $this->model->table($table)->field($field)->options;

					if(!isset($opts['retrieve']) || (isset($opts['retrieve']) && $opts['retrieve'] == true))
						$fieldsvalues[$fieldMapper[$field]] = $value;
				}
				
				return $fieldsvalues;
			}
        
		} elseif($action == 'delete') {
			$this->model->dbConn->Execute("
				DELETE FROM 	`".$table."` 
				WHERE 		`".$identityField->name."` = ?", 
				array($_POST['_id'])	
			);
        
			$this->model->dbConn->Execute("COMMIT");			
			return true;
		}
		
		return false;
	}
}
?>