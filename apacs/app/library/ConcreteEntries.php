<?php

// TODO: What is the purpose of this class? It contains no state/information.
// Almost all methods take an IEntity as argument -> should likely be methods on
// the entity class instead.
class ConcreteEntries {

	private $id;
	private $crud;

	public function __construct(Phalcon\DiInterface $di, $crud = null) {
		
		$this->di = $di;

		//Set crud
		$this->crud = $crud;
		
		
		//If no crud given use default and setup
		if ($this->crud == null) {
			//Settings for ORM db access
			ORM::configure('mysql:host=' . $this->getDI()->get('config')['host'] . ';dbname=' . $this->getDI()->get('config')['dbname'] . ';charset=utf8;');
			ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
			ORM::configure('username', $this->getDI()->get('config')['username']);
			ORM::configure('password', $this->getDI()->get('config')['password']);
			ORM::configure('id_column', 'id');
			//This is necessary for PDO for PHP earlier than 5.3.some, as the charset=utf8 option above is ignored
			try{
				ORM::get_db()->exec("set names utf8");
			}
			catch(Exception $e){

			}
			$this->crud = new CRUD\CRUD();
		}
	}

	private function GetDI() {
		return $this->di;
	}

	/**
	 * Handles a loaded entry for a given entity, transform certain fields, and load sub-entities.
	 * 
	 * @param IEntity $entity The entity to load an entry for.
	 * @param Array $entry The concrete entry data for the entity.
	 * 
	 * @return Array The modified entry data.
	 */
	private function HandleLoadedEntry(IEntity $entity, Array $entry) {
		foreach ($entity->getFields() as $field) {
			if ($field->formFieldType == 'boolean' && isset($entry[$field->fieldName])) {
				$entry[$field->fieldName] = $entry[$field->fieldName] ? true : false;
			}

			if ($field->formFieldType == 'date' && isset($entry[$field->fieldName])) {
				$entry[$field->fieldName] = date('d-m-Y', strtotime($entry[$field->fieldName]));
			}

			//TODO: Hardcoded! Fix when form supports decimal data types
			if(($field->fieldName == 'ageWeeks' || $field->fieldName == 'ageDays' || $field->fieldName == 'ageHours' || $field->fieldName == 'ageMonth' || $field->fieldName == 'ageYears') && isset($result[0][$field->fieldName])){
				$entry[$field->fieldName] = str_replace('.', ',', $result[0][$field->fieldName]);
			}
		}

		foreach ($entity->getChildren() as $childEntity) {
			if ($childEntity->type == 'array') {
				$entry[$childEntity->name] = $this->LoadArray($childEntity, $entry['id']);
			} else if ($childEntity->type == 'object' && isset($entry[$childEntity->entityKeyName])) {
				$entry[$childEntity->name] = $this->LoadObject($childEntity, 'id', $entry[$childEntity->entityKeyName]);
			} else {
				if ($childEntity->required == 1) {
					throw new RuntimeException('Could not load entity ' . $entity->name . ': Missing required child entity ' . $childEntity->name);
				}

				$entry[$childEntity->name] = NULL;
			}
		}

		return $entry;
	}

	/**
	 * Load related entities.
	 */
	private function LoadArray(IEntity $entity, $parentId) {
		if ($entity->type != 'array') {
			throw new InvalidArgumentException('LoadArray called with invalid entity type "' . $entity->type . '"');
		}

		$entries = $this->buildJoins($entity)->where($entity->entityKeyName, $parentId)->order_by_asc('order')->find_array();
		
		$values = [];
		foreach ($entries as $entry) {
			$values[] = $this->HandleLoadedEntry($entity, $entry);
		}

		return $values;
	}

	/**
	 * Load the data connected to an entity structure.
	 */
	private function LoadObject(IEntity $entity, string $primaryKeyName, int $id) {
		if ($entity->type != 'object') {
			throw new InvalidArgumentException("LoadObject for ". $entity->name ." called with invalid entity type '" . $entity->type . "' ");
		}

		if (!isset($primaryKeyName)) {
			throw new InvalidArgumentException("Primary key name not given in ConcreteEntries::Load for entity " . $entity->name);
		}

		$entries = $this->buildJoins($entity)->where($primaryKeyName, $id)->find_array();
		if (is_null($entries) || count($entries) == 0) {
			return null;
		}
		
		return $this->HandleLoadedEntry($entity, $entries[0]);
	}

