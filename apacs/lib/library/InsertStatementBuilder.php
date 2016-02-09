<?php

class InsertStatementBuilder implements IStatementBuilder {
	private $tableName;
	private $fields;
	private $statement;

	/**
	 * Constructor. Takes a table name and an array of fieldss
	 * @param array An array containing the entry type from which the statement is built
	 */
	function __construct($tableName, $fields) {
		$this->tableName = $tableName;
		$this->fields = $fields;

		if (count($this->fields) < 1) {
			throw new InvalidArgumentException("No fields found");
		}
	}

	/**
	 * Returns a statement based on the given table name and fields
	 * @return string A statement based on the given table name and fields
	 */
	public function BuildStatement() {
		$this->statement = "INSERT INTO " . $this->tableName . " (" . $this->getFieldNames() . ") VALUES " . $this->getFieldPlaceholders();
	}

	public function GetStatement() {
		return $this->statement;
	}

	private function getFieldNames() {
		$fieldNames = "";

		foreach ($this->fields as $field) {
			$fieldNames .= '`' . $field['fieldName'] . '`, ';
		}

		return substr($fieldNames, 0, strlen($fieldNames) - 2);
	}

	private function getFieldPlaceholders() {
		$values = "(";

		foreach ($this->fields as $field) {
			$values .= ':' . $field['fieldName'] . ', ';
		}

		return substr($values, 0, strlen($values) - 2) . ')';
	}
}