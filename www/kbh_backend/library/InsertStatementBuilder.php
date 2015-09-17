<?php

class InsertStatementBuilder
{
	private $tableName;
	private $fields;

	/**
	 * Constructor. Takes a table name and an array of fieldss
	 * @param string $tableName The name of the table in which to insert data
	 * @param Array $fields An array of field names
	 */
	function __construct($tableName, Array $fields)
	{
		$this->tableName = $tableName;
		$this->fields = $fields;
	}

	/**
	 * Returns a statement based on the given table name and fields
	 * @return string A statement based on the given table name and fields
	 */
	public function GetStatement(){
		$query = "INSERT INTO " . $this->tableName . " " . $this->getFieldNames() . " VALUES " . $this->getFieldPlaceholders();
		return $query;
	}

	private function getFieldNames()
	{
		$fieldNames = "";

		foreach($this->fields as $field){
			$fieldNames .= '`' . $field . '`, ';
		}

		return substr($fieldNames, 0, strlen($fieldNames)-2);
	}

	private function getFieldPlaceholders()
	{
		$values = "(";

		foreach($this->fields as $fields){
			$values .= ':' . $fields . ', ';
		}

		return substr($values, 0, strlen($values)-2) . ')';
	}
}