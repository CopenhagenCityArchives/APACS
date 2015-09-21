<?php
	
class IndexDataModel extends \Phalcon\Mvc\Model
{   
	/**
	 * Insert indexed data based on collection, volume and page ids and entry type.
	 * Both collection, page and entry are dynamic.
	 * Validates data, builds statement, retrieves data and executes the statement
	 * @param  int $collectionId The id of the collection
	 * @param  int $volumeId 	 The id of the volume
	 * @param  int $pageId       The id of the page on which the data belongs
	 * @param  array   $entryId      The type of entry to insert
	 */
	public function insert($collectionId, $volumeId, $pageId, array $entryType)
	{
		/*
		Flow:
			Validate input
			Build insert statement
			Save data
			return true on success
		 */

	}

	/**
	 * Gets and validates data based on a set of fields
	 * @param  array $fields An array of fields
	 * @param  string $type HTTP Request type
	 * @return bool Returns true if all data was received and valid
	 */
	private function getAndValidateData(array $fields, $type, $response){
		$errors = [];
		$errorsFound = false;
		$requestObject = new Phalcon\Http\Request();
		$i = 0;
		foreach($fields as $field){
			$validator = new Validator(new ValidationRuleSet($field['validationRegularExpression'], $field['required'], $field['validationErrorMessage']));
			$value = (new DataReceiver($requestObject))->get($type, $field['name']);

			if(!$validator->Validate($value)){
				$errors[$field['name']] = $validator->getErrorMessage();
				$errorsFound = true;
			}
			else{
				$fields[$i]['value'] = $value;
			}
		}

		return $errorsFound;
	}
}