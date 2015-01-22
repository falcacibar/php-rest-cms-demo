<?php
/**
 * 
 *  @todo __FieldsToBeProcessed, __FieldsToBeProcessedCount, __CurrentTableUsed
 *  by cfdb static method
 * 
 */
class cfdbMockFields  {
	public $type				= cfdb::fieldsSelect;
	public $currentTable		= null;
	public $parent				= null;
	public $fields				= array();
	
	public $__CurrentTableUsed;
	public $__FieldsToBeProcessed;

	public function __construct(cfdbFields $from=null, $fields=false ) {
		if(!is_null($from)) {
			$this->type			= $from->type;
			$this->paernt		= $from->__FieldsToBeProcessed;
			$this->currentTable	= $from->__CurrentTableUsed;
			
			$this->__CurrentTableUsed		= &$this->currentTable;
			$this->__FieldsToBeProcessed	= &$this->fields;

			if($fields) {
				$this->fields = $fields;				
			}
		}
	}
}

/**
 * 
 * @author falcacibar
 * @property ftSQLQuery __FieldsToBeProcessed
 * 
 */
class cfdbFields{
	public $type				= cfdb::fieldsSelect;
	public $currentTable		= null;
	public $fields				= array();
	public $parent				= null;
	public $count				= 0;
	
	public function __construct($parent, $type=cfdb::fieldsSelect) {
		$this->parent	= $parent;	
		$this->type		= $type;
	}
	 
	public function __get($prop) {
		if($prop === '_pb' || $prop === '_pe' ) {
			$this->__call($prop, array());			
		}

		if($prop === '__FieldsToBeProcessed')  			 
			return $this->fields;
		if($prop === '__FieldsToBeProcessedCount')  			 
			return $this->count;			
		else if($prop === '__CurrentTableUsed')
			return $this->currentTable;			
		else if($prop === '_') { 
			/*
			if($this->type === self::typeSelect && !is_null($this->currentTable) && $this->currentTable && !count($this->fields)) 
					array_push($this->fields, array($this->currentTable, null));	
			*/
			return $this->parent;
		} else {
			if($this->type === cfdb::fieldsSelect && !is_null($this->currentTable) && $this->currentTable != $prop) {
				$usedTable = false;
				
				foreach($this->fields as $field) {					
					if($field[0] == $this->currentTable) {
						$usedTable = true;
						break;
					} 
				}
				
				if(!$usedTable) {
					array_push($this->fields, array($this->currentTable, null));
					$this->count++;	
				}
			}
			
			$this->currentTable = $prop;			
			return $this;
		}
	}
	
	public function __set($p, $v) {
		if($p == '__CurrentTableUsed')
			$this->currentTable = $v;
			
		return false;
	}
	
	public function __call($field, $args) {
		$c = count($args);
		
		if($c > 0 && $this->type == cfdb::fieldsWhere) {
			if($c < 2) {
				cfError::create(
					/* EMSG */				"To made a where assertion i need a operator and a value."
											, cfErrorEngine
				);
				
				return false;
			} elseif($c>2) {
				$logical	= 
				$arg		= null;
					
				for($i=2;$i<4;$i++) {					
					if(isset($args[$i])) {						
						$arg		= array_pop($args);
						$logical	= trim(strtoupper($arg));
						
						$logical	= (($logical === 'OR' || $logical === 'XOR' || $logical === 'AND') ? $logical : null);
						$arg		= null;
					}	
				}
				
				array_unshift($args, $arg);
					
				if(!is_null($logical))
					array_push($args, $logical);
			} else 
				array_unshift($args, null);

		}
			
		array_unshift($args, $field);
		array_unshift($args, $this->currentTable);
		array_push($this->fields, $args);
		$this->count++;
		
		return $this;
	}
	
	public function __toString() {
		if(!is_null($this->parent)) {			
			return (string) $this->parent;
		}
	}
}

class cfdbQueryableStack {
	public function &___getter_($prop) {
		if($prop == 'stack')
			return $this->stack;
	elseif($prop == 'parent')
			return $this->parent;
	}
	
	public $parent	= null;
	public $stack	= array();
	
	public function select($alias=null) {
		if($alias instanceof cfdbQuery) {
			array_push($this->stack, $alias);
			$alias->parent = $this->parent;						
			return $alias;
		} else {
			array_push($this->stack, $r = cfdb::query($this->parent));		
			return $r->select($alias);			
		}
	}
	
