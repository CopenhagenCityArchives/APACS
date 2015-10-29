<?php

class InsertStatementBuilder
{
	private $tableName;
	private $fields;
	public $statement;

	/**
	 * Constructor. Takes a table name and an array of fieldss
	 * @param array An array containing the entry type from which the statement is built
	 */
	function __construct($entryType)
	{
		$this->tableName = $entryType['dbTableName'];
		$this->fields = $entryType['fields'];
	}

	/**
	 * Returns a statement based on the given table name and fields
	 * @return string A statement based on the given table name and fields
	 */
	public function BuildStatement(){
		$this->statement = "INSERT INTO " . $this->tableName . " (" . $this->getFieldNames() . ") VALUES " . $this->getFieldPlaceholders();
	}

	private function getFieldNames()
	{
		$fieldNames = "";

		foreach($this->fields as $field){
			$fieldNames .= '`' . $field['name'] . '`, ';
		}

		return substr($fieldNames, 0, strlen($fieldNames)-2);
	}

	private function getFieldPlaceholders()
	{
		$values = "(";

		foreach($this->fields as $field){
			$values .= ':' . $field['name'] . ', ';
		}

		return substr($values, 0, strlen($values)-2) . ')';
	}
}