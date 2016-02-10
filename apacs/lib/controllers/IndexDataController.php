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
			$post->Save($jsonData['post']);

			//Saving the concrete entry
			$concreteEntry = new ConcreteEntries($this->getDI());
			$concreteId = $concreteEntry->SaveEntriesForTask($entities, $jsonData);
			$concreteEntry->SaveInSolr($concreteEntry->GetSolrData($entities, $jsonData));

			//Saving the meta entry, holding information about the concrete entry
			$entry = new Entries();
			$entry->Save(['tasks_id' => $jsonData['task_id'], 'posts_id' => $post->id, 'concrete_entries_id' => $concreteId, 'users_id' => $userId]);

		} catch (Exception $e) {
			$this->response->setStatusCode(401, 'Save error');
			$this->response->setJsonContent(['message' => 'Could not save entry ' . $e->getMessage()]);
			return;
		}

		$this->response->setStatusCode(200, 'OK');
		$this->response->setJsonContent(['message' => 'all data saved. task_id:  ' . $jsonData['task_id'] . ', post_id: ' . $post->id]);
	}

	public function GetEntries() {
		$postId = $this->request->getQuery('post_id', 'int', false);
		$taskId = $this->request->getQuery('task_id', 'int', false);

		if ($postId == false || $taskId == false) {
			$this->error('task_id and post_id must be set');
			return;
		}

		$conditions = 'posts_id = ' . $postId . ' AND tasks_id = ' . $taskId;

		$entry = Entries::findFirst(['conditions' => $conditions]);

		if ($entry === false) {
			$this->response->setJsonContent(['no entries for task and post']);
			return;
		}

		$concreteEntry = new ConcreteEntries($this->getDI());
		$concreteEntry->LoadEntries($taskId, $entry->concrete_entries_id);

		$this->response->setJsonContent($concreteEntry->LoadEntries($taskId, $entry->concrete_entries_id));
	}

	private function error($error_message) {
		$this->response->setStatusCode(400, 'Bad request');
		$this->response->setJsonContent(['message' => $error_message]);
	}
}