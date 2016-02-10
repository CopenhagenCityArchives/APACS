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

		$entitiesResult = Entities::find(['conditions' => 'task_id = ' . $taskId]);
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
			//Saving the concrete entry
			$concreteEntry = new ConcreteEntries($this->getDI());
			$concreteId = $concreteEntry->SaveEntriesForTask($entities, $jsonData);
			$concreteEntry->SaveInSolr($concreteEntry->GetSolrData($entities, $jsonData));

			//Saving the meta entry, holding information about the concrete entry
			$entry = new Entries();
			$entry->Save(['task_id' => $taskId, 'post_id' => $postId, 'concrete_id' => $concreteId, 'user_id' => $userId]);
		} catch (Exception $e) {
			$this->response->setStatusCode(401, 'Save error');
			$this->response->setJsonContent(['message' => 'Could not save entry ' . $e->getMessage()]);
			return;
		}

		$this->response->setStatusCode(200, 'OK');
		$this->response->setJsonContent(['message' => 'all data saved']);
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

	private function error($error_message) {
		$this->response->setStatusCode(400, 'Bad request');
		$this->response->setJsonContent(['message' => $error_message]);
	}
}