	private function buildJoins(IEntity $entity) {
		$joins = ORM::for_table($entity->primaryTableName);
		
		//Select fields and decoded fields (if they are visible)
		foreach ($entity->getFields() as $field) {
			if ($field->includeInForm == '1') {
				if ($field->hasDecode == '1') {
					$joins = $joins->select($field->decodeTable . '.' . $field->decodeField);
				} else {
					$joins = $joins->select($field->tableName . '.' . $field->fieldName);
				}
			}
		}

		//We always want the id of the entry
		$joins = $joins->select($entity->primaryTableName . '.id');

		// For array (1-n) entities, select the field relating to the parent TODO: Check up on this
		if ($entity->type == 'array') {
			$joins = $joins->select($entity->entityKeyName);
		}

		// Select the fields that refer to (1-1) child entities TODO: Check up on this
		foreach ($entity->getChildren() as $childEntity) {
			if ($childEntity->type == 'object') {
				$joins = $joins->select($entity->primaryTableName . "." . $childEntity->entityKeyName);
			}
		}

		//Adding joins
		foreach ($entity->getFields() as $field) {
			if ($field->hasDecode == '1') {
				$joins = $joins->left_outer_join($field->decodeTable, [$entity->primaryTableName . '.' . $field->fieldName, '=', $field->decodeTable . '.id']);
			}
		}
		return $joins;
	}
	
	/**
	 * Decorate an entry with entity information.
	 */
	public function ConcatEntitiesAndData(IEntity $entity, $entityData, $entry_id) {
		$results = [];

		$merged = [];
		$merged['entity_name'] = $entity->name;
		$merged['label'] = $entity->guiName;
		$merged['entry_id'] = $entry_id;
		$merged['fields'] = [];

		if ($entity->type == 'object') {
			$merged['concrete_entries_id'] = isset($entityData['id']) ? $entityData['id'] : null;

			foreach ($entity->getFields() as $field) {
				$fieldValue = [];
				$fieldValue['field_name'] = $field->GetRealFieldName();
				$fieldValue['label'] = $field->formName;
				$fieldValue['value'] = isset($entityData[$field->GetRealFieldName()]) ? $entityData[$field->GetRealFieldName()] : null;
				$fieldValue['parent_id'] = $entityData['id'];
				$merged['fields'][] = $fieldValue;
			}
			$results[] = $merged;

			foreach ($entity->getChildren() as $child) {
				$childData = $entityData[$child->name];
				if (!isset($childData)) {
					continue;
				}

				$results = array_merge($results, $this->ConcatEntitiesAndData($child, $childData, $entry_id));
			}
		} else if ($entity->type == 'array') {
			$merged['concrete_entries_id'] = isset($entityData[0]['id']) ? $entityData[0]['id'] : null;

			$childResults = [];
			$i = 0;
			foreach ($entityData as $item) {
				foreach ($entity->getFields() as $field) {
					$fieldValue = [];
					$fieldValue['field_name'] = $field->GetRealFieldName();
					$fieldValue['label'] = $field->formName;
					$fieldValue['value'] = isset($item[$field->GetRealFieldName()]) ? $item[$field->GetRealFieldName()] : null;
					$fieldValue['parent_id'] = $item['id'];
					$merged['fields'][$i][] = $fieldValue;
				}

				foreach ($entity->getChildren() as $child) {
					$childData = $item[$entity->name];
					if (!isset($childData)) {
						continue;
					}
					
					$childResults = $this->ConcatEntitiesAndData($child, $childData, $entry_id);
				}

				$i++;
			}

			$results[] = $merged;
			$results = array_merge($results, $childResults);
		}

		return $results;
	}

