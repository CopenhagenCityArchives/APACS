<?php

class Entries extends \Phalcon\Mvc\Model {

	protected $id;
	protected $pagesId;
	protected $tasksId;
	protected $collectionId;

	private $_metaEntities;

	private $crud;

	public function getSource() {
		return 'apacs_' . 'entries';
	}

	public function initialize() {
		$this->hasMany('id', 'Errors', 'entry_id');
		$this->belongsTo('page_id', 'Pages', 'id');
		$this->belongsTo('task_id', 'Tasks', 'id');

		//Settings for ORM db access
		ORM::configure('mysql:host=' . $this->getDI()->get('config')['host'] . ';dbname=' . $this->getDI()->get('config')['dbname']);
		ORM::configure('username', $this->getDI()->get('config')['username']);
		ORM::configure('password', $this->getDI()->get('config')['password']);
		ORM::configure('charset', $this->getDI()->get('config')['charset']);

		$this->crud = new CRUD\CRUD();
	}

	/**
	 * Saves an entry based on an entity.
	 * As entity can consist of more than one table, each field
	 * is checked for relations, and decoded if they exists
	 * @param Entities $entity The data structure defining entity
	 * @param Array $data The data to save. Only single rows are supported!
	 */
	public function SaveEntry(Entities $entity, Array $data) {
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

			//Value not given and new value not allowed. Throw error
			if (count($fieldValues) == 0 && !$field->codeAllowNewValue) {
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
		$newId = $this->crud->save($entity->primaryTableName, $this->GetFieldsAndData($fields, $data));
		if (!$newId) {
			throw new RuntimeException('could not save the entry ' . $entity->name);
		}
		return $newId;
	}

	private function GetFieldsAndData($fields, $data) {
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
	public function SaveEntries($entities, $data) {
		//Let's start a transaction
		$dbCon = $this->getDI()->get('db');
		$dbCon->begin();

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
			throw new InvalidArgumentException('input error ' . $primaryEntity->GetValidationStatus());
		}

		$primaryId = $this->SaveEntry($primaryEntity, $data[$primaryEntity->name]);

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
					throw new InvalidArgumentException('Input error ' . $primaryEntity->GetValidationStatus());
				}

				try {
					$this->SaveEntry($entity, $data[$primaryEntity->name][$entity->name]);
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
						throw new InvalidArgumentException('Input error ' . $entity->GetValidationStatus());
					}

					try {
						$this->SaveEntry($entity, $row);
					} catch (Exception $e) {
						$dbCon->rollback();
						throw new RuntimeException('Error while saving: ' . $e);
					}
				}
			}
		}

		return $dbCon->commit();
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

	public function LoadEntry($entityId, $entryId) {
		$entity = Entities::findById($entityId)->toArray();
		$entity['fields'] = Entities::ConvertFieldsToAssocArray($entity['fields']);

		try {
			return Entries::LoadEntryRecursively('entry_id', $entryId, $entity, $this->getDI()->get('db'));
		} catch (Exception $d) {
			return false;
		}
	}

	public function LoadEntitiesByPost($postId) {
		$entries = Entries::find(['post_id' => $postId]);

		$entriesData = [];

		foreach ($entries as $entry) {
			$metaEntity = $this->loadMetaEntity($entry->entity_id);
			//$ge = new GenericEntry($metaEntity, [], $this->getDI());
			//$this->crud->load();
			$entriesData[] = $ge->Load($postId);
		}

		$this->response->setJsonContent($entriesData);
	}
}