	public function insert($alias=null) {
		array_push($this->stack, $r = cfdb::query($this->parent));
			return $r->delete($alias);		
	} 
	
	public function update($alias=null) {
		array_push($this->stack, $r = cfdb::query($this->parent));
		return $r->update($alias);				
	}

	public function delete($alias=null) { 
		array_push($this->stack, $r = cfdb::query($this->parent));
		return $r->delete($alias);		
	}
}

class cfdbPropStack extends cfdbQueryableStack {	
	public $parent	= null;
	public $stack	= array(); 
	
	/**
	 * 
	 * @return array
	 */
	public function stack($stack=null) {
		if(!is_null($stack) && is_array($stack)) {			
			$this->stack = &$stack;		
		}
		
		return $this->stack;
	}
	
	public function __construct($parent) {
		$this->parent = $parent;
	}
	
	public function __get($prop) {
		if($prop != '_') {
			array_push($this->stack, $prop);
			return $this;
		} else return $this->parent;
	}
}

class cfdbQuery extends stdClass {
	public function quoteString($string) {
		if($s=is_object($string))
			$string	= (string) $string;
			
		if($s || is_string($string))
			return $this->quoteString.str_replace($this->quoteString, $this->quoteStringEscape, $string).$this->quoteString;
		else return $string;
	}
	
	public $quoteStrings		= false;
	public $blobEscapeStrings	= false;
	public $__Exporting			= false;

	public $currentProcesss		= '';
	public $command				= null;	
	
	/**
	 * 
	 * @var cfdbFields
	 */
	public $fields			= null;
	public $values			= null;
	public $order			= null;
	public $group			= null;
	public $raw				= array();	
	
	/**
	 * 
	 * @var cfSimpleGetStack
	 */
	public $tables			= null;
	public $from			= null;
	public $tablesByFields	= array();
	public $alias			= null;
	public $parent			= null;
	public $innerJoin		= array();
	public $leftJoin		= array();
	public $rightJoin		= array();
	public $fullJoin		= array();
	public $crossJoin		= array();
	public $naturalJoin		= array();	
	public $where			= null;
	public $union			= null;	
	
	private $limit			= null;
	private $skip			= null;
	
	public $_				= null;
	
	public $nullValue		= null;
	private $driver			= null;
	
	public function __construct($parent=null) {
		$this->driver		= cfdb::driver($this);
		$reflector			= new ReflectionClass('cfdb_'.$this->driver);
		
		foreach($reflector->getConstants() as $prop => $value) {
			$this->$prop = $value;
			$prop = substr($prop, 0, 1).strtolower(preg_replace('/[a-z]/smx', '', $prop));
			$this->$prop = $value;
		}
		
		$this->tables		= new cfdbPropStack($this);
		$this->naturalJoin	= new cfdbPropStack($this);
		$this->crossJoin	= new cfdbPropStack($this);
		
		$this->parent	= $parent; 
		$this->_		= &$this->parent;
		
		$this->from		= &$this->tables;
		$this->fields	= new cfdbFields($this);
		$this->where	= new cfdbFields($this, cfdb::fieldsWhere);
		$this->group	= new cfdbFields($this, cfdb::fieldsParam);
		$this->order	= new cfdbFields($this, cfdb::fieldsParam);
		
		array_push($this->innerJoin, new cfdbFields($this, cfdb::fieldsJoin));
		
		return $this->innerJoin;
	}

	public function quoteStrings($switch) {
		$this->quoteStrings			= $switch;
		$this->blobEscapeStrings	= !$switch; 
		
		return $this;
	}	

	public function blobEscapeStrings($switch) {
		$this->blobEscapeStrings	= $switch;
		$this->quoteStrings			= !$switch;

		return $this;		
	}	
	
	public function alias($alias) {
		$this->alias	= $alias;
		
		return $this;
	}
	
	public function	select($alias=null) {
		$this->alias	= $alias;
		
		$this->command	= 'SELECT';
		$this->fields	= new cfdbFields($this);
		
		return $this->fields;			
	}

	public function	insert($alias=null) {
		$this->alias	= $alias;
		
		$this->command	= 'INSERT';
		$this->fields	= new cfdbFields($this);
		
		return $this->fields;			
	}
	
	public function	update($alias=null) {
		$this->alias	= $alias;
		
		$this->command	= 'UPDATE';
		$this->fields	= new cfdbFields($this);
		
		return $this->fields;			
	}
		
