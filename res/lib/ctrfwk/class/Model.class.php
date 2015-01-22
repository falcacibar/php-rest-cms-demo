<?php	
/**
 * Counter Framework Data Model Object
 *
 * @package CounterFramework
 * @subpackage Data/DB
 * @todo A Multi table reference
 * 
 */
 
 
/*
 *  The Counter Framework Data Model Object
 */

class cfModel {
	public  $dbConnection	= null;
	public  $dbConn			= null;
	public  $DBConnection	= null;	
	
	public	$tablesMap		= array();
	public	$tables			= array();
	
	public	$relations		= array();
	public	$relationsMap	= array();
	
	public	$tableObject	= 'cfModelTable';
	public	$objects;
	
	public final function __construct($tables = null, $dbconnection=null) {		
		if($tables instanceof ADOConnection || $tables instanceof PDO) {
			$dbconnection	= $tables;
			$tables			= null;
		}
		
		if($dbconnection instanceof ADOConnection || $dbconnection instanceof PDO) {			
			$this->dbConnection = $dbconnection;
			$this->dbConn		= &$this->dbConnection;
			$this->DBConnection	= &$this->dbConnection;			
		}
		
		$this->objects = &$this->tables;
		$this->constructor();
		$this->addTables($tables);
	}
	
	public function __set($var, $val) {}
	
	public function constructor() {}

	final public function addTables($tables) {						
		if(is_array($tables)) {				
			while($table = array_shift($tables)) {					
				if(is_array($table) || is_string($table)) {
					$fields	= null;
					
					if(!cfUtil::is_assoc($table) && count($table)) {
						if(count($table) == 2) 
							$fields	= $table[1];
							
						$table	= $table[0];
					}						
						
					$this->addTable($table, $fields);
				}
			}
		}		
		
		return $this;
	}
	
	final public function addTable($table, $fields=null) {
		$idx	= count($this->tables);
		$to		= &$this->tableObject;
		
		if( is_array($table)
			|| (is_object($table) && $table instanceof cfModelTable)
		) {
							
			if(is_array($table)) 
				$table = new $to($this, $table, $fields);
				
			$table->addFields($fields);
			array_push($this->tables, $table);
			
			$this->tablesMap[$table->name] = $idx;
			return $table;
				
		} else {
			/* EMSG */ cfError::create('To the model you can add a cfModelTable/cfModelObject or an array with his parameters.', cfErrorEngine);
			return false;
		}			
	}
	
	final public function addObject($tabledef) {
		return call_user_method('addTable', $this, $tabledef);
	}
	
	/**
	 * Returns a table from model by name.
	 *
	 * @return cfModelTable
	 */
	final public function table($name) {			
		if(isset($this->tablesMap[$name]))
			return $this->tables[($this->tablesMap[$name])];
	}

	/**
	 * @return cfModelTable
	 */				
	final public function getTable($table) {
		return $this->table($table);
	}
	
	public function addRelationSimple($name, $field, $referenceField, $labelField=null) {
		$relation	= new cfModelRelationSimple($name, $field, $referenceField, $labelField, $this);
		$this->addRelation($relation);
		
		return $this;
	} 
	
	public function addRelationManyToMany($name, $firstField, $secondField, $interFirstField, $interSecondField, $labelFirstField=null, $labelSecondField=null) { 
		$relation	= new cfModelRelationSimple($name, $field, $referenceField, $labelField);
		$this->addRelation($relation);
		
		return $this;		
	}
	
	public function deleteRelation($nameOrIndex) {
		$idx 	= (is_int($nameOrIndex) ? $nameOrIndex : $this->relationsMap[$nameOrIndex]);
		$name	= (is_string($nameOrIndex) ? $nameOrIndex : $this->relations[$nameOrIndex]->name);
		
		array_slice($this->relations, $idx, 1);
		unset($this->relationsMap[$name]);
		
		return $this;
	}
	
	private function addRelation(cfModelRelation $relation) {
		$idx		= count($this->relations);
		
		array_push($this->relations, $relation);
		$this->relationsMap[$relation->name] = $idx;		
	}
}