	/**
	 * Loads a complete entry.
	 * 
	 * @param IEntity  $entity  The entity that describes the data to load
	 * @param int      $id      The id of the concrete entry to load
	 */
	public function LoadEntry(IEntity $entity, $id) {
		$results = [];

		// Load primary entity
		$results[$entity->name] = $this->LoadObject($entity, 'id', $id);

		return $results;
	}

	public function DeleteArrayEntries(IEntity $entity, Array $entries) {
		if ($entity->type != 'array') {
			throw new InvalidArgumentException('DeleteArrayEntries called with invalid entity type "' . $entity->type . '"');
		}

		foreach ($entries as $entry) {
			$this->DeleteSingleEntry($entity, $entry);
		}
	}

	/**
	 * Deletes a single entry of the given entity type.
	 */
	public function DeleteSingleEntry(IEntity $entity, Array $entry) {
		// First delete entries of child entities that depend on this entity
		foreach (array_filter($entity->getChildren(), function ($child) { return $child->type == "array"; }) as $childEntity) {
			$childEntries = $entry[$childEntity->name];
			$this->DeleteArrayEntries($childEntity, $childEntries);
		}

		// Delete the entry of the entity itself
		if (!isset($entry['id'])) {
			throw new InvalidArgumentException('Entry for entity ' . $entity->name . ' missing primary key value.');
		}
		$this->crud->delete($entity->primaryTableName, $entry['id']);

		// Delete entries that this entry was depending on
		foreach (array_filter($entity->getChildren(), function ($child) { return $child->type == "object"; }) as $childEntity) {
			$childEntry = $entry[$childEntity->name];

			$this->DeleteSingleEntry($childEntity, $childEntry);
		}
	}

	/**
	 * Traverses the old entry data and new entry data simultaneously, finding the
	 * subentries that have been removed from the old to the new.
	 */
	public function DeleteRemovedSubentries(IEntity $entity, Array $old, Array $new) {
		foreach ($entity->getChildren() as $child) {
			if ($child->type == 'object') {
				// We only care about children that were there before the update
				if (isset($old[$child->name])) {

					if (!isset($new[$child->name])) {
						// It was removed, so we delete it.
						$this->DeleteSingleEntry($child, $old[$child->name]);
					} else {
						// Not deleted, recurse to check children of children
						$this->DeleteRemovedSubentries($child, $old[$child->name], $new[$child->name]);
					}
				}
			} else if ($child->type == 'array') {
				// We only care about children that were there before the update
				if (isset($old[$child->name])) {

					if (!isset($new[$child->name])) {
						// It was removed, so we delete it.
						$this->DeleteSingleEntry($child, $old[$child->name]);
					} else {
						// Not deleted, recurse to check children of children
						$this->DeleteRemovedSubentries($child, $old[$child->name], $new[$child->name]);
					}
				}
			} else {
				throw new RuntimeException('Unexpected entity type ' . $child->type);
			}
		}
	}

