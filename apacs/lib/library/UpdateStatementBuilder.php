<?php

class UpdateStatementBuilder implements IStatementBuilder {
	private $tableName;
	private $fields;
	private $statement;

	/**
	 * Constructor. Takes a table name and an array of fieldss
	 * @param array An array containing the entry type from which the statement is built
	 */
	function __construct($tableName, $fields, $keyName = 'id') {
		$this->tableName = $tableName;
		$this->fields = $fields;
		$this->keyName = $keyName;

		if (count($this->fields) < 1) {
			throw new InvalidArgumentException("No fields given");
		}

	}

	/**
	 * Returns a statement based on the given table name and fields
	 * @return string A statement based on the given table name and fields
	 */
	public function BuildStatement() {
		$this->statement = "UPDATE " . $this->tableName . " SET " . $this->getFieldNameAndPlaceholderPairs() . " WHERE " . $this->keyName . " = :id;";
	}

	public function GetStatement() {
		return $this->statement;
	}

	private function getFieldNameAndPlaceholderPairs() {
		$string = '';

		foreach ($this->fields as $field) {
			$string .= '`' . $field['fieldName'] . '` = :' . $field['fieldName'] . ', ';
		}

		return substr($string, 0, strlen($string) - 2);
	}
}