class cfModelTable {
	public		$fields		= array();
	public		$fieldsMap	= array();
	
	public		$name;
	public		$label;
	public		$displayName;
	
	public		$identity;
	
	public		$fieldObject = 'cfModelField';
	public		$model;
	
	protected	$__unsetable_props = array('fields', 'identity', 'model');
	
	public final function __set($var, $val) {			
		if(!in_array($var, $this->__unsetable_props)) {				
			if($var == 'model' && !($val instanceof cfModel)) {
				/* EMSG */ cfError::create('The model of the table %s must be a cfModel class or inherited class from cfModel.', cfErrorEngine);
				return false;					
			}
			
			$this->$var = $val;
		} 			
	}
	
	public final function __construct(cfModel $model, $properties=null, $fields=null) {
		$this->model = $model;
		
		if(!is_array($fields))
			$fields = array();

		$this->displayName = &$this->label;

		if(is_array($properties)) {
			foreach($properties as $property => $value) {
				if($property == 'fields') 
					$fields = array_merge($fields, $value);											
				else
					$this->__set($property, $value);
			}
		}
		
		if(count($fields)) {
			$this->addFields($fields);
			$this->findIdentity();
		}
		
		$this->constructor();
	}
	
	final private function findIdentity() {
		$identityFinded = false;
		
		reset($this->fields);
		while(!is_null($key = key($this->fields))) {
			$field = &$this->fields[$key];
			
			$this->setIdentity($field);
								
			next($this->fields);
		}
		
		return $this;
	}
	
	final private function setIdentity(cfModelField $field) {
		if($field->identity) {
			if($this->identity) { 
				/* EMSG */ cfError::create(sprintf('In the table %s you define more than one field as identity field.', $this->name), cfErrorEngine);
				return false;						 
			}
			
			$this->identity = $field;
		} 		
		
		return $this;
	} 
	public function constructor() {}
	
	public function addFields($fields) {
		if(is_array($fields)) {
			while($field = array_shift($fields)) {
				$this->addField($field);
			}
		}
		
		return $this;
	}
	
	public function addField($field) {
		$idx = count($this->fields);
		$fo	 = &$this->fieldObject;
			
		if( is_array($field)
			|| (is_object($field) && $field instanceof cfModelField)
		) {
			if(is_array($field))					 
				$field = new $fo($this, $field);
			
			array_push($this->fields, $field);
			
			$this->setIdentity($field)
					->fieldsMap[$field->name] = $idx;
			;
		} else {
			/* EMSG */ cfError::create(sprintf('To the table %s you can add an instance of cfModelField or an array with his properties.', $this->name), cfErrorEngine);
			return false;
		}
		
		return $this;
	}
	
	/**
	 * @return cfModelField
	 */
	final public function field($name) {
		return (isset($this->fieldsMap[$name]) ? $this->fields[($this->fieldsMap[$name])] : null);
	}

	/**
	 * @return cfModelField
	 */				
	final public function getField($table) {
		return $this->table($table);
	}
}

class cfModeli extends cfModel { }
class cfModelObject extends cfModelTable { };

/**
 *  @property cfModelValidation $validation
 *  @property cfModelTable $table
 */
class cfModelField {
	public $name;
	public $displayName;
	public $label;
	
	public $type;
	public $default;
	
	public $table;
	public $identity  = false;

	public $validation;
	public $validationObject = 'cfModelValidation';
	
	public $reference		= null;
	
	public $referenceds		= array();
	public $referencedsMap	= array();
	
	public $retrieve	= true; 
	
	public $options;
	
	protected	$__unsetable_props = array('table', 'referenced', 'referencing');
	
	public function __set($var, $val) {
		if(!in_array($var, $this->__unsetable_props)) {
			if(property_exists($this, $var)) {
				if($var == 'validation' && !(is_null($val) || $val instanceof cfModelRelation || $val instanceof cfModelValidation)) {
			 		/* EMSG */ cfError::create("The property validation for a field must be an instance of cfModelValidation or cfModelRelation, or an inherited instance of them.", cfErrorEngine);
					return false;
				} 
								
				$this->$var = $val;
			}
		}			
	}