	/**
	 * Saves an entry based on an entity.
	 * As entity can consist of more than one table, each field
	 * is checked for relations, and decoded if they exists
	 * 
	 * @param IEntity $entity  The data structure defining entity
	 * @param Array   $data    The data to save. Only single rows are supported!
	 * 
	 * @return int The primary key id of the created entry.
	 */
	public function Save(IEntity $entity, Array $data) {
		if (!$entity->isDataValid($data)) {
			throw new InvalidArgumentException('Could validate data for entity ' . $entity->name . '. Validation Error: ' . $entity->GetValidationStatus());
		}

		// Child entities that have a 1-1 relationship with the current entity.
		$dependedEntities = array_filter($entity->getChildren(), function(IEntity $child) { return $child->type != 'array'; });

		// Child entities that have a 1-n relationship with the current entity.
		$dependingEntities = array_filter($entity->getChildren(), function(IEntity $child) { return $child->type == 'array'; });

		// Save the child entities that must be saved before this one.
		$dependedsSaved = [];
		foreach ($dependedEntities as $dependedEntity) {
			// Skip or throw exception if data is missing
			if ($dependedEntity->required == '1' && (!isset($data[$dependedEntity->name]) || $dependedEntity->UserEntryIsEmpty($data[$dependedEntity->name]))) {
				throw new InvalidArgumentException('Entity data not set: ' . $entity->name);
			}

			// If entity data is not defined, it can be skipped
			if (!isset($data[$dependedEntity->name])) {
				continue;
			}

			// If the user entry is empty, delete the entry for the depended on entity, and continue
			if ($dependedEntity->UserEntryIsEmpty($data[$dependedEntity->name])) {
				// Delete entity id empty but id is set
				if (isset($data[$dependedEntity->name]['id'])){
					if ($dependedEntity->type == 'array') {
						$this->DeleteArrayEntries($dependedEntity, $data[$dependedEntity->name]);
					} else {
						$this->DeleteSingleEntry($dependedEntity, $data[$dependedEntity->name]);
					}
				}

				continue;
			}

			$dependedData = $data[$dependedEntity->name];
			$dependedsSaved[] = [
				'id' => $this->Save($dependedEntity, $dependedData),
				'entity' => $dependedEntity
			];
		}
		

		// Decoding and saving code values
		foreach ($entity->getFields() as $field) {
			// Skip fields that are not decodable
			if ($field->hasDecode == 0 || !isset($data[$field->decodeField])) {
				continue;
			}

			// Find the decoded value in the decode table
			$fieldValues = $this->crud->find($field->decodeTable, $field->decodeField, $data[$field->decodeField]);
			if (!isset($fieldValues[0]['id'])) {
				// Could not decode (ie. value does not exist)
				if (!$field->codeAllowNewValue) {
					throw new InvalidArgumentException('The field ' . $field->decodeField . ' has a value that does not exist: ' . $data[$field->decodeField]);
				}

				// Create the new value and get the set the code
				if (!is_countable($fieldValues) || count($fieldValues) == 0) {
					$saveData = [$field->decodeField => $data[$field->decodeField]];
					$id = $this->crud->save($field->decodeTable, $saveData);
					$data[$field->fieldName] = $id;
				}
			} else {
				// Decoded the value, setting the value to the code id
				$data[$field->fieldName] = $fieldValues[0]['id'];
			}
		}
		$fields = $entity->fields->toArray();

		if ($entity->isPrimaryEntity != 1 && $entity->type == 'array') {
			// If this entity is not the primary entity, but it is an array
			// it must have a reference to its parent

			$entityField = [];
			$entityField['fieldName'] = $entity->entityKeyName;
			$entityField['formFieldType'] = 'string';

			if (!isset($data[$entityField['fieldName']])) {
				throw new InvalidArgumentException('the entity cannot be saved, as there is no value for the entity key field: ' . $entityField['fieldName']);
			}

			$fields[] = $entityField;
		}

		// We are adding an 'order' field for all array entities
		if ($entity->type == 'array') {
			$orderField = [];
			$orderField['fieldName'] = 'order';
			$orderField['formFieldType'] = 'integer';

			if (!isset($data[$orderField['fieldName']])) {
				$data[$orderField['fieldName']] = 0;
			}

			$fields[] = $orderField;
		}

		// Get the data to save from the Entity fields
		$saveData = $this->CreateFieldValueAssociativeArray($fields, $data);

		// Add the ids of the depended (1-1) entities to the save data
		foreach ($dependedsSaved as $dependedSaved) {
			$saveData[$dependedSaved['entity']->entityKeyName] = $dependedSaved['id'];
		}

		// Save the entity
		$id = isset($data['id']) ? $data['id'] : null;
		$newId = $this->crud->save($entity->primaryTableName, $saveData, $id);
		if (!$newId) {
			throw new RuntimeException('Could not save the entry for the entity ' . $entity->name);
		}

		// Save the depending (1-n) entities by themselves.
		foreach ($dependingEntities as $dependingEntity) {
			if (!isset($data[$dependingEntity->name])) {
				if ($dependingEntity->required == '1') {
					throw new InvalidArgumentException('Entity data not set: ' . $entity->name);
				}

				continue;
			}

			
			$i = 0;
			// Save each item of the array
			foreach ($data[$dependingEntity->name] as $item) {
				if ($dependingEntity->UserEntryItemIsEmpty($item)) {
					continue;
				}

				$i++;

				// Set the identifier of the parent entity
				$item[$dependingEntity->entityKeyName] = $newId;

				//TODO: Hardcoded ordering based on the order of the  rows in the array
				$item['order'] = $i;

				$this->Save($dependingEntity, $item);
			}
		}
		
		return $newId;
	}

