<?php

class Entries extends \Phalcon\Mvc\Model {

	protected $id;
	protected $pagesId;
	protected $tasksId;
	protected $collectionId;

	private $_metaEntities;

	public function getSource() {
		return 'apacs_' . 'entries';
	}

	public function initialize() {
		$this->hasMany('id', 'Errors', 'entry_id');
		$this->belongsTo('page_id', 'Pages', 'id');
		$this->belongsTo('task_id', 'Tasks', 'id');
	}

	public static function SaveInSolr($data) {
		$config = [
			'endpoint' =>
			['localhost' =>
				['host' => '54.194.233.62', 'hostname' => '54.194.233.62', 'port' => 80, 'login' => '', 'path' => '/solr/apacs_core'],
			],
		];

		// create a client instance
		$client = new Solarium\Client($config);

		// get a select query instance
		//   $query = $client->createQuery($client::QUERY_SELECT);

		// $resultset = $client->execute($query);

		$update = $client->createUpdate();
		$doc1 = $update->createDocument();
		$doc1->id = rand(0, 10000000);
		$doc1->collection_id = 1;
		$doc1->page_id = 1;
		$doc1->post_id = 1;
		$doc1->task_id = 1;
		$doc1->entry_id = 1;
		$doc1->firstnames = 'testdoc-1';
		$doc1->lastname = "364";
		$doc1->birthdate = "1972-05-20T17:33:18Z";
		$doc1->birthplace = "København";

		/*     $childDoc = $update->createDocument();
			        //$childDoc = [];
			        $childDoc->id = rand(0,1000000);
			        $childDoc->address1_s ='Jens Nielsens Allé 1, st. tv., 2100 København Ø';
			        $childDoc->street = 'Jens Nielsens Allé';
			        $childDoc->parent_id = $doc1->id;
			        $childDoc->collection_id = 1;
			        $childDoc->page_id = 1;
			        $childDoc->post_id = 1;
			        $childDoc->task_id = 1;
		*/
		$doc1->address = ['Jens Nielsens Allé 1, st. tv., 2100 København Ø', 'Rådhuspladsen 13, midtfor, 1599 København K'];
		$doc1->area = ['Storstrøms Amt', 'Københavns Amt'];
		$doc1->sogn = ['Testsogn'];
		/*
			        $childDoc1 = $update->createDocument();
			       // $childDoc1 = [];
			        $childDoc1->id = rand(0,1000000);
			        $childDoc1->address2_s ='Rådhuspladsen 13, midtfor, 1599 København K';
			        $childDoc1->street = 'Rådhuspladsen';
			        $childDoc1->parent_id = $doc1->id;
			        $childDoc1->collection_id = 1;
			        $childDoc1->page_id = 1;
			        $childDoc1->post_id = 1;
			        $childDoc1->task_id = 1;
			        $childDoc1->entry_id = 1;
		*/
		//      $doc1->address_concat[] = $childDoc1->address;

		$doc2 = $update->createDocument();
		$doc2->id = rand(0, 10000000);
		$doc2->collection_id = 1;
		$doc2->page_id = 1;
		$doc2->post_id = 1;
		$doc2->task_id = 1;
		$doc2->entry_id = 1;
		$doc2->firstnames = 'testdoc-2';
		$doc2->lastname = "340";
		$doc2->birthdate = "1972-05-20T17:33:18Z";
		$doc2->birthplace = "København";
		$doc2->address = ['Hans Knudsens Plads 23, 2100 København Ø'];

		$result = $update->addDocuments([$doc1, $doc2]);
		$update->addCommit();
		$result = $client->update($update);
		//echo  $result->getStatus();

		// get a select query instance
		$query = $client->createQuery($client::QUERY_SELECT);
		// this executes the query and returns the result
		$resultset = $client->execute($query);
		// display the total number of documents found by solr
		//  echo 'NumFound: '.$resultset->getNumFound();

		$this->response->setJsonContent($resultset->GetData());
	}

	public function SaveEntry($entityId, $data) {
		$entity = Entities::findById($entityId)->toArray();

		$dbCon = $this->getDI()->get('db');
		$dbCon->start_transaction();

		try {
			Entries::SaveEntryRecursively($entity, $data, $this->getDI()->get('db'));
		} catch (Exception $d) {
			$dbCon->fallback();
		}

		return $dbCon->commit();
	}

