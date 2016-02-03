<?php

class GenericEntry {
	private $di;

	private $_errorMessages;

	//Table and field info
	private $_fields;
	private $_mainTableName;
	private $_primaryKeyFieldName;

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
	function __construct($table, $fields, $dbCon) {
		$this->_fields = $fields;
		$this->_mainTableName = $table['dbTableName'];
		$this->_primaryKeyFieldName = $table['primaryKeyFieldName'];

		$this->_errorMessages = [];
		$this->_loadStatement = null;
		$this->_insertStatement = null;

		$this->_dbConnection = $dbCon;
	}

	/*
		* @description: Saves the given data
		* @param: Array $data An array of data to save
	*/
	public function Save(Array $data) {
		if ($this->_insertStatement == null) {
			$queryBuilder = new InsertStatementBuilder($this->_mainTableName, array_filter($this->_fields, function ($el) use ($data) {return isset($el['dbFieldName']) && isset($data[$el['dbFieldName']]) && !is_null($data[$el['dbFieldName']]);}));
			$queryBuilder->BuildStatement();
			$this->_insertStatement = $queryBuilder->GetStatement();
		}

		//Checking if all keys are numeric and sequential.
		//If so, it is assumed that several rows of data are given
		if (array_keys($data) == range(0, count($data) - 1)) {
			foreach ($data as $row) {
				$validated = $this->ValidateValues($row);

				if (!$validated) {
					return false;
				}

				if (!$this->_dbConnection->execute($this->_insertStatement, $this->GetDataAsParameters($row))) {
					$this->_errorMessages[] = 'Could not save entry:' . $this->_dbConnection->getErrorInfo()[0];
					return false;
				}
			}
		} else {
			$validated = $this->ValidateValues($data);

			if (!$validated) {
				return false;
			}
			echo $this->_insertStatement;
			var_dump($this->GetDataAsParameters($data));
			if (!$this->_dbConnection->execute($this->_insertStatement, $this->GetDataAsParameters($data))) {
				$this->_errorMessages[] = 'Could not save entry:' . $this->_dbConnection->getErrorInfo()[0];
				return false;
			}
		}

		return true;
	}

	public function GetInsertId() {
		return $this->_dbConnection->lastInsertId();
	}

	public function Update($data) {
		if (!$this->ValidateValues($data)) {
			return false;
		}

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
		//We make a new statement each time the Load is called, because the key name can change
		$queryBuilder = new LoadStatementBuilder($this->_mainTableName, $this->_fields);
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

	public function ValidateValues($data, $ignoreNulls = false) {
		$isValid = true;

		foreach ($this->_fields as &$field) {
			//Don't validate the primary key and field types that are not values
			if (isset($field['dbFieldName']) && $field['dbFieldName'] == $this->_primaryKeyFieldName || $field['type'] !== 'string') {
				continue;
			}

			if (isset($field['validationRegularExpression'])) {
				$validator = new Validator(
					new ValidationRuleSet(
						$field['validationRegularExpression'],
						$field['required'],
						$field['validationErrorMessage']
					)
				);

				//Get validation error messages
				if (!$validator->isValid($data[$field['dbFieldName']], $ignoreNulls)) {
					$this->_errorMessages[] = $field['dbFieldName'] . ':' . $field['validationErrorMessage'];
					//$this->_fields[$i]['isValid'] = false;
					//$this->_fields[$i]['errorMessage'] = $validator->GetErrorMessage();
					$isValid = false;
				}
			} else {
				//Check for null values
				if ($ignoreNulls == false && is_null($data[$field['dbFieldName']])) {
					$this->_errorMessages[] = $field['dbFieldName'] . ':' . $field['validationErrorMessage'];
					///$this->_fields[$i]['isValid'] = false;
					//$this->_fields[$i]['errorMessage'] = $this->_fields[$i]['validationErrorMessage'];
					$isValid = false;
				}
			}
		}

		return $isValid;
	}

	public function GetErrorMessages() {
		return $this->_errorMessages;
		/*	$errors = [];
			foreach ($this->_fields as $field) {
				if (isset($field['errorMessage'])) {
					$errors[] = $field['dbFieldName'] . ':' . $field['errorMessage'];
				}

			}

		*/
	}

	private function GetDataAsParameters($data) {
		$parameters = [];

		foreach ($this->_fields as $field) {
			//Set fields that are of type value and have a value
			$dataFieldName = isset($field['dbFieldName']) ? $field['dbFieldName'] : $field['dbTableName'];

			if (($field['type'] == 'string' || $field['type'] == 'object') && isset($data[$dataFieldName])) {
				$parameters[$dataFieldName] = $data[$dataFieldName];
			}
		}
		if (count($parameters) == 0) {
			throw new InvalidArgumentException("no fields of type string or object found");
		}

		return $parameters;
	}
/*
private function ConvertCodeValues($row) {

foreach ($this->_fields as $field) {
if (!is_null($field['codeTable'])) {
$newValue = $this->GetCodeValue($field, $row[$field['dbFieldName']]);

if (!is_null($newValue)) {
$row[$field['dbFieldName']] = $newValue;
}
}
}

return $row;
}

private function GetCodeValue($field, $value) {
$query = 'SELECT id FROM ' . $field['codeTable'] . ' WHERE ' . $field['codeField'] . ' = "' . $value . '" LIMIT 1';

$resultSet = $this->_dbConnection->query($query);
$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
$result = $resultSet->fetchAll();

if (count($result) == 0 && $field['codeAllowNewValue'] == 1) {
return $this->CreateNewCodeValue($field, $value);
}

if (count($result) == 1) {
return $result[0]['id'];
}

return null;
}

private function CreateNewCodeValue($field, $value) {
$query = 'INSERT INTO ' . $field['codeTable'] . ' (' . $field['codeField'] . ') VALUES ("' . $value . '")';

if ($this->_dbConnection->query($query)) {
return $this->_dbConnection->lastInsertId();
}

return null;
}*/
}