	/**
	 * Creates an associative array with keys corresponding to the field names in the
	 * given fields, and appropriately transformed values taken from the given data.
	 * 
	 * @param Array $fields The fields to take data from.
	 * @param Array $data The data for the fields.
	 * 
	 * @return Array An associative array of the transformed data, with the field names 
	 * 				 as keys.
	 */
	private function CreateFieldValueAssociativeArray(Array $fields, Array $data) {
		$result = [];
		
		foreach ($fields as $field) {
			if (!isset($field['fieldName'])) {
				throw new Exception("fieldName not set in field.");
			}

			$fieldName = $field['fieldName'];

			// The value is not defined, 
			if (!isset($data[$fieldName])) {
				if ($field['includeInForm'] == 1) {
					$assoc[$fieldName] = null;
				}
				continue;
			}

			$value = $data[$fieldName];

			if (is_string($value) && trim($value) == '') {
				$result[$fieldName] = null;
			} else {
				$result[$fieldName] = $value;
			}

			// Transform boolean values into integers [0, 1]
			if (is_bool($value)) {
				$value = $value ? 1 : 0;
			}

			//Converting danish date to english (for database)
			//TODO: This should be implemented elsewhere...
			if ($field['formFieldType'] == 'date') {
				$result[$fieldName] = date('Y-m-d', strtotime($result[$fieldName]));
			}

			//TODO: Hardcoded! Fix when form supports decimal data types
			if ($fieldName == 'ageWeeks' || $fieldName == 'ageDays' || $fieldName == 'ageHours' || $fieldName == 'ageMonth' || $fieldName == 'ageYears') {
				$result[$fieldName] = str_replace(',', '.', $result[$fieldName]);
			}
		}

		//TODO: HARDCODED calculation!
		if (isset($data['dateOfDeath']) && isset($data['ageYears'])) {
			$result['yearOfBirth'] = date('Y', strtotime($data['dateOfDeath'])) - $data['ageYears'];
		}

		return $result;
	}

	public function startTransaction() {
		//Let's start a transaction
		$dbCon = ORM::get_db();
		$dbCon->beginTransaction();
	}

	public function rollbackTransaction() {
		//Let's start a transaction
		try{
			$dbCon = ORM::get_db();
			$dbCon->rollBack();
		}
		catch(Exception $e){
			
		}
	}

	public function commitTransaction() {
		//Let's start a transaction
		$dbCon = ORM::get_db();
		$dbCon->commit();
	}
	
	/**
	 * Saving entries for a task. Each entry is validated and saved
	 * The primary entity is special, as it's insert id is used for the following entities
	 * All saving is done in a transaction, which is rolled back on error
	 * 
	 * @param IEntity $entitiy  The entity tree
	 * @param Array $data 	    The data to save
	 * @throws InvalidArgumentException if data is not set, or is not valid
	 * @throws RuntimeException if data could not be saved to the database
	 * 
	 * @return int The primary key id of the primary entry.
	 */
	public function SaveEntriesForTask(IEntity $entity, $data) {
		if (!isset($data[$entity->name])) {
			$this->rollbackTransaction();
			throw new InvalidArgumentException('Could not save entry: No data given for ' . $entity->name);
		}

		try {
			$primaryId = $this->Save($entity, $data[$entity->name]);
		} catch (Exception $e) {
			$this->rollbackTransaction();
			throw $e;
		}

		if (is_null($primaryId)) {
			$this->rollbackTransaction();
			throw new RuntimeException('Could not get insert id for primary entity');
		}

		return $primaryId;
	}