	public function	delete($alias=null) {
		$this->alias	= $alias;
		$this->command	= 'DELETE';
		
		return $this;			
	}	
	
	public function from() {		
		return $this->tables;
	}
		
	public function	where() {		
		$this->where	= new cfdbFields($this, cfdb::fieldsWhere);
		
		return $this->where;			
	}
	
	public function end() {
		return ((!is_null($this->parent)) ? $this->parent : $this);
	} 

	private function join($type) {
		$type = strtolower($type);
		$prop = $type.'Join';
		
		if($type === 'cross' || $type === 'natural') {
			$this->$prop = new cfdbPropStack($this);
			return $this->$prop;
		} else { 
			array_push($this->$prop, ($f = new cfdbFields($this, cfdb::fieldsJoin)));
			return $f;
		}
	}
	
	public function top($n) {
		return $this->limit($n);
	}
	
	public function fetchFirst($n) {
		return $this->limit($n);
	}
	
	public function first($n) {
		return $this->limit($n);
	}
	
	public function offset($n) {
		return $this->skip($n);
	}
	
	public function skip($n) {		
		if(is_null($n) || ((int) $n > 0)) {
			$this->skip = (int) $n;
		}
		
		return $this;
	}
	
	public function limit($n) {
		if(is_null($n) || ((int) $n > 0)) {
			$this->limit = (int) $n;
		}
		
		return $this;		
	} 

	public function crossJoin() {
		return $this->join('cross');
	}
	
	public function naturalJoin() {
		return $this->join('natural');
	}
		
	public function innerJoin() {
		return $this->join('inner');
	}
	
	public function leftJoin() {
		return $this->join('left');
	}	

	public function rightJoin() {
		return $this->join('right');
	}	

	public function fullJoin() {
		return $this->join('full');
	}
	
	public function order() {
		$this->order = new cfdbFields($this, cfdb::fieldsParam);
		
		return $this->order;
	}	
	
	public function group() {
		$this->group = new cfdbFields($this, cfdb::fieldsParam);
		
		return $this->group;
	}	
	
	public function raw($raw) {
		array_push($this->raw, $raw);
		return $this;
	}
	
	public function union() {
		$c = 'cfdbQuery_'.strtolower($this->driver);		
		$this->union = new $c($this);
		return $this->union;
	}
	
	private static function _skip(cfdbQuery $cfdbQuery, &$sql) {
		if(!is_null($cfdbQuery->skip) && ((int) $cfdbQuery->skip) > 0)
			$sql	.= $cfdbQuery->skipCmd.' '.$cfdbQuery->skip.' ';		
	}

	private static function _limit(cfdbQuery $cfdbQuery, &$sql) {
		if(!is_null($cfdbQuery->limit) && ((int) $cfdbQuery->limit) > 0)
			$sql	.= $cfdbQuery->limitCmd.' '.$cfdbQuery->limit.' ';
	}
		
