<?php

class GenericEntry {
	private $di;

	private $_errorMessages;

	//Table and field info
	private $_fields;
	private $_mainTableName;
	private $_primaryKeyFieldName = 'id';

	//Query statements
	private $_loadStatement;
	private $_insertStatement;
	private $_updateStatement;

	/**
	 * Constructor
	 * @param Array table information: An array of entity informations, including database tablename and fields
	 * @param Array field values: An array of fields in the form [fieldname => 'the_name_of_the_field', value => 'input_value']
	 * @param \Phalcon\DiInterface Phalcon Dependency Injection Service
	 */
	function __construct($tableName, array $fields, $dbCon) {
		$this->_fields = $fields;
		$this->_mainTableName = $tableName;
		$this->_errorMessages = [];
		$this->_loadStatement = null;
		$this->_insertStatement = null;
		$this->_updateStatement = null;

		$this->_dbConnection = $dbCon;
	}

	/*
		* @description: Saves the given data
		* @param: Array $data An array of data to save
	*/
	public function Save(Array $data) {
		$fieldsAndValues = $this->GetDataAsParameters($data);
		if ($this->_insertStatement == null) {
			$queryBuilder = new InsertStatementBuilder($this->_mainTableName, array_keys($fieldsAndValues));
			$queryBuilder->BuildStatement();
			$this->_insertStatement = $queryBuilder->GetStatement();
		}

		//Checking if all keys are numeric and sequential.
		//If so, it is assumed that several rows of data are given
		/*if (count($data) > 1 && array_keys($data) == range(0, count($data) - 1)) {
			foreach ($data as $row) {
				if (!$this->_dbConnection->execute($this->_insertStatement, $this->GetDataAsParameters($row))) {
					$this->_errorMessages[] = 'Could not save entry:' . $this->_dbConnection->getErrorInfo()[0];
					return false;
				}
			}
		} else {*/
		if (!$this->_dbConnection->execute($this->_insertStatement, $fieldsAndValues)) {
			$this->_errorMessages[] = 'Could not save entry:' . $this->_dbConnection->getErrorInfo()[0];
			return false;
		}
		//	}

		return true;
	}

	public function GetInsertId() {
		return $this->_dbConnection->lastInsertId();
	}

	public function Update($data) {
		if ($this->_updateStatement == null) {
			$queryBuilder = new UpdateStatementBuilder($this->_mainTableName, $this->_fields);
			$queryBuilder->BuildStatement();
			$this->_updateStatement = $queryBuilder->GetStatement();
		}

		//Save entry
		if (!$this->_dbConnection->execute($this->_updateStatement, $this->GetDataAsParameters($data))) {
			$this->_errorMessages[] = 'Could not save entry:' . $this->_dbConnection->getErrorInfo()[0];
			return false;
		}

		return true;
	}

	public function Load($id) {
		//We always load the primary field
		$fields = $this->_fields;
		$fields[] = ['fieldName' => $this->_primaryKeyFieldName];

		//We make a new statement each time the Load is called, because the key name can change
		$queryBuilder = new LoadStatementBuilder($this->_mainTableName, $fields);
		$queryBuilder->BuildStatement();
		$this->_loadStatement = $queryBuilder->GetStatement();

		$results = $this->_dbConnection->query($this->_loadStatement, ['id' => $id]);
		$results->setFetchMode(Phalcon\Db::FETCH_ASSOC);

		return $results->fetchAll();
	}

	public function FindByValues($values) {
		$queryBuilder = new FindStatementBuilder($this->_mainTableName, $this->_fields, $values);
		$queryBuilder->BuildStatement();

		$resultSet = $this->_dbConnection->query($queryBuilder->GetStatement());
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		return $resultSet->fetchAll();
	}

	public function GetErrorMessages() {
		return $this->_errorMessages;
	}

	private function GetDataAsParameters($data) {
		$parameters = [];

		foreach ($this->_fields as $field) {
			if (isset($data[$field['fieldName']])) {
				$parameters[$field['fieldName']] = $data[$field['fieldName']];
			}
		}
		if (count($parameters) == 0) {
			throw new InvalidArgumentException("no data found that matched the given fields");
		}

		return $parameters;
	}
}