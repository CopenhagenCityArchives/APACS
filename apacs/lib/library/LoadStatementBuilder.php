<?php

class LoadStatementBuilder implements IStatementBuilder {
	private $tableName;
	private $fields;
	private $statement;
	private $keyName;

	/**
	 * Constructor. Takes a table name and an array of fieldss
	 * @param array An array containing the entry type from which the statement is built
	 */
	function __construct($tableName, $fields, $keyName = 'id') {
		$this->tableName = $tableName;
		$this->fields = $fields;
		$this->keyName = $keyName;
	}

	/**
	 * Returns a statement based on the given table name and fields
	 * @return string A statement based on the given table name and fields
	 */
	public function BuildStatement() {
		//"SELECT id, name, lastname, stilling_id, stilling as stilling_value FROM begrav_person LEFT JOIN begrav_stillinger ON begrav_person.stilling_id = begrav_stillinger.id WHERE id = 2";

		//	$this->statement = 'SELECT ' . $this->getFieldNames() . ' FROM ' . $this->tableName . '.' . $this->keyName . $this->getJoins() . ' WHERE' . $this->tableName . '.'. $this->keyName - ' = :id';
		$this->statement = 'SELECT ' . $this->getFieldNames() . ' FROM ' . $this->tableName . ' WHERE ' . $this->tableName . '.' . $this->keyName . ' = :id';
	}

	public function GetStatement() {
		return $this->statement;
	}

	private function getFieldNames() {
		$fieldNames = "";

		foreach ($this->fields as $field) {
			if ($field['type'] == 'value') {
				$fieldNames .= '`' . $field['dbFieldName'] . '`, ';
			}
		}

		return substr($fieldNames, 0, strlen($fieldNames) - 2);
	}
}