	public function __toString() {
		if(($this->parent instanceof cfdbQuery) && !$this->__Exporting) {
			$this->__Exporting = true;
			$sql = (string) $this->parent;
			$this->__Exporting = false;
			return $sql;
		} else {
			if(!$this->command) {
				cfError::create(
				/* EMSG */				"Without sql main command"
										, cfErrorEngine
				);
				
				return false;
			}
			
			$this->currentProcess = $this->command;
			$sql	= $this->command . ' ';
						
			($this->limitCmdPos === cfdb::CmdPosAfterSelect) && self::_limit($this, $sql);
			($this->skipCmdPos === cfdb::CmdPosAfterSelect) && self::_skip($this, $sql);
					
			$first	= true;
	
			$sql	.= $this->fieldsToString($this->fields);
			
			if(is_null($this->tables) && !count($this->tablesByFields)) {			 
				cfError::create(
				/* EMSG */				"Cannot figure a table to get the fields"
										, cfErrorEngine
				);
				
				return 'NULL';
			}
			
			foreach(array('inner', 'left', 'right') as $join) {
				if($c = count($this->{$join.'Join'})) {
					for($i=0;$i<$c;$i++) {
						$joinFields = $this->{$join.'Join'}[$i]->__FieldsToBeProcessed;
						foreach( $joinFields as $joinField) {				
							if($idx = array_search($joinField[0], $this->tablesByFields))
								array_splice($this->tablesByFields, $idx, 1);
						}
					}
				}
			}
			
			if(!count($tables = $this->tables->stack()))
				$tables = $this->tables->stack($this->tablesByFields);
			
			$this->currentProcess = 'FROM';
			$sql .= ' FROM ';
			
			foreach($tables as $table) {				
				if($first)		$first = false;
				else			$sql .= $this->sepField;
				
				if($table instanceof cfdbQuery) {	
					if(!$this->parent && !$table->__Exporting)
						$table->__Exporting = true;
																
					$sql .= $this->nestedStart.((string) $table).$this->nestedEnd.' '.$table->alias;					
					$table->__Exporting = false;
				} else
					$sql .= $this->quoteFieldStart.$table.$this->quoteFieldEnd; 
			}			

			foreach(explode(',', 'natural,cross') as $joinType) {
				$prop		= $joinType.'Join';
				
				if(count($tables = $this->$prop->stack())) {
					$sqlJoin	= strtoupper($joinType).' JOIN';
					
					foreach($tables as $table) {
						$sql .= ' '.$sqlJoin.' ';
						
						if($table instanceof cfdbQuery) {
							if(!$this->parent && !$table->__Exporting)
								$table->__Exporting = true;
							
							$sql .= $this->nestedStart.((string) $table).$this->nestedEnd.' '.$table->alias;
							$table->__Exporting = false;
						} else
							$sql .= $this->quoteFieldStart.$table.$this->quoteFieldEnd; 
					}
				}  					
			}
			
			foreach(explode(',', 'inner,left,right,full') as $joinType) {
				$prop		= $joinType.'Join';

				if($c = count($this->$prop)) {					
					$sqlJoin	= strtoupper($joinType).(($joinType == 'inner') ? '' :  ' OUTER').' JOIN';
					$this->currentProcess = $sqlJoin;
					
					for($i=0;$i<$c;$i++) {						
						$joinStack	= &$this->$prop;
						if($joinFields	= $joinStack[$i]->__FieldsToBeProcessedCount) {
							$joinFields	= $joinStack[$i]->__FieldsToBeProcessed;
							
							foreach( $joinFields as $joinField) {				
								if($idx = array_search($joinField[0], $this->tablesByFields))
									array_splice($this->tablesByFields, $idx, 1);
							}
							
							$sql .= ' '.$sqlJoin.' '.$this->fieldsToString($joinStack[$i]);
						}
					}
				}
			}	
			
			if($this->where->__FieldsToBeProcessedCount) {
				$this->currentProcess = 'WHERE';
				$sql .= ' WHERE '.$this->fieldsToString($this->where);			
			}
			
			if($this->group->__FieldsToBeProcessedCount) {
				$this->currentProcess = 'GROUP BY';
				$sql .= ' GROUP BY '.$this->fieldsToString($this->group);
			}
			
			if($this->order->__FieldsToBeProcessedCount) {
				$this->currentProcess = 'ORDER BY';
				$sql .= ' ORDER BY '.$this->fieldsToString($this->order);
			}

			($this->limitCmdPos === cfdb::CmdPosAtEnd) && self::_limit($this, $sql);
			($this->skipCmdPos === cfdb::CmdPosAtEnd) && self::_skip($this, $sql);			
			
			if(!is_null($this->union)) {
				# if(!$this->parent && !$this->union->__Exporting)
				$this->union->__Exporting = true;
												
				$this->currentProcess = 'UNION ALL';

				$sql .= ' UNION ALL '.((string) $this->union);
				$this->union->__Exporting	= false;										
			}
						
			return $sql.join(' ', $this->raw);	
		}
	}
	
