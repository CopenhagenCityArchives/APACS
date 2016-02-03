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
			if ($field['type'] == 'value') {
				$string .= '`' . $field['dbFieldName'] . '` = :' . $field['dbFieldName'] . ', ';
			}
		}

		if (strlen($string) == 0) {
			throw new InvalidArgumentException("No fields of type string or object found in fields");
		}

		return substr($string, 0, strlen($string) - 2);
	}
}