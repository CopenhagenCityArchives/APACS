<?php

class GenericEntry
{
	private $di;
	private $_dr;
	private $_errorMessages;
	private $_fields;
	private $_mainTableName;
	private $_dataIsNormalized;

	function __construct($tablename, $fields, \Phalcon\DiInterface $di)
	{
		$this->di = $di;

		$this->dr = new DataReceiver($this->di->get('request'));
		$this->_errorMessages = [];
		$this->_fields = $fields;
		$this->_mainTableName = $tablename;
		$this->_dataIsNormalized = false;
	}

	public function GetErrorMessages()
	{
		return $this->_errorMessages;
	}

	public function GetData()
	{
		if(!$this->_dataIsNormalized)
			return false;

		$this->getAndValidateData();

		$data = [];
		for($i = 0; $i < count($this->_fields); $i++)
		{
			$field = $this->_fields[$i];
			$data[$field['name']] = isset($field['normalizedValue']) ? $field['normalizedValue'] : $field['value'];
		}

		return $data;
	}

	public function Save()
	{
		if(!$this->getAndValidateData())
			return false;

		if(!$this->getOrSaveNormalizedData())
			return false;

		$queryBuilder = new InsertStatementBuilder($this->_mainTableName, $this->_fields);
		$queryBuilder->BuildStatement();

		$connection = $this->di->get('db');

		//Save entry
		if(!$connection->execute($queryBuilder->statement, $this->mapFieldValues()))
		{
			$this->_errorMessages[] = 'Could not save entry:' . $connection->getErrorInfo()[0];
			return false;
		}

		return true;
	}

	private function getAndValidateData()
	{
		$isValid = true;
		//Get inputs and validate them
		for($i = 0; $i < count($this->_fields); $i++)
		{
			$this->_fields[$i]['value'] = $this->dr->Value('POST',$this->_fields[$i]['name']);
			
			$validator = new Validator(
				new ValidationRuleSet(
					$this->_fields[$i]['validationRegularExpression'],
					$this->_fields[$i]['required'],
					$this->_fields[$i]['validationErrorMessage'])
			);

			//TODO: Get validation error messages
			if(!$validator->isValid($this->_fields[$i]['value']))
			{
				$this->_errorMessages[] = $validator->GetErrorMessage();
				$isValid = false;
			}
		}

		return $isValid;
	}

	private function getOrSaveNormalizedData()
	{
		$normalizedDataIsValid = true;
		//Get or save normalized data
		for($i = 0; $i < count($this->_fields); $i++)
		{
			if($this->_fields[$i]['type'] == '1:m' || $this->_fields[$i]['type'] == 'm:m')
			{
				//Copying the real value in normalizedValue
				$this->_fields[$i]['normalizedValue'] = $this->_fields[$i]['value'];
				$this->_fields[$i]['value'] = $this->convertDataToNormalizedId($this->_fields[$i]);

				$validator = new Validator(
					new ValidationRuleSet(
						'/^$|\s|\d+/', 
						true, 
						$this->_fields[$i]['name'] . ' indeholder en ikke-normaliseret værdi: ' . $this->_fields[$i]['normalizedValue']
					)
				);
				
				if(!$validator->IsValid($this->_fields[$i]['value'])){
					$normalizedDataIsValid = false;
					$this->_errorMessages[] = $validator->GetErrorMessage();
				}
			}
		}

		$this->_dataIsNormalized = true;

		return $normalizedDataIsValid;
	}

	private function convertDataToNormalizedId($field)
	{
		$normalizedId = null;

		if($field['type'] == '1:m') //Normalized field
		{
			$normalizedId = $this->getNormalizedId($field['normalizedTable'], $field['normalizedField'], $field['value'], $field['normalizedAllowNewValue']);
		}
		else if($field['type'] == 'm:m') //Many to many relation
		{
			throw new NotImplementedExeception('many to many saving is not implemented...');
			/*$normalizedId = $this->getNormalizedId($field[0], $field[1], $field[2]);

			$this->getManyToMany($field[0], $field[1], $field[2], $field[3], $field[5]);*/
		}	

		return $normalizedId;
	}

	private function getNormalizedId($table, $entryField, $value, $newValueAllowed)
	{
		$query = 'SELECT id FROM ' . $table . ' WHERE ' . $entryField . ' = "' . $value . '" LIMIT 1';
		$resultSet = $this->di->get('db')->query($query);
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $result = $resultSet->fetchAll();

		if(count($result) !== 1 && $newValueAllowed == true)
		{
			return $this->saveNormalizedValueGetId($table, $entryField, $value);
		}

		if(count($result) == 1)
		{
			return $result[0]['id'];
		}

		return null;
	}

	private function saveNormalizedValueGetId($tablename, $entryField, $value)
	{
		$query = 'INSERT INTO ' . $tablename . ' (' . $entryField . ') VALUES ("' . $value .'")';
		$con = $this->di->get('db');
		
		if($con->query($query))
		{
			return $con->lastInsertId();
		}

		return false;
	}	

	private function mapFieldValues()
	{
		$fieldsValues = [];

		for($i = 0; $i < count($this->_fields); $i++)
		{
			$fieldsValues[$this->_fields[$i]['name']] = $this->_fields[$i]['value'];
		}

		return $fieldsValues;
	}	
}