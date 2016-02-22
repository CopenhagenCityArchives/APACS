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
		ORM::configure('mysql:host=' . $this->getDI()->get('config')['host'] . ';dbname=' . $this->getDI()->get('config')['dbname']);
		ORM::configure('username', $this->getDI()->get('config')['username']);
		ORM::configure('password', $this->getDI()->get('config')['password']);
		ORM::configure('charset', $this->getDI()->get('config')['charset']);
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
				return $result[0];
			}
		}
	}

	private function buildJoins($entity) {
		$joins = ORM::for_table($entity->primaryTableName);
		//Select visible fields

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

		//Adding joins
		foreach ($entity->fields as $field) {
			if ($field->hasDecode == '1') {
				$joins = $joins->left_outer_join($field->decodeTable, [$entity->primaryTableName . '.' . $field->fieldName, '=', $field->decodeTable . '.id']);
			}
		}

		return $joins;
	}

	public function LoadEntries($taskId, $concreteId) {
		$result = [];

		$entities = Entities::find(['conditions' => 'task_id = ' . $taskId]);

		$primaryEntity = Entities::GetPrimaryEntity($entities);

		if ($primaryEntity == null) {
			throw new InvalidArgumentException('There is no primary entity for task ' . $taskId);
		}

		$results[$primaryEntity->name] = [];

		//Load primary entity
		$results[$primaryEntity->name]['metadata'] = $primaryEntity->toArray();
		$results[$primaryEntity->name]['data'] = $this->Load($primaryEntity, 'id', $concreteId);

		$secondaryEntities = Entities::GetSecondaryEntities($entities);

		//Load secondary entities
		foreach ($secondaryEntities as $entity) {
			$results[$entity->name] = [];

			$results[$entity->name]['metadata'] = $entity->toArray();
			$results[$entity->name]['data'] = $this->Load($entity, $entity->entityKeyName, $concreteId);
		}

		return $results;
	}

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
			if ($field->hasDecode == 0) {
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
		$newId = $this->crud->save($entity->primaryTableName, $this->GetFieldsValuesArray($fields, $data));
		if (!$newId) {
			throw new RuntimeException('could not save the entry ' . $entity->name);
		}
		return $newId;
	}

	private function GetFieldsValuesArray($fields, $data) {
		$fieldsAndData = [];
		foreach ($fields as $field) {
			if (isset($data[$field['fieldName']])) {
				$fieldsAndData[$field['fieldName']] = $data[$field['fieldName']];
			}
		}
		return $fieldsAndData;
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
		//Let's start a transaction
		$dbCon = ORM::get_db();
		$dbCon->beginTransaction();

		if (!is_array($entities)) {
			throw new InvalidArgumentException('entities should be an array');
		}

		//Save primary entity and get id
		$primaryEntity = array_filter($entities, function ($el) {return $el->isPrimaryEntity == '1';})[0];

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

		foreach (array_filter($entities, function ($el) {return $el->isPrimaryEntity != '1';}) as $entity) {

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
					$dbCon->rollback();
					throw new InvalidArgumentException('could not save single row of secondary entity ' . $entity->name . ' data. Input error ' . $primaryEntity->GetValidationStatus());
				}

				try {
					$this->Save($entity, $data[$primaryEntity->name][$entity->name]);
				} catch (Exception $e) {
					$dbCon->rollback();
					throw new RuntimeException('Error while saving: ' . $e);
				}
			} else {
				foreach ($data[$primaryEntity->name][$entity->name] as $row) {
					//Setting the identifier of the primary entity
					$row[$entity->entityKeyName] = $primaryId;

					if (!$entity->isDataValid($row)) {
						$dbCon->rollback();
						throw new InvalidArgumentException('could not save array row of secondary entity ' . $entity->name . ' data. Input error ' . $entity->GetValidationStatus());
					}

					try {
						$this->Save($entity, $row);
					} catch (Exception $e) {
						$dbCon->rollback();
						throw new RuntimeException('Error while saving: ' . $e);
					}
				}
			}
		}

		$dbCon->commit();
		return $primaryId;
	}

	public static function SaveInSolr($solrData) {
		$config = [
			'endpoint' =>
			['localhost' =>
				['host' => '54.194.233.62', 'hostname' => '54.194.233.62', 'port' => 80, 'login' => '', 'path' => '/solr/apacs_core'],
			],
		];

		// create a client instance
		$client = new Solarium\Client($config);

		$update = $client->createUpdate();
		$doc1 = $update->createDocument();

		$post = new Posts();
		$doc1->id = rand(0, 100000000);

		//	var_dump(($solrData));

		foreach ($solrData as $key => $row) {
			if (strlen($key) > 0) {
				$doc1->{$key} = $row;
			}

		}

		$result = $update->addDocuments([$doc1]);
		$update->addCommit();
		$result = $client->update($update);
		return $result->getStatus();

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

		$primaryEntity = array_filter($entities, function ($el) {return $el->isPrimaryEntity;})[0];

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