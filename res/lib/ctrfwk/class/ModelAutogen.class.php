<?php

class cfModelAutogen extends stdClass {
	public $modelObject				= 'cfModel';
	public $connection				= null;
	public $dbConn					= null;
	public $conn					= null;
	
	public function __construct($connection, $modelObject=null ) {
		$this->setModelObject($modelObject);
		
		$reflector			= new ReflectionClass('cfdb_'.cfdb::connectionDriver($connection));
		
		foreach($reflector->getConstants() as $prop => $value) {
			$this->$prop = $value;
			$prop = substr($prop, 0, 1).strtolower(preg_replace('/[a-z]/smx', '', $prop));
			$this->$prop = $value;
		}
		
		$this->connection	= $connection;		
		$this->conn			= &$this->connection;
		$this->dbconn		= &$this->connection;
	}
	
	public function setModelObject($modelObject) {
		if(
			!is_null($modelObject) 
			&& class_exists($modelObject) 
			&& is_subclass_of($modelObject, 'cfModel')
		) {
			$this->modelObject = $modelObject;
		} 
	}
	
	private function driver() {
		return substr(get_class($this), 13);
	} 
	
	public function query() {
		
	}
	
	public function schemas() {
		
	}
	
	public function relations() {
		
	}
	
	public function columns() {
		
	}
	
	private function createModel() {
		$to		= $this->modelObject;
		return new $to($this->tables(), $this->connection);
	}
		
	/**
	 * Generate model from db 
	 *
	 * @return cfModel
	 */	 
	function generateModel() {
		
			$model	= $this->createModel();			
			$rs		= $this->columns();

			while($col = array_shift($rs)) {								
				$model->table($col['table'])->addField($col);
			}
			
			$rs		= $this->relations();
			
			while($rel = array_shift($rs)) {
				$model->addRelationSimple(
											$rel->name
											, $model->table($rel->table)->field($rel->column)
											, $model->table($rel->referencedTable)->field($rel->referencedColumn)
				);
			}
			
			
			return $model;	
	}
}

class cfModelAutogen_iso extends cfModelAutogen {
	/**
	 * get cfdbQuery with properly driver
	 * 
	 * @return cfdbQuery;
	 */
	function query() {
		return cfdb::query(null, cfdb::driver($this));
	}
	
	function schemas() {
		return $this->conn->GetCol(
			$this->query()	
				->select()
					->INFORMATION_SCHEMA__SCHEMATA
						->SCHEMA_NAME(null, 'schema')
				->_
				->order
					->SCHEMA_NAME(null, asc)
		);
	}
	
	function tables($schema=null) {
		$schema = is_null($schema) ? $this->cs : $schema;
			
		return $this->conn->GetAll(
			$this->query()
				->quoteStrings(false)
				->select()
					->INFORMATION_SCHEMA__TABLES
						->TABLE_NAME(null, 'name')
						->TABLE_COMMENT("substring_index(:field, '; InnoDB', 1)", 'label')
				->_
				->where
					->TABLE_SCHEMA(eq, $schema)
				->_
				->order
					->TABLE_NAME(null, asc, y)
					
		);		
	}
	
	function relations($schema=null) { 
		$schema = is_null($schema) ? $this->cs : $schema;
		
		$rs =$this->conn->GetAll(
			$this->query()
				->quoteStrings(false)
				->select()
					->INFORMATION_SCHEMA__KEY_COLUMN_USAGE
						->CONSTRAINT_NAME(null, 'name') 
						->TABLE_SCHEMA(null, 'schema')
						->TABLE_NAME(null, 'table')
						->COLUMN_NAME(null, 'column')
						->REFERENCED_TABLE_SCHEMA(null, 'referencedSchema')
						->REFERENCED_TABLE_NAME(null, 'referencedTable')
						->REFERENCED_COLUMN_NAME(null, 'referencedColumn')
				->_
				->where
					->INFORMATION_SCHEMA__KEY_COLUMN_USAGE
						->TABLE_SCHEMA(eq, $schema, y)
						->REFERENCED_TABLE_NAME(ne, null)
		);	
		
		for($i=0,$c = count($rs);$i<$c;$i++) 
			$rs[$i] = (object) $rs[$i];
		
		return $rs;	
	}
}		

class cfModelAutogen_mysql extends cfModelAutogen_iso {
	function columns($schema=null) {
		$rs = $this->conn->GetAll(
			$this->query()
				->quoteStrings(false)
				->select()
					->INFORMATION_SCHEMA__COLUMNS
						->TABLE_CATALOG(null, 'catalog')
						->TABLE_SCHEMA(null, 'schema')
						->TABLE_NAME(null, 'table')
						->COLUMN_NAME(null, 'name')
						->IS_NULLABLE('LOWER', 'nullable')
						->COLUMN_DEFAULT(null, 'default')
						->DATA_TYPE(null, 'datatype')
						->CHARACTER_MAXIMUM_LENGTH(null, 'char_max_len')
						->CHARACTER_OCTET_LENGTH(null, 'char_oct_len')
						->NUMERIC_PRECISION(null, 'num_prec')						
						->CHARACTER_SET_NAME(null, 'charset')
						->COLUMN_KEY('LOWER', 'key')
						->EXTRA(null, 'extra')
						->COLUMN_COMMENT(null, 'label')
				->_
				->where
					->TABLE_SCHEMA(eq, is_null($schema) ? $this->cs : $schema)
				->_
				->order
					->TABLE_NAME(asc)
					->ORDINAL_POSITION(asc)					
		);
		
		$outrs = array();
		
		while($col = array_shift($rs)) {
			array_push($outrs , array(
					'table'			=> $col['table']	
					,'name'			=> $col['name']					
					,'label'		=> $col['label']
					,'options'		=> array('type'			=>  (
												(strpos($col['datatype'], 'char') !== false)
												? 'textbox'
												: ( (strpos($col['datatype'], 'text') !== false)
														? 'textarea'
														:	((substr($col['datatype'], 0,3) === 'int' || substr($col['datatype'], -3) === 'int')
																? 'textbox' // int
																: ( (strpos($col['datatype'], 'blob') !== false)
																	? 'file'
																	: //cfUtil::miniSwitch(
																		$col['datatype']
//																		, $col['datatype']
	//																 )
																)
														)
												)
									)
										,'retrieve'			=> ((strpos($col['datatype'], 'blob') !== false) ? false : true)
									)
					,'default'		=> $col['default']
					,'identity'		=> $col['key'] === 'pri'
					,'validation'	=> array(
						  'null'			=> $col['nullable'] === 'no'
						, 'nullConvert'		=> $col['nullable'] === 'no'
						, 'maxLength'		=> $col['char_max_len']				
					)
			));
		}
		
		return $outrs;
	}
}

class cfModelAutogen_mssql extends cfModelAutogen_iso {

}
