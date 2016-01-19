<?php

class GenericEntry
{
	private $di;

	private $_errorMessages;
	
	//Table and field info
	private $_fields;
	private $_mainTableName;
	
	//Query statements
	private $_loadStatement;
	private $_insertStatement;

	/**
	 * Constructor
	 * @param Array table information: An array of entity informations, including database tablename and fields
	 * @param Array field values: An array of fields in the form [fieldname => 'the_name_of_the_field', value => 'input_value']
	 * @param \Phalcon\DiInterface Phalcon Dependency Injection Service
	 */
	function __construct($table, $fields, $dbCon)
	{
		$this->_fields = $fields;
		$this->_mainTableName = $table;

		$this->_errorMessages = [];
		$this->_loadStatement = null;
		$this->_insertStatement = null;

		$this->_dbConnection = $dbCon;
	}

	/*
	* @description: Saves the given data
	* @param: Array $data An array of data to save
	 */
	public function Save(Array $data)
	{
		if($this->_insertStatement == null){
			$queryBuilder = new InsertStatementBuilder($this->_mainTableName, $this->_fields);
			$queryBuilder->BuildStatement();
			$this->_insertStatement = $queryBuilder->GetStatement();
		}

		//Checking if all keys are numeric and sequential.
		//If so, it is assumed that several rows of data are given
		if(array_keys($data) == range(0, count($data) - 1))
		{
			foreach($data as $row)
			{
				$row = $this->ConvertCodeValues($row);
				$this->ValidateValues($row);

				if(!$this->_dbConnection->execute($this->_insertStatement, $this->GetDataAsParameters($row)))
				{
					$this->_errorMessages[] = 'Could not save entry:' . $this->_dbConnection->getErrorInfo()[0];
					return false;
				}
			}
		}
		else{
			$data = $this->ConvertCodeValues($data);
			$this->ValidateValues($data);
			if(!$this->_dbConnection->execute($this->_insertStatement, $this->GetDataAsParameters($data)))
			{
				$this->_errorMessages[] = 'Could not save entry:' . $this->_dbConnection->getErrorInfo()[0];
				return false;
			}
		}

		return true;
	}

	public function GetInsertId()
	{
		return $this->_dbConnection->lastInsertId();
	}
/*
	public function Update($id)
	{
		if(!$this->ValidateValues()){
			$this->_dbConnection->rollback();
			return false;
		}

		if(!$this->getOrSaveNormalizedData()){
			$this->_dbConnection->rollback();
			return false;
		}

		if($this->_updateStatement == null){
			$queryBuilder = new UpdateStatementBuilder($this->_mainTableName, $this->_fields);
			$queryBuilder->BuildStatement();
			$this->_updateStatement = $queryBuilder->GetStatement();
		}

		//Save entry
		if(!$this->_dbConnection->execute($this->_updateStatement, $this->mapFieldValues()))
		{
			$this->_errorMessages[] = 'Could not update entry:' . $this->_dbConnection->getErrorInfo()[0];
			$this->_dbConnection->rollback();
			return false;
		}

		$this->_dbConnection->commit();
		return true;
	}
*/
	
	public function Load($id)
	{
		//We make a new statement each time the Load is called, because the key name can change
		$queryBuilder = new LoadStatementBuilder($this->_mainTableName, $this->_fields);
		$queryBuilder->BuildStatement();
		$this->_loadStatement = $queryBuilder->GetStatement();

		$results = $this->_dbConnection->query($this->_loadStatement, ['id' => $id]);
		$results->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		
		return $results->fetchAll();
	}

	public function FindByValues($values)
	{
		$queryBuilder = new FindStatementBuilder($this->_mainTableName, $this->_fields, $values);
		$queryBuilder->BuildStatement();
		$resultSet = $this->_dbConnection->query($queryBuilder->GetStatement());
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        return $resultSet->fetchAll();
	}

	public function ValidateValues($data, $ignoreNulls = false)
	{
		$isValid = true;

		for($i = 0; $i < count($this->_fields); $i++)
		{
			if(isset($field['validationRegularExpression'])){
				$validator = new Validator(
					new ValidationRuleSet(
						$this->_fields[$i]['validationRegularExpression'],
						$this->_fields[$i]['required'],
						$this->_fields[$i]['validationErrorMessage']
					)
				);
				
				//Get validation error messages
				if(!$validator->isValid($data[$this->_fields[$i]['dbFieldName']], $ignoreNulls))
				{
					$this->_fields[$i]['isValid'] = false;
					$this->_fields[$i]['errorMessage'] = $validator->GetErrorMessage();
					$isValid = false;
				}
			}
		}

		return $isValid;
	}

	public function GetErrorMessages()
	{
		$errors = [];
		foreach($this->_fields as $field)
		{
			if(isset($field['errorMessage']))
				$errors[] = $field['errorMessage'];
		}

		return $errors;
	}

	private function GetDataAsParameters($data)
	{
		$parameters = [];
		foreach($this->_fields as $field)
		{
			if(isset($data[$field['dbFieldName']]))
			{
				$parameters[$field['dbFieldName']] = $data[$field['dbFieldName']];
			}
		}

		return $parameters;
	}

	private function ConvertCodeValues($row)
	{
		foreach($this->_fields as $field)
		{
			if($field['codeTable'] !== NULL)
			{
				$newValue = $this->GetCodeValue($field, $row[$field['dbFieldName']]);
				
				if($newValue !== NULL){
					$row[$field['dbFieldName']] = $newValue;
				}
			}
		}

		return $row;
	}

	private function GetCodeValue($field, $value)
	{
		$query = 'SELECT id FROM ' . $field['codeTable'] . ' WHERE ' . $field['codeField'] . ' = "' . $value . '" LIMIT 1';

		$resultSet = $this->_dbConnection->query($query);
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $result = $resultSet->fetchAll();

		if(count($result) == 0 && $field['codeAllowNewValue'] == 1)
		{
			return $this->CreateNewCodeValue($field, $value);
		}

		if(count($result) == 1)
		{
			return $result[0]['id'];
		}

		return null;
	}

	private function CreateNewCodeValue($field, $value)
	{
		$query = 'INSERT INTO ' . $field['codeTable'] . ' (' . $field['codeField'] . ') VALUES ("' . $value .'")';
		
		if($this->_dbConnection->query($query))
		{
			return $this->_dbConnection->lastInsertId();
		}

		return null;
	}	
}