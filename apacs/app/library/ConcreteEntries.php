<?php

class ConcreteEntries {

	private $id;
	private $di;
	private $crud;

	public function __construct(Phalcon\DiInterface $di = null, $crud = null) {
		$this->di = $di;
		if ($this->di == null) {
			$this->di = new Phalcon\DI\FactoryDefault();
		}

		$this->crud = $crud;
		if ($this->crud == null) {
			$this->crud = new CRUD\CRUD();
		}

		//Settings for ORM db access
		ORM::configure('mysql:host=' . $this->getDI()->get('config')['host'] . ';dbname=' . $this->getDI()->get('config')['dbname'] . ';charset=utf8;');
		ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
		ORM::configure('username', $this->getDI()->get('config')['username']);
		ORM::configure('password', $this->getDI()->get('config')['password']);
		ORM::configure('id_column', 'id');
		//This is necessary for PDO for PHP earlier than 5.3.some, as the charset=utf8 option above is ignored
		ORM::get_db()->exec("set names utf8");
		//ORM::configure('logging', true);
		//echo ORM::get_last_query();

		$this->crud = new CRUD\CRUD();
	}

	private function GetDI() {
		return $this->di;
	}

	public function Load(Entities $entity, $primaryKeyName, $id) {
		if ($entity->type == 'array') {

			return $this->buildJoins($entity)->where($primaryKeyName, $id)->find_array();
		} else {
			$result = $this->buildJoins($entity)->where($primaryKeyName, $id)->find_array();

			if (isset($result[0])) {
				foreach ($entity->fields as $field) {
					if ($field->formFieldType == 'date' && isset($result[0][$field->fieldName])) {
						$result[0][$field->fieldName] = date('d-m-Y', strtotime($result[0][$field->fieldName]));
					}
				}

				return $result[0];
			}
		}
	}

