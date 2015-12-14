<?php
	
class IndexData extends \Phalcon\Mvc\Model
{   

	private $_errorInfo;
	private $_db;
	private $_entryType;

	/**
	 * Insert indexed data based on collection, volume and page ids and entry type.
	 * Both collection, page and entry are dynamic.
	 * Validates data, builds statement, retrieves data and executes the statement
	 * @param  int $collectionId The id of the collection
	 * @param  int $volumeId 	 The id of the volume
	 * @param  int $pageId       The id of the page on which the data belongs
	 * @param  array   $entryId      The type of entry to insert
	 */
	public function Insert($collectionId, $volumeId, $pageId, array $entryType)
	{
		/*
		Flow:
			Validate input
			Build insert statement
			Save data
			return true on success
		 */
		$this->_entryType = $entryType;
		if(!$this->getAndValidateData('get'))
			return false;

		//Let's build a prepared statement
		$sb = new InsertStatementBuilder($entryType);
		$sb->BuildStatement();

		//Retrieving the database, which is used to prepare and run the statement
		$db = $this->getDatabase();

		//$statement = $db->prepare($sb->statement);
 		$result = $db->execute($sb->statement, $this->getKeysAndValuesFromFields());

 		return true;
	}

	public function GetErrors()
	{
		return $this->_errorInfo;
	}

	private function getDatabase(){
		return $this->getDI()->get('db');
	}

	private function getKeysAndValuesFromFields()
	{
		$arr = [];
		foreach($this->_entryType['fields'] as $field){
			$arr[':'.$field['name']] = $field['value']; 
		}

		return $arr;
	}

	/**
	 * Gets and validates data based on a set of fields
	 * @param  array $fields An array of fields
	 * @param  string $type HTTP Request type
	 * @return bool Returns true if all data was received and valid
	 */
	private function getAndValidateData($type){
		$this->_errorInfo = [];
		$errorsFound = false;
		$requestObject = new Phalcon\Http\Request();
		$i = 0;
		foreach($this->_entryType['fields'] as $field){
			$validator = new Validator(new ValidationRuleSet($field['validationRegularExpression'], $field['required'], $field['validationErrorMessage']));
			$value = (new DataReceiver($requestObject))->Get($type, $field['name']);

			if(!$validator->Validate($value)){
				$this->_errorInfo[$field['name']] = ['error' => $validator->GetErrorMessage(), 'value' => $value];
				$this->_entryType['fields'][$i]['value'] = NULL;

				$errorsFound = true;
			}
			else{
				$this->_entryType['fields'][$i]['value'] = $value;
			}
			$i++;
		}
		return !$errorsFound;
	}
}