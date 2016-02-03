<?php

require_once __DIR__ . '../../library/GenericEntry.php';
class IndexDataController extends \Phalcon\Mvc\Controller {
	private $config;
	private $response;
	private $request;

	private $dbCon;

	public function onConstruct() {
		$this->config = $this->getDI()->get('configuration');
		$this->response = $this->getDI()->get('response');
		$this->request = $this->getDI()->get('request');
	}

	public function test() {
		//Entries::SaveInSolr(['test' => 'this is a value']);
		$values = [
			'firstnames' => 'Niels',
			'lastname' => 'Jensen',
			'begrav_addresses' => [
				'id' => 1,
				'begrav_deathcauses' => 'Lungebetændelse',
				'begrav_streets' => [
					'id' => 1,
					'name' => 'streetname',
				],
			],
			'entry_id' => 1,
		];
		$entity = Entities::findById(1)[0]->toArray();
		Entries::SaveEntryRecursively($entity, $values, $this->getDI()->get('db'));
	}

	/*
		Flow:
			Check user access rights (authorize and authenticate)
			Get configuration for the current collection and volume
			Validate input
			Build insert statement
			Save data
			Update stats
			Return response
	*/

	public function SaveEntry($taskId) {
		//This is incomming data!
		$jsonData = json_decode(file_get_contents('php://input'), true);

		$entities = Entities::LoadEntitiesHierarchy($taskId);

		$this->dbCon = $this->getDI()->get('db');

		$errorMessages = Entries::ValidateJSONData(Tasks::GetFieldsSchema($taskId), $jsonData);
		if (count($errorMessages) > 0) {
			$this->response->setStatusCode('400', 'Input error');
			$this->response->setJsonContent(['message' => 'all data saved']);
		}

		//Saving data based on the entity hierarcy
		//We traverse the entity hierarchy and the data hierarchy in parallel
		foreach ($entities as $entity) {
			//Let's start a transaction
			$this->dbCon->begin();
			try {
				Entries::SaveEntryRecursively($this->dbCon, $entity, $jsonData);
			} catch (Exception $e) {
				$this->dbCon->rollback();
				$this->response->setStatusCode(401, 'Could not save entry');
				$this->response->setJsonContent($e);
				return;
			}

			$this->dbCon->commit();
		}

		//$ge = new GenericEntry($entities[0]['dbTableName'], $entities[0]['fields'], $this->dbCon);

		$this->response->setStatusCode('200', 'OK');
		$this->response->setJsonContent(['message' => 'all data saved']);
	}

	public function Update() {
		$dataReceiver = new DataReceiver(new Phalcon\Http\Request());
		$data = $dataReceiver->GetDataFromFields('POST', $this->config->getIndexEntity($entityId)['fields']);

		//Needed: collection id, task id, page id
		$metaInfo = $dataReceiver->GetDataFromFields('POST', ['collection_id', 'task_id', 'page_id']);

		$existingEntity = Entities::findById($metaInfo['entity_id']);
		if ($existingEntity['user_id'] != "userObj.id" && "tasksUsers.isSuperUser" !== true) {
			throw new Exception("Unauthorized access");
		}

		$entity->id = $metaInfo['entity_id'];
		$entity->collection_id = $metaInfo['collection_id'];
		$entity->task_id = $metaInfo['task_id'];
		$entity->page_id = $metaInfo['page_id'];
		$entity->data = json_encode($data, true);

		if (!$entity->save()) {
			$this->response->setStatusCode('500', 'Could not save entity');
			return $this->response;
		}

		$saver = new GenericIndex();
		if (!$saver->save($data)) {
			$this->response->setStatusCode('401', 'Could not save data');
			$this->response->setJsonContent($saver->getErrorMessages());
			return $this->response;
		}

		$this->response->setJsonContent($data);
		return $this->response;
	}

	public function Delete($id) {
		//Needed: collection id, task id, page id
		$metaInfo = $dataReceiver->GetDataFromFields('POST', ['collection_id', 'task_id', 'page_id']);

		$existingEntity = Entities::findById($metaInfo['entity_id']);
		if ($existingEntity['user_id'] != "userObj.id" && "tasksUsers.isSuperUser" !== true) {
			throw new Exception("Unauthorized access");
		}

		$model = new GenericIndex();
		$model->setContext($existingEntity);
		$model->delete();

		if ($existingEntity . delete() !== true) {
			$this->response->setStatusCode('500', 'Could not save entity');
		} else {
			$this->response->setStatusCode('401', 'Entity deleted');
		}

		return $this->response;
	}

	public function GetEntries() {
		$pageId = $this->request->getQuery('page_id', 'int', false);
		$taskId = $this->request->getQuery('task_id', 'int', false);

		if ($pageId == false) {
			$this->error('page_id must be set');
			return;
		}

		$conditions = 'page_id = ' . $pageId;

		if ($taskId !== false) {
			$conditions .= ' AND task_id = ' . $taskId;
		}

		$resultSet = Entries::find(['conditions' => $conditions]);

		$this->response->setJsonContent($resultSet->toArray());
	}

	public function GetEntry($entryId) {
		$entry = Entries::findFirst(['id' => $entryId]);
		$task = $entry->getTasks();

		$entities = $task->getEntities();

		$result = [];

		//Go through entities, get values and map them to fields
		for ($i = 0; $i < count($entities); $i++) {
			$entity = $entities[$i];

			//Load entity fields
			$query = $this->modelsManager->createQuery('SELECT f.* FROM Entities AS e LEFT JOIN EntitiesFields as ef ON e.id = ef.entity_id LEFT JOIN Fields as f ON ef.field_id = f.id WHERE e.id = ' . $entity->id);
			$fields = $query->execute()->toArray();

			//Instantiate loader for concrete entry values
			$ge = new GenericEntry($entity->dbTableName, $fields, $this->getDI());

			//Get entries based on the entity
			$entries = $entity->getEntriesEntities()->toArray();

			$values = [];
			foreach ($entries as $entry) {
				//Load data
				$data = $ge->Load($entry['id']);

				//We have the values. Let's map them to the fields
				foreach ($fields as $field) {
					$row = [];
					$row['fieldname'] = $field['dbFieldName'];
					$row['value'] = $data[$field['dbFieldName']];
					$values[] = $row;
				}
			}

			//Setting data (entity info and values)
			$result[$i] = ['entity_id' => $entity->id, 'entry_id' => $entryId];
			$result[$i]['values'] = $values;
		}

		$this->response->setJsonContent($result);
	}

	private function error($error_message) {
		$this->response->setStatusCode(400, 'Bad request');
		$this->response->setJsonContent(['message' => $error_message]);
	}
}