<?php
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class CommonInformationsController extends \Phalcon\Mvc\Controller {
	private $response;
	private $request;

	public function onConstruct() {
		$this->response = $this->getDI()->get('response');
		$this->request = $this->getDI()->get('request');
	}

	private function error($error_message) {
		$this->response->setStatusCode(400, 'Wrong parameters');
		$this->response->setJsonContent(['message' => $error_message]);
	}

	public function GetCollections() {
		$confLoader = new DBConfigurationLoader();
		$this->response->setJsonContent($confLoader->GetCollections());
	}

	public function GetCollection($collectionId) {
		$confLoader = new DBConfigurationLoader();
		$this->response->setJsonContent($confLoader->GetCollection($collectionId));
	}

	public function GetTasks() {
		$confLoader = new DBConfigurationLoader();
		$this->response->setJsonContent($confLoader->GetTasks());
	}

	public function GetTask($taskId) {
		$confLoader = new DBConfigurationLoader();
		$this->response->setJsonContent($confLoader->GetTask($taskId));
	}

	public function GetTaskFieldsSchema() {
		$request = $this->getDI()->get('request');

		$taskId = $request->getQuery('task_id', null, null);

		if (is_null($taskId)) {
			$this->error('task_id is required');
			return;
		}

		$task = Tasks::find(['conditions' => 'id = ' . $taskId])[0];
		$this->response->setJsonContent($task->GetTaskSchema($taskId));
	}

	public function GetUnits() {
		$request = $this->getDI()->get('request');

		$collectionId = $request->getQuery('collection_id', null, false);
		$taskId = $request->getQuery('task_id', null, null);

		if (!$collectionId) {
			$this->error('collection_id is required');
			return;
		}

		$resultSet = Units::find([
			'collection_id' => $collectionId,
		]);

		$results = [];
		$i = 0;

		$unitsConditions = is_null($taskId) ? [] : ['conditions' => 'tasks_id = ' . $taskId];

		foreach ($resultSet as $row) {
			$results[$i] = array_intersect_key($row->toArray(), array_flip(Units::$publicFields));
			$results[$i]['tasks'] = $row->getTasksUnits($unitsConditions)->toArray();
			$i++;
		}
		if (count($results) > 0) {
			$this->response->setJsonContent($results);
		} else {
			$this->response->setJsonContent([]);
		}
	}

	public function GetUnit($unitId) {
		$unit = Units::findFirst([
			'conditions' => 'id = :unitId:',
			'bind' => ['unitId' => $unitId],
		]);

		$result = [];
		$result = $unit->toArray(Units::$publicFields);
		$result['tasks'] = $unit->getTasksUnits()->toArray();

		$this->response->setJsonContent($result);
	}

	public function GetPages() {
		$request = $this->getDI()->get('request');

		$unitId = $request->getQuery('unit_id', 'int', false);
		$pageNumber = $request->getQuery('page_number', 'int', false);

		if (!$unitId) {
			$this->error('unit_id is required');
			return;
		}

		$conditions = 'unit_id = ' . $unitId;

		if ($pageNumber !== false) {
			$conditions = $conditions . ' AND page_number = ' . $pageNumber;
		}

		$resultSet = Pages::find([
			'conditions' => $conditions,
		]);

		$results = [];
		//If we want entries at the this level, uncomment this:
		/*	$i = 0;

		while($resultSet->valid()){
			$results[$i] = array_intersect_key($resultSet->current()->toArray(), array_flip(Pages::$publicFields));
			$results[$i]['entries'] = $resultSet->current()->getEntries()->toArray();
			$resultSet->next();
			$i++;
		}*/

		$results = $resultSet->toArray();

		$this->response->setJsonContent($results);
	}

	public function GetPage($pageId) {
		$page = Pages::findFirst(['id' => $pageId]);
		$taskId = $this->request->getQuery('task_id', null, null);

		$taskPageConditions = 'pages_id = ' . $pageId;
		if (!is_null($taskId)) {
			$taskPageConditions .= ' AND tasks_id = ' . $taskId;
		}
		$result = [];
		$result = $page->toArray(Pages::$publicFields);
		$result['task_page'] = TasksPages::find(['conditions' => $taskPageConditions])->toArray();
		//$result['posts'] = $page->getPosts()->toArray();

		$this->response->setJsonContent($result);
	}

	/**
	 * Retrieves the next available page, meaning the next page in the protocol
	 * for which there haven't been activity the last 5 minutes, based on the current page number
	 */
	public function GetNextAvailablePage() {
		$taskId = $this->request->getQuery('task_id', null, null);
		$unitId = $this->request->getQuery('unit_id', null, null);
		$currentPageNumber = $this->request->getQuery('current_number', 'int', 0);

		if (is_null($taskId) || is_null($unitId) || is_null($currentPageNumber)) {
			$this->error('task_id, unit_id and current_number are required');
			return;
		}
/*AND Pages.unit_id = :unit_id AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)*/
		$query = 'SELECT * FROM apacs_tasks_pages as TasksPages LEFT JOIN apacs_pages as Pages ON TasksPages.pages_id = Pages.id WHERE tasks_id = :task_id AND unit_id = :unit_id AND Pages.page_number > :current_page_number ORDER BY Pages.page_number LIMIT 1';

		$taskPage = new TasksPages();
		$result = new Resultset(null, $taskPage,
			$taskPage->getReadConnection()->query($query,
				['unit_id' => $unitId, 'task_id' => $taskId, 'current_page_number' => $currentPageNumber]
			)
		);

		$this->response->setStatusCode('200', 'OK');
		$this->response->setJsonContent($result->toArray());
	}

	public function GetActiveUsers() {
		$taskId = $this->request->getQuery('task_id', null, null);
		$unitId = $this->request->getQuery('unit_id', null, null);

		if (is_null($taskId) && is_null($unitId)) {
			$this->error('task_id or unit_id are required');
			return;
		}

//TODO: Active users are not supported yet!
		if (!is_null($taskId)) {
			$activeUsers = TasksUsers::find(['conditions' => 'task_id = taskId: AND ']);
		}
	}

	public function ImportUnits() {
		$request = $this->getDI()->get('request');

		$collectionId = $request->getPost('collection_id', null, false);

		if (!$collectionId) {
			$this->error('collection_id is required');
			return;
		}

		$type = $request->getPost('type', null, Units::OPERATION_TYPE_CREATE);

		$importer = new Units();
		$colConfig = $this->getDI()->get('configuration')->getCollection($collectionId)[0];

		if ($importer->Import($type, $collectionId, $colConfig['units_id_field'], $colConfig['units_info_field'], $colConfig['units_table'], $colConfig['units_info_condition'])) {
			$this->response->setStatusCode('201', 'Content added');
			$this->response->setJsonContent($importer->GetStatus());
		} else {
			$this->response->setStatusCode('500', 'Internal server error');
			$this->response->setJsonContent($importer->GetStatus());
		}
	}

	public function ImportPages() {
		$request = $this->getDI()->get('request');

		$collectionId = $request->getPost('collection_id', null, false);

		if (!$collectionId) {
			$this->error('collection_id is required');
			return;
		}

		$type = $request->getPost('type', null, Pages::OPERATION_TYPE_CREATE);

		$importer = new Pages();
		$colConfig = $this->getDI()->get('configuration')->getCollection($collectionId)[0];

		if ($importer->Import($type, $collectionId, $colConfig['pages_id_field'], $colConfig['pages_unit_id_field'], $colConfig['pages_table'], $colConfig['pages_image_url'], $colConfig['pages_info_condition'])) {
			$this->response->setStatusCode('201', 'Content added');
			$this->response->setJsonContent($importer->GetStatus());
		} else {
			$this->response->setStatusCode('500', 'Internal error');
			$this->response->setJsonContent($importer->GetStatus());
		}
	}
}