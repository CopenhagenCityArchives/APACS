<?php

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

	public function GetDataFromDatasouce($dataSourceId) {
		$query = $this->request->getQuery('q', null, null);

		$datasource = Datasources::findFirst(['conditions' => 'id = ' . $dataSourceId]);

		$this->response->setJsonContent($datasource->GetData($query));
	}

	public function SolrProxy() {
		$this->response->setContentType('application/json', 'UTF-8');
		ConcreteEntries::ProxySolrRequest();
	}

	public function ReportError() {
		$jsonData = json_decode($this->request->getRawBody(), true);
		//TODO: Implement user auth
		$reportingUserId = 1;
		$requiredFields = ['task_id', 'entity_name', 'field_name', 'concrete_entry_id', 'comment'];

		array_walk($requiredFields, function ($el) use ($requiredFields) {
			if (!isset($jsonData[$el])) {
				$this->error(implode($requiredFields) . ' must be set');
				return;
			}
		});
		var_dump($jsonData);
		$concreteEntry = new ConcreteEntries($this->getDI());
		//$entry = $concreteEntry->Load(Entities::findFirst(['conditions' => ['name' => $jsonData['entity_name'], 'tasks_id' => $jsonData['task_id']]]), 'id', $jsonData['concrete_entry_id']);

		$errors = new ErrorReports();
		$errors->reporting_user_id = $reportingUserId;
		$errors->tasks_id = $jsonData['task_id'];
		$errors->entity_name = $jsonData['entity_name'];
		$errors->field_name = $jsonData['field_name'];
		$errors->comment = $jsonData['field_name'];
		$errors->old_value = $entry[$jsonData['field_name']];

		$errors->users_id = Entries::find();

		if (!$errors->Save($jsonData)) {
			throw new Exception('could not save error: ' . implode($errors->getMessages(), ', '));
		}
	}

	public function SaveEntry() {
		//This is incomming data!
		$jsonData = json_decode($this->request->getRawBody(), true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->response->setStatusCode(401, 'Input error');
			$this->response->setJsonContent(['Invalid JSON format']);
			return;
		}

		if (count($jsonData) == 0) {
			$this->response->setStatusCode(401, 'Input error');
			$this->response->setJsonContent(['No data given']);
			return;
		}

		//TODO: Get and authorize user
		$userId = 1;

		$entitiesResult = Entities::find(['conditions' => 'task_id = ' . $jsonData['task_id']]);
		$entities = [];
		foreach ($entitiesResult as $result) {
			$entities[] = $result;
		}

		if (count($entities) == 0 || !is_array($entities)) {
			$this->response->setStatusCode(401, 'Input error');
			$this->response->setJsonContent(['No entities found for task ' . $taskId]);
			return;
		}

		try {
			//Saving the post
			$post = new Posts();
			$jsonData['post']['complete'] = 1;
			$jsonData['post']['pages_id'] = $jsonData['page_id'];
			if (!$post->Save($jsonData['post'])) {
				throw new InvalidArgumentException('Could not save post.');
			}
			$post->SaveThumbImage();

			//Saving the concrete entry
			$concreteEntry = new ConcreteEntries($this->getDI());
			$concreteId = $concreteEntry->SaveEntriesForTask($entities, $jsonData);

			//Saving the meta entry, holding information about the concrete entry
			$entry = new Entries();

			$entry->tasks_id = $jsonData['task_id'];
			$entry->posts_id = $post->id;
			$entry->concrete_entries_id = $concreteId;
			$entry->users_id = $userId;
			$entry->complete = 0;
			$entry->id = 0;

			if (!$entry->save()) {
				throw new RuntimeException('could not save entry information' . $entry->getMessages()[0]);
			}

			$solrData = [];
			$solrData['id'] = $concreteId;
			$solrData['task_id'] = $jsonData['task_id'];
			$solrData['post_id'] = $post->id;
			$solrData['entry_id'] = $entry->toArray()['id'];

			$concreteEntry->SaveInSolr(array_merge(
				$solrData, $concreteEntry->GetSolrData($entities, $jsonData)
			));
			$entry->complete = 1;
			$entry->save();

		} catch (Exception $e) {
			$this->response->setStatusCode(401, 'Save error');
			$this->response->setJsonContent(['message' => 'Could not save entry ' . $e->getMessage()]);
			return;
		}

		$this->response->setStatusCode(200, 'OK');
		$this->response->setJsonContent(['message' => 'all data saved. task_id:  ' . $jsonData['task_id'] . ', post_id: ' . $post->id]);
	}

	/**
	 * Updates part of an entry. Note that this method only supports updating one entry at a time
	 *
	 */
	public function UpdateEntry($entryId) {
		$jsonData = json_decode($this->request->getRawBody(), true);

		$concreteId = $jsonData['concrete_id'];
		$entityName = $jsonData['entity_name'];
		$fieldName = $jsonData['field_name'];
		$value = $jsonData['value'];

		$conEntry = new ConcreteEntries();
		$entityData = $conEntry->Load($entity, 'id', $concreteId);
		$entityData[$fieldName] = $value;

		if ($conEntry->Save($entity, $entityData) !== true) {
			throw new RuntimeException('could not update entry wth id: ' . $concreteId);
		}

		$this->setJsonContent(['message' => 'entry updated']);
	}

	public function GetPostEntries($id) {
		$entries = Entries::find(['conditions' => 'posts_id = ' . $id]);
		$response = [];
		foreach ($entries as $entry) {
			//Loading entry
			//	$entry = Entries::findFirstById($id);

			//Loading entities for entry
			$entities = Entities::find(['conditions' => 'task_id = ' . $entry->tasks_id]);

			//Loading concrete entry
			$concreteEntry = new ConcreteEntries($this->getDI());
			$entryData = $concreteEntry->LoadEntry($entities, $entry->concrete_entries_id);

			$response = array_merge($response, $concreteEntry->EnrichData($entities, $entryData, $entry->concrete_entries_id));
		}

		$this->response->setJsonContent($response);
	}

	private function error($error_message) {
		$this->response->setStatusCode(400, 'Bad request');
		$this->response->setJsonContent(['message' => $error_message]);
	}
}