	/**
	 * @todo clean up this mess
	 */
	public function fieldsToString($fields) {		
		$sql		= '';		
		$fieldList	= $fields->__FieldsToBeProcessed;

		// Schema SCHEMA__TABLE
		if(preg_match('/^(.*?)(__)/sxm', $fields->__CurrentTableUsed)) {			
			$fields->__CurrentTableUsed = preg_replace('/^(.*?)(__)/', '$1'.$this->quoteFieldEnd.$this->refSchemaTable.$this->quoteFieldStart, $fields->__CurrentTableUsed);
		}
		
		// Just one table without fields, select all
		if((!($fieldCount = count($fieldList)) && $fields->__CurrentTableUsed) && $fields->type == cfdb::fieldsSelect) {
			array_push($this->tablesByFields, $fields->__CurrentTableUsed);			
			return $this->quoteFieldStart.$fields->__CurrentTableUsed.$this->quoteFieldEnd.$this->refTableColumn.$this->allTable;
		}				
		
		$first 			= true;
		$currentTable	= '';
		$pair			= true;
		
		// fields
		for($fieldIndex=0;$fieldIndex<$fieldCount;$fieldIndex++) {		
			$field			= &$fieldList[$fieldIndex];
			$pair			= !$pair;
			
			// concat separator if not first and is select or param
			if($first)
				$first = false;
			elseif($fields->type === cfdb::fieldsSelect || $fields->type === cfdb::fieldsParam)
				$sql .= $this->sepField;		
			
			// If is a pair field in join field list. If not, mock a select fields
			if($fields->type !== cfdb::fieldsJoin || ($fields->type === cfdb::fieldsJoin && !$pair)) {								
				if(is_null($field[1])) {					
					$field[1] = $this->allTable;
					$isalltable	= true;
				}

				if(
					$fields->type === cfdb::fieldsWhere 
					&& isset($field[3]) 
					&& is_string($field[3]) 
				) {
						if($field[4] === $this->nullValue) {
							if(trim($field[3]) === eq) 
								$field[3] = 'IS';
							elseif(trim($field[3]) === ne)
								$field[3] = 'IS NOT';

							$field[4] = 'NULL';
						}				
				}
								
				if((isset($field[2]) && $field[2] !== false) || !isset($field[2]) || $fields->type !== cfdb::fieldsJoin ) {
					// if not parenthesis begin and parenthesis end _pb and _pe				
					if(($field[1] != '_pb' && $field[1] != '_pe') && !is_null($field[0])) {
						
						// find schemas SCHEMA__TABLE
						if(preg_match('/^(.*?)(__)/sxm', $field[0])) {
							$field[0] = preg_replace('/^(.*?)(__)/', '$1'.$this->quoteFieldEnd.$this->refSchemaTable.$this->quoteFieldStart, $field[0]);
						}
						
						if(!in_array($field[0], $this->tablesByFields))
							array_push($this->tablesByFields, $field[0]);
						
						// Set the table with field to the first param	 
						$field[1] = $this->quoteFieldStart.$field[0].$this->quoteFieldEnd.$this->refTableColumn.(isset($isalltable) ?  $this->allTable : $this->quoteFieldStart.$field[1].$this->quoteFieldEnd );
						
						// Concat "table ON"
						if($fields->type == cfdb::fieldsJoin) {
							$currentTable 	= $field[0];
							$sql 			.= $this->quoteFieldStart.$field[0].$this->quoteFieldEnd.' ON ';
						}						
					} elseif($fields->type === cfdb::fieldsJoin) {
						cfError::create(
							/* EMSG */			"Cannot made a join without a table"
												, cfErrorEngine
						);
						
						return 'NULL';
					}
				}

				if(isset($field[2])) {
					if(is_string($field[2])) {
						if(
							($fields->type === cfdb::fieldsJoin || $fields->type === cfdb::fieldsParam) && isset($field[3])	
							|| ($fields->type !== cfdb::fieldsJoin && $fields->type !== cfdb::fieldsParam) 
							|| (
									($fields->type === cfdb::fieldsJoin && $fields->type !== cfdb::fieldsParam) 
									&& isset($field[3])
							)
						) {
							$field[1] = $this->functionString($field[2], $field[1]); 	
						} 
						
						if($fields->type === cfdb::fieldsJoin && isset($field[3]) && is_bool($field[3]))
							$field[1] .= ' '.$field[3];
					} 
				}
				
				if($fields->type === cfdb::fieldsSelect && isset($field[3]) && is_string($field[3])) {
						$field[1] .= ' AS '.$this->quoteFieldStart.$field[3].$this->quoteFieldEnd;								
				}
				 				
				if($fields->type === cfdb::fieldsWhere && isset($field[4])) {
					$field[4] = $this->value($field[4]);
					
					if(is_array($field[4])) {
						 $field[3] = (($field[3] == '=') ? 'IN' : (($field[3] == '<>' || $field[3] == '!=') ? 'NOT IN' : $field[3]));
						
						 if($this->quoteString && substr($field[3], -2) == 'IN' ) {						 	
						 	for($c=count($field[4]), $i=0;$i<$c;$i++) 
								$field[4][$i] = $this->quoteString($field[4][$i]); 
						 }
						 
						 $field[4] = '('.join(',', $field[4]).')';	
					}					
					
					if(is_object($field[4]) && (
						$field[4] instanceof cfdbQuery 
						|| $field[4] instanceof cfdbFields 
						|| $field[4] instanceof cfdbPropStack
					)) {
						$field[4] = $this->nestedStart.((string) $field[4]).$this->nestedEnd;
					} elseif($this->quoteStrings && $field[3] !== 'IS' && $field[3] !== 'IS NOT') {
		 				$field[4] = $this->quoteString($field[4]);
					} 
					
					
				}

				// parenthesis begin and end _pb and _pe
				if(
					substr($field[1], 0, 2) === '_p'  
					&& (
						$fields->type === cfdb::fieldsSelect
						||  $fields->type === cfdb::fieldsWhere
					) 
				){
					if($field[1] == '_pb')
						$sql .= ' ( ';
					else if($field[1] == '_pe') {
						$sql .= ' ) ';
						
						if(isset($field[2]) && !empty($field[2]))
							$sql .= ' AS '.$field[2];
					}
				} elseif($fields->type === cfdb::fieldsWhere) {
					if(isset($field[3]) && $field[3] === 'IS NOT' || !isset($field[3]{2}))
						$sql .= $field[1];
					
					if(isset($field[3]) && isset($field[4])) 
						$sql .= ' '.$field[3].' '.$field[4];
					
					if(isset($field[5]))
						$sql .= ' '.$field[5].' ';					
				} else $sql .= $field[1];
			
				if(($fields->type === cfdb::fieldsJoin || $fields->type === cfdb::fieldsParam) && (isset($field[2]) || isset($field[3])))
					$sql .= ' '.$field[(isset($field[3]) ? 3 : 2)].' ';
			} else {
				$mockFields			= new cfdbMockFields($fields);
				$mockFields->type 	= cfdb::fieldsSelect;
				
				array_push($mockFields->fields, $field);				

				$sql	.= $this->fieldsToString($mockFields);
				
				if(($fieldIndex + 1)<$fieldCount)
					$sql .= ' '.$this->currentProcess.' ';
					
				unset($mockFields);
			} 			
		}
		
		return $sql;
	}
	