	public static function GetSolrDataFromEntryContext($entryCon, $taskId) {
		$solrData = [];

		//Entry id is used as id in Solr
		$solrData['id'] = $entryCon['entry_id'];

		$solrData['collection_id'] = $entryCon['collection_id'];
		$solrData['task_id'] = $taskId;
		$solrData['unit_id'] = $entryCon['unit_id'];
		$solrData['page_id'] = $entryCon['page_id'];
		$solrData['post_id'] = $entryCon['post_id'];
		$solrData['entry_id'] = $entryCon['entry_id'];
		$solrData['user_id'] = $entryCon['user_id'];
		$solrData['user_name'] = $entryCon['user_name'];
		$solrData['created'] = date('Y-m-d\TH:i:s.u\Z', strtotime($entryCon['created']));;
		$solrData['updated'] = date('Y-m-d\TH:i:s.u\Z', strtotime($entryCon['updated']));

		$solrData['collection_info'] = $entryCon['collection_name'];

		return $solrData;
	}

	public static function SaveInSolr($config, $solrData, $id = null) {
		$config = [
			'endpoint' =>
			['aws' =>
				['scheme' => $config['scheme'], 'host' => $config['host'], 'hostname' => $config['host'], 'port' => $config['port'], 'username' => $config['username'], 'password' => $config['password'], 'login' => '', 'path' => $config['path'], 'timeout' => $config['timeout']],
			],
		];

		// create a client instance
		$client = new Solarium\Client($config);

		$client->setDefaultEndPoint('aws');

		$update = $client->createUpdate();

		if (!is_null($id)) {
			$update->addDeleteById($id);
			$update->addCommit();
		}

		$doc1 = $update->createDocument();
		foreach ($solrData as $key => $row) {
			if (is_array($row) || (strlen($key) > 0 && strlen(trim($row)) > 0)) {
				$doc1->{$key} = $row;
			}

		}

		$result = $update->addDocuments([$doc1]);
		$update->addCommit();
		$result = $client->update($update);
		return $result->getStatus();
	}

	public static function DeleteFromSolr($config, $solr_id){
		$config = [
			'endpoint' =>
			['aws' =>
				['scheme' => $config['scheme'], 'host' => $config['host'], 'hostname' => $config['host'], 'port' => $config['port'], 'username' => $config['username'], 'password' => $config['password'], 'login' => '', 'path' => $config['path'], 'timeout' => $config['timeout']],
			],
		];

		// create a client instance
		$client = new Solarium\Client($config);

		$client->setDefaultEndPoint('aws');

		// get an update query instance
		$update = $client->createUpdate();

		// add the delete query and a commit command to the update query
		$update->addDeleteQuery('id:' . $solr_id);
		$update->addCommit();

		// this executes the query and returns the result
		$result = $client->update($update);

		return $result->getStatus();
	}

	public static function ProxySolrRequest($config) {
		header("Access-Control-Allow-Origin: *");
		header('Content-type: application/json; charset=UTF-8');
		header('Cache-Control: max-age=60');
		//header("Cache-Control: no-cache, no-store, must-revalidate");
		//header("Pragma: no-cache");
		//header("Expires: 0");

		$queryStr = $_SERVER['QUERY_STRING'];
		//$queryStr = str_replace('_url=/search?', '', $queryStr);
		$queryStr = substr($queryStr, strpos('?q=', $queryStr));
		if (strrpos($queryStr, 'delete')) {
			die();
		}

		$url = $config['host'] . $config['path'] . '/select?' . $queryStr;

		$content = @file_get_contents($url);

		if($content)
			print $content;
		else{
			print json_encode(['url' => $url]);

		}
		exit();
	}

	/**
	 * Method for converting data to SOLR format
	 * For entities of type object and includeInSOLR = 1, all related fields with includeInSOLR = 1 is sent to
	 * SOLR in a 1:1 form, using SOLRFieldName as name. The entity itself is concated and sent to SOLR.
	 * For entities of type array and includeInSOLR = 1, all related fields with includeInSOLR = 1 is sent to
	 * SOLR in a concated form, one row pr. entity, and all values are put in arrays according to the field
	 * they belong to
	 * @param IEntity $entity The entities to save
	 * @param Array $data     The data to convert
	 */
	public function GetSolrData(IEntity $entity, $data) {
		$solrData = [];

		$solrData = $entity->getDenormalizedData($data[$entity->name]);

		return $solrData;
	}
}