	final public function __toString() {
		return $this->name;
	}

	final public function __construct(cfModelTable $table, $properties) {
		$this->table		= $table;
		$this->displayName	= &$this->label;
		
		foreach($properties as $property => $value) {
			if($property == 'validation') {
				if(is_array($value)) {
					$vo		= &$this->validationObject;
					$value = new $vo($this, $value);
				}
			}
				
			$this->__set($property, $value);
		}
	}

	private function addReferencedAndReference(cfModelRelation $relation, $type) {		
		$prop 		= (($type == 0) ? 'referenceds' : 'references');		
		$propMap	= $prop.'Map';

		$idx = count($this->$prop);		
		$this->$propMap[$relation->name] = $idx;
		array_push($this->$prop, $relation);		
	}
	
	private function deleteReferencedAndReference($nameOrIndex, $type) {		
		$prop 		= (($type == 0) ? 'referenceds' : 'references');		
		$propMap	= $prop.'Map';
		
		$idx	= (is_int($nameOrIndex) ? $nameOrIndex : $this->$propMap);
		$name	= (is_int($nameOrIndex) ? $this->$prop[$idx]->name : $nameOrIndex);
		
		array_slice($this->$prop, $idx, 1);
		unset($this->propMap[$name]);
	}	
	
	public function addReferences($relations) {
		while($relation = array_shift($$relations)) {
			$this->addReference($relation);	
		}
		
		return $this;
	}
	
	public function addReference(cfModelRelation $relation) {
		// $this->addReferencedAndReference($relation, 1);
		$this->reference = $relation;
		return $this;
	}
	
	public function deleteReference($nameOrIndex) {
		// $this->deleteReferencedAndReference($nameOrIndex, 1);
		$this->reference = null;
		return $this;
	}
	
	public function reference($nameOrIndex=null) { /*
		if(is_int($nameOrIndex) && isset($this->references[$nameOrIndex]))
			return $this->references[$nameOrIndex];
		else if(isset($this->referencesMap[$nameOrIndex]))
			return $this->references[($this->referencesMap[$nameOrIndex])]; */
			
			return $this->reference;
	}

	public function addReferenceds($relations) {
		while($relation = array_shift($$relations)) {
			$this->addReference($relation);	
		}
		
		return $this;
	}
	
	public function addReferenced(cfModelRelation $relation) {
		$this->addReferencedAndReference($relation, 0);
		return $this;
	}

	public function deleteReferenced($nameOrIndex) {
		$this->deleteReferencedAndReference($nameOrIndex, 0);
		return $this;
	}
	
	public function referenced($nameOrIndex) {
		if(is_int($nameOrIndex) && isset($this->referenceds[$nameOrIndex]))
			return $this->referenceds[$nameOrIndex];
		else if(isset($this->referencedsMap[$nameOrIndex]))
			return $this->referenceds[($this->referencedsMap[$nameOrIndex])];		
	}
	
	public function constructor() {}
}

class cfModelValidation {
	public		$null			= true;
	public		$void;
	public		$nullConverts		= array( '', 'NULL','null',null);
	public		$nullValue		= null;
	public		$nullConvert		= false;
	public		$boolTrueConverts	= array(
							true, 'true', 'True', 'TRUE','1',1,'on','On','ON','yes','YES','Yes'
							, 'Y', 'y'
						);
	public		$boolFalseConverts	= array(
							false, 'false', 'False', 'FALSE','0',0,'off','Off','OFF','no','NO'
							,'No','n','N',''
						);
	public		$boolTrue		= '1';
	public		$boolFalse		= '0';
	public		$dateFormat		= '%Y-%m-%d';
	public		$dateTimeFormat		= '%Y-%m-%d %H:%M:%S';
	public		$pcre			= null;
	public		$maxLength		= 0;
	public		$trim			= true;
	public		$numeric		= false;
	public		$date			= false;
	public		$dateTime		= false;
	public		$bool			= false;
	public		$errorType		= cfError;
	public		$errors;
	