	private function buildJoins($entity) {
		$joins = ORM::for_table($entity->primaryTableName);

		//Select fields and decoded fields (if they are visible)
		foreach ($entity->fields as $field) {
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

		//We also want the foreign key to the primary entity for secondary entities
		if ($entity->isPrimaryEntity !== '1') {
			$joins = $joins->select($entity->entityKeyName);
		}

		//Adding joins
		foreach ($entity->fields as $field) {
			if ($field->hasDecode == '1') {
				$joins = $joins->left_outer_join($field->decodeTable, [$entity->primaryTableName . '.' . $field->fieldName, '=', $field->decodeTable . '.id']);
			}
		}

		return $joins;
	}

	public function ConcatEntitiesAndData($entities, $entityData, $entry_id) {

		$results = [];

		foreach ($entities as $entity) {
			//if( isset($entityData[$entity->name][0]) ){
			$data = $entityData[$entity->name];

			if ($entity->type == 'object') {
				$temp = $data;
				unset($data);
				$data = [];
				$data[] = $temp;
				//var_dump($data);
			}

			$entityRow = [];
			$entityRow['entity_name'] = $entity->name;
			$entityRow['label'] = $entity->guiName;
			$entityRow['entry_id'] = $entry_id;
			$entityRow['task_id'] = $entity->task_id;
			$entityRow['concrete_entries_id'] = isset($data[0]['id']) ? $data[0]['id'] : null;
			$entityRow['fields'] = [];
			$i = 0;
			$addFieldsAsArray = $entity->type == 'array';
			foreach ($data as $row) {
				$fieldValueRow = [];
				//Set field name and value for each field
				foreach ($entity->fields as $field) {
					if (isset($row[$field->GetRealFieldName()])) {
						$fieldValueRow['field_name'] = $field->GetRealFieldName();
						$fieldValueRow['label'] = $field->formName;
						$fieldValueRow['value'] = $row[$field->GetRealFieldName()];
						$fieldValueRow['parent_id'] = $row['id'];

						if ($addFieldsAsArray == true) {
							$entityRow['fields'][$i][] = $fieldValueRow;
						} else {
							$entityRow['fields'][] = $fieldValueRow;
						}
					}
				}
				$i++;
			}
			$results[] = $entityRow;
			//	}
		}

		return $results;
	}

	/**
	 * Loads a complete entry. Can be loaded either hierarchical (secondary entities are a part of the primary entity),
	 * or not (all entities are in the same level)
	 * @param [type]  $entities         The entities that describes the data to load
	 * @param [type]  $id               The id of the concrete entry to load
	 * @param boolean $loadhierarchical Indication of wheter or not to load the data in a hierarchical form. Defaults to false.
	 */
	public function LoadEntry($entities, $id, $loadhierarchical = false) {
		$results = [];

		/*usort($entities, function ($a, $b) {
			if ($a->viewOrder == $b->viewOrder) {
				return 0;
			}

			return ($a->viewOrder < $b->viewOrder) ? -1 : 1;
		});*/

		//Load in hierarchical form
		if ($loadhierarchical) {
			$primaryEntity = Entities::GetPrimaryEntity($entities);

			//Load primary entity
			$results[$primaryEntity->name] = $this->Load($primaryEntity, 'id', $id);

			$secondaryEntities = Entities::GetSecondaryEntities($entities);

			//Load secondary entities
			foreach ($secondaryEntities as $entity) {
				$results[$primaryEntity->name][$entity->name] = $this->Load($entity, $entity->entityKeyName, $id);
			}

			return $results;
		}

		//Load in flat form
		foreach ($entities as $entity) {
			$results[$entity->name] = $this->Load($entity, $entity->entityKeyName, $id);
		}

		return $results;
	}

	public function convertDataFromHierarchy($entities, $data) {
		$results = [];

		$primaryEntity = Entities::GetPrimaryEntity($entities);

		$results[$primaryEntity->name] = $data[$primaryEntity->name];

		foreach (Entities::GetSecondaryEntities($entities) as $entity) {
			$results[$entity->name] = $data[$primaryEntity->name][$entity->name];
			unset($results[$primaryEntity->name][$entity->name]);
		}

		return $results;
	}

	//TODO: Hardcoded!
	public function deleteConcreteEntries($oldData, $newData) {
		foreach ($oldData['persons']['deathcauses'] as $row) {
			if (!in_array($row, $newData['persons']['deathcauses'])) {
				$this->DeleteConcreteEntry('burial_persons_deathcauses', $row['id']);
				//	echo 'deleted burial_deathcauses' . $row['id'];
			}
		}

		foreach ($oldData['persons']['positions'] as $row) {
			if (!in_array($row, $newData['persons']['positions'])) {
				$this->DeleteConcreteEntry('burial_persons_positions', $row['id']);
				//	echo 'deleted burial_positions' . $row['id'];
			}
		}
	}

	//TODO: Hardcoded!
	public function removeAdditionalDataFromNewData(&$oldData, &$newData) {
		array_walk($newData['persons']['deathcauses'], [$this, 'removeIds'], $oldData['persons']['deathcauses']);
		array_walk($newData['persons']['positions'], [$this, 'removeIds'], $oldData['persons']['positions']);
	}

	private function removeIds(&$item, $key, $orgArray) {

		//If item is not found in the original array, remove all ids
		if (!in_array($item, $orgArray)) {
			unset($item['persons_id']);
			unset($item['id']);
		}
	}

	public function DeleteConcreteEntry($tableName, $id) {
		$this->crud->delete($tableName, $id);
	}

	/**
	 * Deletes a concrete entry
	 * Note that it is assumed that related tables are deleted using cascading constraints!
	 * @param Array $entities The entities array
	 * @param integer $id       the id of the concrete entry
	 */
	/*public function Delete($entities, $id) {
		$primaryEntity = Entities::GetPrimaryEntity($entities);
		$this->crud->delete($primaryEntity->primaryTableName, $id);
	}*/

	/**
	 * Saves an entry based on an entity.
	 * As entity can consist of more than one table, each field
	 * is checked for relations, and decoded if they exists
	 * @param Entities $entity The data structure defining entity
	 * @param Array $data The data to save. Only single rows are supported!
	 */
	public function Save(Entities $entity, Array $data) {

		//Decoding and saving code values
		foreach ($entity->getFields() as $field) {
			if ($field->hasDecode == 0 || !isset($data[$field->decodeField])) {
				continue;
			}

			//This is a field that needs decoding
			//Lets get the data from the decode table
			//Creating an entry consisting of the decode table and a field in the table identified by decodeField
			$fakeField = $field->toArray();
			$fakeField['fieldName'] = $field->decodeField;

			$fieldValues = $this->crud->find($field->decodeTable, $field->decodeField, $data[$field->decodeField]);

			//Value not given
			if (!isset($fieldValues[0]['id'])) {

				//new value not allowed. Throw error
				if (!$field->codeAllowNewValue) {
					throw new InvalidArgumentException('The field ' . $field->decodeField . ' has a value that does not exist: ' . $data[$fakeField['decodeField']]);
				}

				//Let's create the new value
				if (count($fieldValues) == 0) {

					$saveData = [$fakeField['fieldName'] => $data[$fakeField['fieldName']]];

					//$fieldValues = $ge->Save($saveData);
					$id = $this->crud->save($field->decodeTable, $saveData);
					//Let's use the id of the decode value
					$data[$field->fieldName] = $id;
				}
			} else {
				$data[$field->fieldName] = $fieldValues[0]['id'];
			}
		}

		$fields = $entity->fields->toArray();

		//The data is now decoded
		//We're adding another fake field: The EntityKey. This referes to the main entity of the task
		if ($entity->isPrimaryEntity != 1) {
			$entityField = new Fields();
			$entityField->fieldName = $entity->entityKeyName;

			if (!isset($data[$entityField->fieldName])) {
				throw new InvalidArgumentException('the entity cannot be saved, as there is no value for the entity key field: ' . $entityField->fieldName);
			}

			$fields[] = $entityField->toArray();
		}

		//Let's save the data
		$id = isset($data['id']) ? $data['id'] : null;

		$newId = $this->crud->save($entity->primaryTableName, $this->GetFieldsValuesArray($fields, $data), $id);
		if (!$newId) {
			throw new RuntimeException('could not save the entry ' . $entity->name);
		}
		return $newId;
	}

	private function GetFieldsValuesArray($fields, $data) {
		$fieldsAndData = [];
		foreach ($fields as $field) {
			if (isset($data[$field['fieldName']])) {

				if (trim($data[$field['fieldName']]) == '') {
					$fieldsAndData[$field['fieldName']] = null;
				} else {
					$fieldsAndData[$field['fieldName']] = $data[$field['fieldName']];
				}
				//Converting danish date to english (for database)
				//TODO: This should be implemented elsewhere...
				if ($field['formFieldType'] == 'date') {
					$fieldsAndData[$field['fieldName']] = date('Y-m-d', strtotime($fieldsAndData[$field['fieldName']]));
				}
			} else {
				if ($field['includeInForm'] == 1) {
					$fieldsAndData[$field['fieldName']] = null;
				}
			}
		}

		//TODO: HARDCODED calculation!
		if (isset($data['dateOfDeath']) && isset($data['ageYears'])) {

			$fieldsAndData['yearOfBirth'] = date('Y', strtotime($data['dateOfDeath'])) - $data['ageYears'];
		}

		return $fieldsAndData;
	}

	public function startTransaction() {
		//Let's start a transaction
		$dbCon = ORM::get_db();
		$dbCon->beginTransaction();
	}

	public function rollbackTransaction() {
		//Let's start a transaction
		$dbCon = ORM::get_db();
		$dbCon->rollBack();
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
	 * @param Array  $entities An array of Entities
	 * @param Array $data 	   The data to save
	 * @throws InvalidArgumentException if data is not set, or is not valid
	 * @throws RuntimeException if data could not be saved to the database
	 */
	public function SaveEntriesForTask($entities, $data) {
		$dbCon = ORM::get_db();

		//Save primary entity and get id
		$primaryEntity = Entities::GetPrimaryEntity($entities);

		//Saving main entity
		if (!isset($data[$primaryEntity->name])) {
			$dbCon->rollback();
			throw new InvalidArgumentException('no data given for ' . $primaryEntity->name);
		}

		if (!$primaryEntity->isDataValid($data[$primaryEntity->name])) {
			$dbCon->rollback();
			throw new InvalidArgumentException('could not save primary entity. Input error ' . $primaryEntity->GetValidationStatus());
		}

		$primaryId = $this->Save($primaryEntity, $data[$primaryEntity->name]);

		if (is_null($primaryId)) {
			$dbCon->rollback();
			throw new RuntimeException('could not get insert id for primary entity');
		}

		//foreach (array_filter($entities, function ($el) {return $el->isPrimaryEntity != '1';}) as $entity) {
		foreach (Entities::GetSecondaryEntities($entities) as $entity) {
			if (!isset($data[$primaryEntity->name][$entity->name])) {
				if ($entity->required == '1') {
					throw new InvalidArgumentException('entity data not set: ' . $entity->name);
				}
				continue;
			}

			if ($entity->type == 'object') {
				//Setting the identifier of the primary entity
				$data[$primaryEntity->name][$entity->name][$entity->entityKeyName] = $primaryId;

				if (!$entity->isDataValid($data[$primaryEntity->name][$entity->name])) {
					//$dbCon->rollback();
					throw new InvalidArgumentException('could not save single row of secondary entity ' . $entity->name . ' data. Input error ' . $primaryEntity->GetValidationStatus());
				}

				try {
					$this->Save($entity, $data[$primaryEntity->name][$entity->name]);
				} catch (Exception $e) {
					//$dbCon->rollback();
					throw new RuntimeException('Error while saving: ' . $e);
				}
			} else {
				foreach ($data[$primaryEntity->name][$entity->name] as $row) {
					//Setting the identifier of the primary entity
					$row[$entity->entityKeyName] = $primaryId;

					if (!$entity->isDataValid($row)) {
						//$dbCon->rollback();
						throw new InvalidArgumentException('could not save array row of secondary entity ' . $entity->name . ' data. Input error ' . $entity->GetValidationStatus());
					}

					try {
						$this->Save($entity, $row);
					} catch (Exception $e) {
						//$dbCon->rollback();
						throw new RuntimeException('Error while saving: ' . $e);
					}
				}
			}
		}

		//$dbCon->commit();
		return $primaryId;
	}

	public static function GetSolrDataFromEntryContext($entryCon) {
		$solrData = [];

		//Entry id is used as id in Solr
		$solrData['id'] = $entryCon['entry_id'];

		$solrData['collection_id'] = $entryCon['collection_id'];
		$solrData['task_id'] = $entryCon['task_id'];
		$solrData['unit_id'] = $entryCon['unit_id'];
		$solrData['page_id'] = $entryCon['page_id'];
		$solrData['post_id'] = $entryCon['post_id'];
		$solrData['entry_id'] = $entryCon['entry_id'];
		$solrData['user_id'] = $entryCon['user_id'];
		$solrData['user_name'] = $entryCon['user_name'];
		//$solrData['last_update'] = $entryCon['last_update'];

		$solrData['collection_info'] = $entryCon['collection_name'] . ' ' . $entryCon['unit_description'];

		return $solrData;
	}

	public static function SaveInSolr($solrData, $id = null) {
		$config = [
			'endpoint' =>
			['localhost' =>
				['host' => '54.194.89.54', 'hostname' => '54.194.89.54', 'port' => 80, 'login' => '', 'path' => '/solr/apacs_core'],
			],
		];

		// create a client instance
		$client = new Solarium\Client($config);

		$update = $client->createUpdate();

		if (!is_null($id)) {
			$update->addDeleteById($id);
			$update->addCommit();
		}

		$doc1 = $update->createDocument();

		//	$post = new Posts();
		//		$doc1->id = $solrData['id'];

		//var_dump(($solrData));

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

	public static function ProxySolrRequest() {
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

		$url = 'http://ec2-54-194-89-54.eu-west-1.compute.amazonaws.com/solr/apacs_core/select?' . $queryStr;

		print file_get_contents($url);
		exit();
	}

	/**
	 * Method for converting data to SOLR format
	 * For entities of type object and includeInSOLR = 1, all related fields with includeInSOLR = 1 is sent to
	 * SOLR in a 1:1 form, using SOLRFieldName as name. The entity itself is concated an sent to SOLR.
	 * For entities of type array and includeInSOLR = 1, all related fields with includeInSOLR = 1 is sent to
	 * SOLR in a concated form, one row pr. entity, and all values are put in arrays according to the field
	 * they belong to
	 * @param Array $entities The entities to save
	 * @param Array $data     The data to convert
	 */
	public function GetSolrData($entities, $data) {
		$solrData = [];

		$primaryEntity = Entities::GetPrimaryEntity($entities);
		//array_filter($entities, function ($el) {return $el->isPrimaryEntity;})[0];

		foreach ($entities as $entity) {
			$row = null;
			if ($entity->isPrimaryEntity == '1') {
				$row = $data[$entity->name];
			} else {
				if (isset($data[$primaryEntity->name][$entity->name])) {
					$row = $data[$primaryEntity->name][$entity->name];
				} else {
					$row = [];
				}
			}

			if (count($row) > 0) {
				if ($entity->includeInSOLR == '1') {
					$solrData[$entity->name] = $entity->ConcatDataByEntity($row);
				}
				$solrData = array_merge($solrData, $entity->ConcatDataByField($row));
			}
		}

		return $solrData;
	}
}