	public function value(&$value) {		
		return $value;
	}
	
	public function functionString($string, $field) {
		$string	= trim($string);
		
		if($string == 'null' || $string == 'NULL') {
			$out = &$string;
		} else {
			$bm		= 1;
			$l		= strlen($string);
	
			if($l > $bm) {		
				$bl 	= 6;
				
				$i		= 
				$s		= 0;
				
				$b 		= 
				$out	= '';
				
				$si		= true;
						
				while(($si = $i<$l) || (!$si && $s)) {					
					if($si) $b .= $string{$i};
					
					if($i>=$bm) {				
						if($s>$bl || !$si) {
							$out	.= substr($b, 0, 1);
							$b		= substr($b, 1);
							$s--;
						}					
		
						$tout = '';
						
						if(substr($b, 0, 2) === '\\\\') { 
							$out .= '\\';
							$b		= substr($b, 2);
							$s		-= 2;
						} elseif($b === '\:field') {
							$out .= ':field';
							$b		= '';
							$s		= 0;
						} elseif($s >= 6 && substr($b, 0, 6) === ':field') {
							$out	.= $field;
							$b		= substr($b, 6);
							$s		-= 6;
						}
					}
					
					if($si) { 
						$i++;
						$s++; 
					}
				}
			} else {
				$out = &$string;
			}
			
			if(!preg_match('/[\(\)\s]/sm', $out)) 
				$out	= $out.'('.$field.')';
		}			
		
		return $out;			
	}	 
}

class cfdb_iso {
	const quoteFieldStart		= '"';
	const quoteFieldEnd			= '"';
	const quoteString			= "'";
	const quoteStringEscape		= "''";
	const refTableColumn		= '.';
	const refSchemaTable		= '.';
	const sepField				= ',';
	const nestedStart			= '(';
	const nestedEnd				= ')';
	const allTable				= '*';
	const currentSchema			= 'CURRENT_SCHEMA()';
	
	const limitCmdPos			= cfdb::CmdPosAtEnd;	
	const limitCmd				= 'SKIP';
	
