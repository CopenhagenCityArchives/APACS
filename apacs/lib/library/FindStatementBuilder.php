<?php

class FindStatementBuilder implements IStatementBuilder {
	private $tableName;
	private $fields;
	private $statement;
	private $values;

	/**
	 * Constructor. Takes a table name and an array of fieldss
	 * @param array An array containing the entry type from which the statement is built
	 */
	function __construct($tableName, $fields, $values) {
		$this->tableName = $tableName;
		$this->fields = $fields;
		$this->values = $values;
	}

	/**
	 * Returns a statement based on the given table name and fields
	 * @return string A statement based on the given table name and fields
	 */
	public function BuildStatement() {
		//"SELECT id, name, lastname, stilling_id, stilling as stilling_value FROM begrav_person LEFT JOIN begrav_stillinger ON begrav_person.stilling_id = begrav_stillinger.id WHERE id = 2";

		//$this->statement = "SELECT " . $this->getFieldNames() . " FROM " . $this->tableName . " WHERE id = " . $id;
		$this->statement = 'SELECT * FROM ' . $this->tableName . ' WHERE ' . $this->getConditions();
	}

	public function GetStatement() {
		return $this->statement;
	}

	private function getFieldNames() {
		$fieldNames = "";

		foreach ($this->fields as $field) {
			$fieldNames .= '`' . $field['dbFieldName'] . '`, ';
		}

		return substr($fieldNames, 0, strlen($fieldNames) - 2);
	}

	private function getConditions() {
		$conditions = '';
//TODO: Also find codeTable and field values
		foreach ($this->fields as $field) {
			if (isset($this->values[$field['dbFieldName']])) {
				$conditions .= $field['dbFieldName'] . ' = "' . $this->values[$field['dbFieldName']] . '" AND ';
			}
		}

		return substr($conditions, 0, strlen($conditions) - 5);
	}
}