	public		$errorMessages		= array(
							'null' 		=> 'The field %s is empty.',
							'pcre' 		=> 'The field %s is not valid.',
							'maxLength'	=> 'The field %s exceeds the max lenght of %d characters.',
							'numeric'	=> 'The field %s is not numeric.',
							'date'		=> 'The field %s does not contains a valid date.',
							'dateTime'	=> 'The field %s does not contains a valid date and time.'
						);
	public		$field;
	
	public		$__ignore_validate_props	= array(
								'__ignore_validate_props',
								'__unsetable_props',
								'field',
								'void',
								'trim',
								'dateFormat',
								'dateTimeFormat',
								'errorMessages',
								'errorType',
								'errors',
								'boolTrue',
								'boolFalse',
								'boolTrueConverts',
								'boolFalseConverts',
								'nullConverts',
								'nullValue'
	);	
	
	protected	$__unsetable_props 		= array(
								'__unsetable_props', 
								'__ignore_validate_props',
								'field'
	);
	
	final public function __set($var, $val) {
		if(
			!in_array($var, $this->__unsetable_props) 
			&& property_exists($this, $var)
		) {	
			if($var == 'maxLength') {
				if((int) $val < 1 )		$val = 0;
				else					$val = (int) $val; 
			} elseif($var == 'errors') {
				if(!$val instanceof cfErrorHandler) {
					/* EMSG */ cfError::create("If you want to add a custom errorHandler, this must be a ctrErrorHandler class or inherit class.", cfErrorEngine);
					return false;
				}
			}
			
			$this->_set($var, $val);				
		}
	}
	
	protected function _set($var, $val) {			
		$this->$var = $val;
	}
	
	final public function __construct(cfModelField $field, $properties) {			
		$this->field	= $field;
		$this->void		= &$this->null;
		
		if(is_array($properties)) {					
			foreach($properties as $property => $value) {						
				if(!in_array($property, $this->__unsetable_props)) {											
					$this->__set($property, $value);
				}
			}
		}
			
		$this->constructor();
		
		if(is_null($this->errors))
			$this->errors = new cfErrorHandler();			
	}
	
	public function constructor() {}
	
	public function proccess(&$value) {
		foreach($this as $type => $xD) {
			if(!in_array($type, $this->__ignore_validate_props)) {
				$method = 'validate'.ucfirst($type);

				if($this->trim) cfUtil::reftrim($value);	
				$this->{$method}($value);
			}
		}
	}
	
	public function registerError($type, $other_params=array()) {
			if(!is_array($other_params)) $other_params=array();

			$this->errors->register(call_user_func_array('sprintf',array_merge( array(
											$this->errorMessages[$type],
											$this->field->displayName),
											$other_params
							)
			), $this->errorType);
	}
	
	public function validatePcre($value) {
		if($this->pcre) {
			if(!preg_match($pcre, $value))	{
				$this->registerError('pcre');
				return false;
			}
		}
		return true;
	}
	
	public function validateNull($value) {						
		if($this->null) {				
			if($value == '' || $value == null)	{					
				$this->registerError('null');
				return false;
			} 
		}			
		return true;			
	}
	
	public function validateNullConvert(&$value) {
		if($this->nullConvert) {
			if(in_array($value, $this->nullConverts)) 
				$value = $this->nullValue;
			return true;
		}
			
	}

	public function validateBool(&$value) {
		if($this->bool) {	
			if(in_array($value, $this->boolTrueConverts, true)) 
				$value = $this->boolTrue;
			elseif(in_array($value, $this->boolFalseConverts, true)) 
				$value = $this->boolFalse;
    
			return true;
		}
			
	}

	public function validateMaxLength($value) {
		if($this->maxLength > 0) {
			if(strlen($value) > $this->maxLength)	{
				$this->registerError('maxLength', array($this->maxLength));
				return false;
			} 
		}			
		return true;
	}
	
