<?php

class GenericEntry
{
	private $di;

	private $_errorMessages;
	
	private $_fields;
	private $_mainTableName;
	
	private $_fieldValues;

	private $_dataIsNormalized;

	private $_loadStatement;
	private $_insertStatement;

	/**
	 * Constructor
	 * @param Array Entity information: An array of entity informations, including database tablename and fields
	 * @param Array Field values: An array of fields in the form [fieldname => 'the_name_of_the_field', value => 'input_value']
	 * @param \Phalcon\DiInterface Phalcon Dependency Injection Service
	 */
	function __construct($entityInfo, $fieldValues, \Phalcon\DiInterface $di)
	{
		$this->di = $di;

	//	$this->dr = new DataReceiver($this->di->get('request'));
		$this->_errorMessages = [];
		$this->_fields = $entityInfo['fields'];
		$this->_mainTableName = $entityInfo['tablename'];

		$this->_fieldValues = $fieldValues;

		$this->_dataIsNormalized = false;

		$this->_loadStatement = null;
		$this->_insertStatement = null;

		$this->_dbConnection = $di->get('db');
		
		//Map values and fields if values are given
		if(count($this->_fieldValues) > 0)
		{
			$this->mapValuesAndFields();
		}
	}

	public function GetData()
	{
		if(!$this->_dataIsNormalized)
			return false;

		$this->ValidateValues();

		$data = [];
		for($i = 0; $i < count($this->_fields); $i++)
		{
			$field = $this->_fields[$i];
			$data[$field['name']] = isset($field['normalizedValue']) ? $field['normalizedValue'] : $field['value'];
		}

		return $data;
	}

	/**
	 * Saves an entry based on the metadata and the concrete input fields
	 */
	public function Save()
	{
		//Let's start a transaction
		$this->_dbConnection->begin();

		if(!$this->ValidateValues()){
			$this->_dbConnection->rollback();
			return false;
		}

		if(!$this->getOrSaveNormalizedData()){
			$this->_dbConnection->rollback();
			return false;
		}

		if($this->_insertStatement == null){
			$queryBuilder = new InsertStatementBuilder($this->_mainTableName, $this->_fields);
			$queryBuilder->BuildStatement();
			$this->_insertStatement = $queryBuilder->GetStatement();
		}

		//Save entry
		if(!$this->_dbConnection->execute($this->_insertStatement, $this->mapFieldValues()))
		{
			$this->_errorMessages[] = 'Could not save entry:' . $this->_dbConnection->getErrorInfo()[0];
			$this->_dbConnection->rollback();
			return false;
		}

		//Committing the database changes
		if(!$this->_dbConnection->commit())
		{
			$this->_errorMessages[] = 'Could not save entry:' . $this->_dbConnection->getErrorInfo()[0];
			return false;
		}

		return true;
	}

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

	//TODO: Should be able to load by primary key and by post_id
	public function Load($id)
	{
		if($this->_loadStatement == null){
			$queryBuilder = new LoadStatementBuilder($this->_mainTableName, $this->_fields);
			$queryBuilder->BuildStatement();
			$this->_loadStatement = $queryBuilder->GetStatement();
		}

		$results = $this->_dbConnection->query($this->_loadStatement, ['id' => $id]);
		$results->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		
		$entitiesFields = [];
		$i = 0;
		while($cursor = $results->fetchArray())
		{
			$entitiesFields[$i]['entity_id'] = '';
			$entitiesFields[$i]['fields'] = [];
			$j = 0;
			foreach($this->_fields as $field){
				$entitiesFields[$i]['fields'][$j]['fieldname'] = $field['dbFieldName'];
				$entitiesFields[$i]['fields'][$j]['value'] = $cursor[$field['dbFieldName']];
				$j++;
				
			}
	//		$results->next();
			$i++;
		}
		return $entitiesFields;
	//	return $results->fetchAll();
	}

	private function mapValuesAndFields()
	{
		//Go through all fields as given in metadata
		for($i = 0; $i < count($this->_fields); $i++)
		{
			$key = array_search($this->_fields[$i]['dbFieldName'], array_column($this->_fieldValues, 'fieldname'));

			if($key !== false)
			{
				$this->_fields[$i]['value'] = $this->_fieldValues[$key]['value'];
			}
			else{
				//echo 'couldnt find key :' . $this->_fields[$i]['dbFieldName'];
				$this->_fields[$i]['value'] = null;
			}
		}
	}

	public function ValidateValues($ignoreNulls = false)
	{
		$isValid = true;

		for($i = 0; $i < count($this->_fields); $i++)
		{
			$validator = new Validator(
				new ValidationRuleSet(
					$this->_fields[$i]['validationRegularExpression'],
					$this->_fields[$i]['required'],
					$this->_fields[$i]['validationErrorMessage']
				)
			);

			//Get validation error messages
			if(!$validator->isValid($this->_fields[$i]['value'], $ignoreNulls))
			{
//				echo $validator->GetErrorMessage() . ' ' .$this->_fields[$i]['dbFieldName'];
				$this->_fields[$i]['isValid'] = false;
				$this->_fields[$i]['errorMessage'] = $validator->GetErrorMessage();
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
		$resultSet = $this->_dbConnection->query($query);
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $result = $resultSet->fetchAll();

		if(count($result) == 0 && $newValueAllowed == true)
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
		
		if($this->_dbConnection->query($query))
		{
			return $this->_dbConnection->lastInsertId();
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