	const skipCmdPos			= cfdb::CmdPosAtEnd;	
	const skipCmd				= 'FECTH FIRST';	
	const skipCmdHack			= false;		
}

class cfdbQuery_iso extends cfdbQuery {

}



class cfdb_mssql extends cfdb_iso {
	const quoteFieldStart		= '[';
	const quoteFieldEnd			= ']';
	const currentSchema			= 'SCHEMA_NAME()';
	
}

class cfdbQuery_mssql extends cfdbQuery_iso {
}


class cfdb_mysql extends cfdb_iso{
	const quoteFieldStart		= '`';
	const quoteFieldEnd			= '`';
	const currentSchema			= 'schema()';	
	
	const limitCmdPos			= cfdb::CmdPosAtEnd;
	const limitCmd				= 'LIMIT';	
	
	const skipCmdPos			= cfdb::CmdPosAtEnd;
	const skipCmd				= 'OFFSET';	
}

class cfdbQuery_mysql extends cfdbQuery_iso {
}


class cfdb {
	const fieldsSelect			= 1;
	const fieldsWhere			= 2;
	const fieldsUpdate			= 3;
	const fieldsInsert			= 3;
	const fieldsDelete			= 4;
	const fieldsJoin			= 5;
	const fieldsParam			= 6;

	const CmdPosAfterSelect		= 0;
	const CmdPosAtEnd			= 1;

	public static $driver		= 'iso';
	public static $constants	= false;
	
	public static function driver($strorobj) {
		if(is_string($strorobj))	
			self::$driver = strtolower($strorobj);
		elseif(is_object($strorobj)) {
			if($strorobj instanceof ADOConnection || $strorobj instanceof PDO)
				self::$driver = self::connectionDriver($strorobj);
			else
				return preg_replace('/^.*?_/', '', get_class($strorobj));
		}		
	}
	
	public static function connectionDriver($obj) {
		if($obj instanceof ADOConnection) {
			return self::ADODBDriver($obj);
		}	
	} 
	
	private static function ADODBDriver($obj) {
		$parent = 'ISO';
		
		$driver = strtolower(substr(get_class($obj), 6));
		
		if(substr($driver,0,5) === 'mysql')
			$driver = 'mysql';			
		elseif(substr($driver, 0, 8) === 'postgres')
			$driver = 'plpgsql';
		
		return $driver; 		
	} 

	/**
	 *  returns a new cfdbQuery object
	 * 
	 *	@param cfdbQuery $parent cfdbQuery class
	 *  @param string $driver to use to create query, if null uses default
	 *  @return cfdbQuery
	 * 
	 */	
	public static function query($parent=null, $driver=null) {
			if($parent instanceof ADOConnection || $parent instanceof PDO) {
				$driver = self::connectionDriver($parent);
				$parent = null;
			}
			
			$c = 'cfdbQuery_'.strtolower(((!is_null($driver)) ? $driver : self::$driver)); 
			
			if(class_exists($c)) {				
				return new $c($parent);
			}			
	}
	
	/**
	 * returns a Model Autogenerator cfModelAutogen Object
	 * 
	 * @param object $connection Database connection
	 * @param string $modelObject Name of the new model object to use.
	 * @param string $driver Name of the database driver to use
	 * @return cfModelAutogen
	 */
	public static function modelAutogen($connection, $modelObject=null, $driver=null) {
		$c = 'cfModelAutogen_'.strtolower(((!is_null($driver)) ? $driver : self::$driver));
		
		if(class_exists($c)) {
			return new $c($connection, $modelObject);
		}
	}
	
	public static function registerConstants() {
			if(!self::$constants) {
				foreach(array(	
					'eq'		=> '='
					,'lt'		=> '<'
					,'le'		=> '<='
					,'lte'		=> '<='
					,'gt'		=> '>'
					,'ge'		=> '>='
					,'gte'		=> '>='
					,'ne'		=> '<>' // i like != but all is a sybase fault!!! =(
					,'asc'		=> 'ASC'
					,'desc'		=> 'DESC'
					,'y'		=> 'AND'
					,'o'		=> 'OR'
					,'in'		=> 'IN'
				) as $c => $v) {
					if(!defined($c)) 
						define($c, $v, true);
					else
						trigger_error('The constant "'.$c.'" is needed by cfdb framework but is actually in use', E_WARNING);
				}
				
				self::$constants = true;				
			}
	}
	 		
}


cfdb::registerConstants();
?>