	public static function SaveEntryRecursively($entity, $values, $dbCon) {
		$entityModel = new Entities();
		$entityTableAndFields = $entityModel->GetEntityAndFields($entity['id']);

		//Process:
		//Save entities for fields of type object
		//Save entity including ids from saved objects
		//Save entities for fields of type array with id from saved entity

		//Saving related entities with relation type object
		//foreach (array_filter($entityTableAndFields['fields'], function ($el) {return isset($el['type']) && $el['type'] == 'object';}) as $key => $objectField) {
		$tempFields = $entityModel->ConvertFieldsToAssocArray($entityTableAndFields['fields']);

		foreach ($tempFields as $key => $objectField) {

			if ($objectField['type'] !== 'object') {
				continue;
			}

			//var_dump($values, $key);
			$newId = Entries::SaveEntryRecursively($objectField, $values[$key], $dbCon);

			$values[$key] = $newId;
		}

		//Saving the entity
		$entry = new GenericEntry($entityTableAndFields, $entityTableAndFields['fields'], $dbCon);

		if (!$entry->Save($values)) {
			throw new Exception('Could not save entry');
		}

		$entryId = $entry->GetInsertId();

		//Saving related entities with relation type array
		foreach (array_filter($entity['fields'], function ($el) {return $el['type'] == 'array';}) as $arrayField) {
			$tableAndFields = $this->EntityFactory->Get($arrayField['foreignEntity']);
			$values[$tableAndFields['dbTableName']]['foreignFieldName'] = $entryId;

			Entries::SaveEntryRecursively($arrayField['foreignTableName'], $tableAndFields['fields'], $values[$tableAndFields['dbTableName']], $dbCon);
		}

		return $entryId;
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

	public static function LoadEntryRecursively($keyName, $keyValue, $entity, $dbCon) {
		$entityModel = new Entities();
		$entityTableAndFields = $entityModel->GetEntityAndFields($entity['id']);

		//Load and return a single array of all data related to the entry
		//Process:
		//Load the main entry based on the entryId
		//Load related data for object type fields by using the id from the main entry field
		//Load related data for array type fields by using the primary key of the main entry
		//var_dump($entity['fields']);
		$ge = new GenericEntry($entity, $entity['fields'], $dbCon);
		$entry = $ge->FindByValues([$keyName => $keyValue])[0];

		foreach (array_filter($entityTableAndFields['fields'], function ($el) {return isset($el['type']) && $el['type'] == 'object';}) as $key => $objectField) {

			$objectData = Entries::LoadEntryRecursively($objectField['primaryKeyFieldName'], $entry[$key], $objectField, $dbCon);

			$entry[$key] = $objectData;
		}

		//Loading related entities with relation type array
		foreach (array_filter($entity['fields'], function ($el) {return $el['type'] == 'array';}) as $arrayField) {
			/*$tableAndFields = $this->EntityFactory->Get($arrayField['foreignEntity']);
				$values[$tableAndFields['dbTableName']]['foreignFieldName'] = $entryId;

				Entries::SaveEntryRecursively($arrayField['foreignTableName'], $tableAndFields['fields'], $values[$tableAndFields['dbTableName']], $dbCon);
			*/
		}

		return $entry;
	}

	public static function ValidateJSONData($schema, $data) {
		var_dump($schema);
		// Validate
		$validator = new JsonSchema\Validator();
		$validator->check($data, $schema);

		$messages = [];
		if (!$validator->isValid()) {
			foreach ($validator->getErrors() as $error) {
				$messages[] = sprintf("[%s] %s\n", $error['property'], $error['message']);
			}
		}

		return $messages;
	}

	/**
	 * Validates an array of entities (POST data from users)
	 * @param Array $entities An array of entities with data
	 * @return bool Returns true is all fields are valid, false if not
	 */
	public function ValidateEntry(Array $entities) {
		//Array for metaEntities. Loaded as entities of specific metaEntity type is reached
		$metaEntities = [];
		$isValid = true;

		for ($j = 0; $j < count($entities); $j++) {
			$entity = $entities[$j];

			//Get metaEntity
			$this->loadMetaEntity($entity['entity_id']);

			$ge = new GenericEntry($this->metaEntities[$entity['entity_id']], $entity['fields'], $this->getDI());

			if (!$ge->ValidateData()) {
				$isValid = false;
			}

			$entities[$j] = $entity;
		}

		return $isValid;
	}

	public function UpdateEntry() {

	}

	public function LoadEntitiesByPost($postId) {
		$entries = Entries::find(['post_id' => $postId]);

		$entriesData = [];

		foreach ($entries as $entry) {
			$metaEntity = $this->loadMetaEntity($entry->entity_id);
			$ge = new GenericEntry($metaEntity, [], $this->getDI());

			$entriesData[] = $ge->Load($postId);
		}

		$this->response->setJsonContent($entriesData);
	}

	/*public function UpdateEntryData($id)
		    {
		        //This is the heavy one! What we want here is this:
		        //Get all metaEntities
		        //Get all metaFields
		        //Get concrete field data from entities
		        //Map it all together in this:
		        /*

		        {
		            entity_groups:[
		                {
		                    name: "Hovedperson",
		                    entities: [
		                        {
		                            id: 232,
		                            fields: [
		                                {
		                                    id: 3525
		                                    fieldname: fornavn,
		                                    value: Hans,
		                                    unreadable: false
		                                }
		                            ]
		                        },
		                        {
		                            id: 252,
		                            fields: [
		                                {
		                                    id: 32425,
		                                    fieldname: fornavn,
		                                    value: Jens,
		                                    unreadable: false
		                                }
		                            ]
		                        }
		                    ]
		                },
		                {
		                    name: "Ægtefæller",
		                    entities: [
		                        {
		                            id: 523,
		                            fields: [
		                                {
		                                    id: 2423,
		                                    fieldname: fornavn,
		                                    value: Jensine,
		                                    unreadable: true
		                                }
		                            ]
		                        }
		                    ]
		                }
		            ]
		        }

	*/
}