	public function validateNumeric($value) {
		if($this->numeric) {
			if(preg_match('/[\D]/', $value))	{
				$this->registerError('numeric');
				return false;
			} ;
		}			
		return true;
	}
	
	public function validateDate(&$value) {
		if($this->date) {
			$date = strtotime($value);
			if($date) {
				$value = strftime($this->dateFormat, $date);
				return true;
			} else return false;	
		}
	}
	
	public function validateDateTime(&$value) {
		if($this->dateTime) {
			$date = strtotime($value);
			if($date) {
				$value = strftime($this->dateTimeFormat, $date);
				return true;
			} else return false;
		}
	}		
}

class cfModelRelation {
	public $model;
	public $name;

	public function proccess(&$value) {
		if($this->acceptNull) {
			$vo			= &$this->field->validationObject;
			$validator	= new $vo($this->field, array());

			$validator->nullConvert = true;					
			$validator->validateNullConvert($value);
		}
		return true;
	}
	
	/**
	 *  Return the field
	 * 
	 *  @return cfModelField
	 * 
	 */
	protected function getField($field) {
		if(is_string($field)) {				
			if(!is_null($this->model) && ($this->model instanceof cfModel)) {
				$defs = explode('.', trim($field));
				if(
					!is_null($this->model->table($defs[0]))
					&& !is_null($this->model->table($defs[0])->field($defs[1]))
				) 
					$field = $this->model->table($defs[0])->field($defs[1]);
			} 
		}
		 	
		
	 	if(($field instanceof cfModelField )) {
	 		if($field->table instanceof cfModelTable) {
		 		return $field;
	 		} else {
		 		/* EMSG */ cfError::create("For a model simple relationship you need to add an Object that be inherited from cfModelField and it is related with a table/object.", cfErrorEngine);
				return null;		 			
	 		}
		} else {
		 	/* EMSG */ cfError::create("For a model simple relationship you need to add an Object that be inherited from cfModelField class or a string like table.field .", cfErrorEngine);
			return null;
		}
	}
	
	public function __set($var, $val) {
		if($var == 'model') {
			$this->setModel($val);
		} elseif(
			property_exists($this, $var)
			&& strtolower(substr($var, -5)) == 'field'
		) {
			$this->$var = (is_null($val) ? null : $this->getField($val));
		}
	}		
}

/**
 *  Simple Relationship Model Class
 * 
 *  @property field cfModelField
 */
class cfModelRelationSimple extends cfModelRelation {		
	public $field;
	public $referenceField;
	public $labelField;
	
	public $nullable;
	
	public function __construct($name, $field, $referenceField, $labelField=null, cfModel $model=null) {
		$this->name					= $name;
		$this->model				= $model;
		
		$this->field 				= $this->getField($field);
		$this->referenceField		= $this->getField($referenceField);
		
		$this->__set('labelField', $labelField);
		
		$this->nullable				= &$this->field->validation->validateNull;
		$this->field->validation	= clone $this->referenceField->validation;
		
		$this->field->validation->null	= &$this->nullable;	 
		
		$this->field->addReference($this);
		$this->referenceField->addReferenced($this);
	}
}

class cfModelRelationManyToMany extends cfModelRelation {
	public $firstField; 
	public $secondField;
	public $interFirstField;
	public $interSecondField;
	public $labelFirstField;
	public $labelSecondField;
					
	public function __construct($name, $firstField, $secondField, $interFirstField, $interSecondField, $labelFirstField=null, $labelSecondField=null, cfModel $model=null) {
		$this->model				= $model;
		$this->name					= $name;
		
		$this->firstField			= $this->getField($firstField);
		$this->secondField			= $this->getField($secondField);
		
		$this->__set('labelFirstField', $labelFristField);
		$this->__set('labelSecondField', $labelSecondField);
		 
		$this->field 				= &$this->firstField;		
		
		$this->field->addReference($this);
		$this->referenceField->addReferenced($this);		
	}
	
}


?>
