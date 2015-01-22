<?php
/**
 * @todo Abstraction ADODB/PDO
 */
class cfAdminModel extends cfModel {
	public $tableObject	= 'cfAdminModelTable';	

	public function tableIdx($table) {
		return $this->tablesMap[$table];	
	}
	
	public function dbObjectQuote($object) {
		return $this->dbObjectQuoteChar.$object.$this->dbObjectQuoteChar;
	}

	public function tableFieldsMapByName($name) {
		return $this->tableFieldsMap($this->tablesMap[$name]); 
	}
	
	public function tableFieldsMap($index) {
		$tableFields = array();
		if(isset($this->tables[$index])) {
			$currentTable = $this->tables[$index];
			
			foreach($currentTable->fields as $idx => $field) {				
				$data = array();
				$tableFields[$field->name] = 't'.$index.'-f'.$idx;
			}
			
			return $tableFields;
		} else {
			/* EMSG */ new cfError('Cannot get table '.$idx, cfError);
			return false;
		}		 
	}
	
	
	public function getRowFromTableByName($name, $id) {
		return $this->getRowFromTable($this->tablesMap[$name], $id);		
	}
	
	public function getRowFromTable($index, $id) {
		if(isset($this->tables[$index])) {
			
			$currentTable	= $this->tables[$index];
			$tableName		= $currentTable->name;
			
			$q				= cfdb::query($this->dbConn)
								->QuoteStrings(true);
								
			$fields			= $q->select()->$tableName;
			
			foreach($currentTable->fields as $field) {
				if(
					!isset($field->options['retrieve']) 
					|| (isset($field->options['retrieve']) && $field->options['retrieve'] == true)
				)
					$fields->{$field->name}();
			}		

			$idName = $currentTable->identity->name;
			$q->where
				->$idName(eq, $id);

			$adofm = $GLOBALS['ADODB_FETCH_MODE'];
			$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;

			$row 	= $this->dbConnection->getRow($q);
			$crow	= array();

			foreach($currentTable->fields as $i => $field) {
				  $crow['t'.$index.'-f'.$i] = ((
												!isset($field->options['retrieve']) 
												|| (isset($field->options['retrieve']) && $field->options['retrieve'] == true)
											) ?  @$row[$field->name] : null);
				 
			}		

			$GLOBALS['ADODB_FETCH_MODE'] = $adofm;

			return $crow;
			
		}
	}

	public function tableDataByName($name) {
		return $this->tableData($this->tablesMap[$name]);		
	}
	
	public function tableData($in) {
		if(is_string($in) || is_numeric($in) || (is_object($in) && $in instanceof cfModelTable)) {
			
			if(is_object($in)) {
				$currentTable = $in;
				for($i=0,$c=count($this->tables);$i<$c;$i++) {
					if($this->tables[$i] === $in) {
						$in = $i;
						break;	
					}		
				}
			} elseif(isset($this->tables[$in]))
				$currentTable = $this->tables[$in];
			 
			
			$table	= array(
								'name' 		=> 't'.$in,
								'label' 	=> $currentTable->label,
								'fields'	=> array()
			);
			
			foreach($currentTable->fields as $idx => $field) {				
				if($field->reference instanceof cfModelRelation) {										
					if(
						$field->reference instanceof cfModelRelationSimple && (
							!isset($field->options['noauto']) ||
							(isset($field->options['noauto']) && (!$field->options['noauto']))
						)
					) {
						$field->options['type'] = 'selectbox';
						
						$data		= array();			
						
						$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_NUM;
												
						if(is_null($field->reference->labelField)) {
							if(!is_null($field->reference->field->table->field('name')))
								$field->reference->labelField = $field->reference->field->table->field('name');
							else
								cfError::create(
									/* EMSG */ sprintf('For reference "%s" does not exists a label field.', $field->reference->name)
									, cfErrorEngine
								);
						}
						
						$data		= array('data' => $this->dbConn->getAll(
													cfdb::query($this->dbConnection)
														->select()
															->{$field->reference->referenceField->table->name}
																->{$field->reference->referenceField->name}()
																->{$field->reference->labelField->name}()
														->_
														->order
															->{$field->reference->labelField->name}('')	
						));
						
						
						if(is_null($field->options))
							$field->options = $data;
						else {
							$field->options = $field->options + $data;
							unset($data);
						} 
						
						$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
					}	
				}
				
				array_push($table['fields'], array(
								'name' 			=> 't'.$in.'-f'.$idx,
								'label'			=> $field->label,
								'identity'		=> $field->identity,
								'default'		=> $field->default,
								'options'		=> $field->options
														
				));
				
			}
			
			return $table;
		} else {
			/* EMSG */ new cfError('Cannot get table '.$idx, cfError);
			return false;
		}
	}
	
	public function getClientAliasFromField($field) {
		$clientAlias = '';
		
		if(is_string($field)) 
			list($table, $fld) = explode('.', $field);

		for($i=0,$c=count($this->tables);$i<$c;$i++) {
			if((!is_string($field) && $this->tables[$i] === $field->table) || (is_string($field) && (is_object($this->tables[$i]) && $this->tables[$i]->name == $table))) {
				$clientAlias .= 't'.$i;
				for($k=0,$c=count($this->tables[$i]->fields);$k<$c;$k++) {
					if($field === $this->tables[$i]->fields[$k] || (is_string($field) && ($this->tables[$i]->fields[$k]->name == $fld))) {
						$clientAlias .= '-f'.$k;
						break;
					}
				}
				break; 
			}
		}

		return ($clientAlias) ? $clientAlias : null;
	}

	public function getFieldFromClientAlias($alias) {
		list($table, $field) = explode('-', preg_replace('/[tf]/', '', $alias));
		return $this->tables[((int) $table)]->fields[((int) $field)];
	}
	
	public function getTableFromClientAlias($alias) {
		$table = substr($alias, 1);
		return $this->tables[((int) $table)];
	}
}

class cfAdminModelTable extends cfModelTable {
	public $fieldObject = 'cfAdminModelField';
}

class cfAdminModelField extends cfModelField {
	public $validationObject = 'cfModelValidation';
}

class ModelValidation extends cfModelValidation {
	public 	$errorMessages		= array(
						'null' 		=> 'El campo %s esta vacio.',
						'pcre' 		=> 'El campo %s no es v�lido.',
						'maxLength'	=> 'El campo %s excede el largo de %d car�cteres.',
						'numeric'	=> 'El campo %s no es num�rico.'
					);
}

?>
