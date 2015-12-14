<?php

class LoadStatementBuilder implements IStatementBuilder
{
	private $tableName;
	private $fields;
	private $statement;

	/**
	 * Constructor. Takes a table name and an array of fieldss
	 * @param array An array containing the entry type from which the statement is built
	 */
	function __construct($tableName, $fields)
	{
		$this->tableName = $tableName;
		$this->fields = $fields;
	}

	/**
	 * Returns a statement based on the given table name and fields
	 * @return string A statement based on the given table name and fields
	 */
	public function BuildStatement(){
		//"SELECT id, name, lastname, stilling_id, stilling as stilling_value FROM begrav_person LEFT JOIN begrav_stillinger ON begrav_person.stilling_id = begrav_stillinger.id WHERE id = 2";

		//$this->statement = "SELECT " . $this->getFieldNames() . " FROM " . $this->tableName . " WHERE id = " . $id;
		$this->statement = 'SELECT ' . $this->getFieldNames() . ' FROM ' . $this->tableName . $this->getJoins() . ' WHERE ' . $this->tableName . '.id = :id';
	}

	public function GetStatement()
	{
		return $this->statement;
	}

	private function getFieldNames()
	{
		$fieldNames = "";

		foreach($this->fields as $field){
			if(isset($field['normalizationTable']) && $field['normalizationTable'] !== null){
				$fieldNames .= '`' . $field['dbFieldName'] . '` as ' . $field['dbFieldName'] .'_id' . ', ' . $field['normalizationTable'] . '.`' . $field['normalizationField'] . '` as ' . $field['dbFieldName'] . '_value, ';
			}
			else{
				$fieldNames .= '`' . $field['dbFieldName'] . '`, ';
			}			
		}

		return substr($fieldNames, 0, strlen($fieldNames)-2);
	}

	private function getJoins()
	{
		$joins = '';

		foreach($this->fields as $field){
			//Normalization assumed
			if(isset($field['normalizationTable']) && $field['normalizationTable'] !== null)
			{
				$joins .= ' LEFT JOIN ' . $field['normalizationTable'] . ' ON ' . $this->tableName . '.' . $field['dbFieldName'] . ' = ' . $field['normalizationTable'] . '.' . $field['normalizationPrimaryKey']; 
			}
		}

		return $joins;
	}	
}