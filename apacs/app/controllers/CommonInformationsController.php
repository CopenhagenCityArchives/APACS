<?php

class CommonInformationsController extends \MainController {
	private $response;
	private $request;

	public function onConstruct() {
		$this->response = $this->getDI()->get('response');
		$this->request = $this->getDI()->get('request');
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
			$this->SetResponse(400, null, 'task_id is required');
			return;
		}

		$task = Tasks::find(['conditions' => 'id = ' . $taskId])[0];
		$this->response->setJsonContent($task->GetTaskSchema($taskId));
	}

	public function GetSearchConfig() {
		$request = $this->getDI()->get('request');
		$collectionId = $request->getQuery('collection_id', null, null);

		$conditions = '';
		if (!is_null($collectionId)) {
			$conditions = 'id = ' . $collectionId;
		}

		$collections = Collections::find($conditions);
		$result = [];
		$collections->rewind();
		while ($collections->valid()) {
			$resRow = $collections->current()->toArray();
			$resRow['fields'] = $collections->current()->GetSearchConfig();
			$result[] = $resRow;
			$collections->next();
		}

		$this->response->setJsonContent($result);
	}

	public function GetTasksUnits() {
		$indexActive = $this->request->getQuery('index_active', null, null);
		$taskId = $this->request->getQuery('task_id', null, null);

		$conditions = [];

		if (is_null($taskId) && is_null($indexActive)) {
			$this->SetResponse(403, null, ['task_id or index_active is required']);
			return;
		}

		if (!is_null($indexActive)) {
			$conditions[] = 'index_active = ' . $indexActive;
		}

		if (!is_null($taskId)) {
			$conditions[] = 'tasks_id = ' . $taskId;
		}

		$taskUnits = TasksUnits::FindUnitsAndTasks(implode(' AND ', $conditions));
		$this->response->setJsonContent($taskUnits->toArray());
	}

	public function GetUnits() {
		$request = $this->getDI()->get('request');

		$collectionId = $request->getQuery('collection_id', null, false);
		$taskId = $request->getQuery('task_id', null, null);
		$index_active = $request->getQuery('index_active', null, null);

		if (!$collectionId) {
			$this->SetResponse(403, null, ['collection_id is required']);
			return;
		}

		$conditions = '';

		if (!is_null($index_active)) {
			$conditions = $conditions . 'index_active = ' . $index_active;
		}

		if (!is_null($taskId)) {
			$conditions = $conditions . 'tasks_id = ' . $taskId;
		}

		$resultSet = Units::find([
			'collection_id' => $collectionId,
		]);

		$results = [];
		$i = 0;

		foreach ($resultSet as $row) {
			$results[$i] = array_intersect_key($row->toArray(), array_flip(Units::$publicFields));
			$results[$i]['tasks'] = $row->getTasksUnits(['conditions' => $conditions])->toArray();
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
		$pageId = $request->getQuery('page_id', 'int', false);

		$conditions = [];

		if ($unitId !== false) {
			$conditions[] = 'unit_id = ' . $unitId;
		}

		if ($pageNumber !== false) {
			$conditions[] = 'page_number = ' . $pageNumber;
		}

		if ($pageId !== false) {
			$conditions[] = 'id = ' . $pageId;
		}

		if (count($conditions) < 1) {
			$this->SetResponse(400, null, 'page_id, unit_id or page_number is required');
			return;
		}

		$resultSet = Pages::find([
			'conditions' => implode(' AND ', $conditions),
		]);

		if (count($resultSet) == 1) {
			$this->GetPage($resultSet->toArray()[0]['id']);
		} else {
			$results = $resultSet->toArray();
			$this->response->setJsonContent($results);
		}
	}

	public function GetPage($pageId) {
		$page = Pages::findFirst($pageId);
		$taskId = $this->request->getQuery('task_id', null, null);

		$taskPageConditions = 'pages_id = ' . $pageId;
		if (!is_null($taskId)) {
			$taskPageConditions .= ' AND tasks_id = ' . $taskId;
		}

		$result = $page->toArray();
		$result['task_page'] = TasksPages::find(['conditions' => $taskPageConditions, 'columns' => ['is_done', 'last_activity', 'tasks_id', 'id']])->toArray();
		$taskUnit = TasksUnits::findFirst(['conditions' => ['tasks_id = ' . $taskId]]);

		if ($taskUnit == false) {
			$this->SetResponse(400, null, 'TaskUnit not found for page id ' . $pageId);
			return;
		}
		$post = new Posts();
		$result['next_post'] = $post->GetNextPossiblePostForPage($pageId, $taskUnit->columns, $taskUnit->rows);
		$posts = Posts::find(['conditions' => 'pages_id = ' . $pageId, 'columns' => ['id', 'pages_id', 'width', 'height', 'x', 'y', 'complete']])->toArray();

		$result['posts'] = [];

		foreach ($posts as $curPos) {
			$result['posts'][] = $curPos;
		}

		$this->SetResponse(200, null, json_encode($result, JSON_NUMERIC_CHECK));
	}

	public function GetPostImage($postId) {
		$post = Posts::findFirstById($postId);

		if ($post == false) {
			throw new Exception('Post image not found for post id ' . $postId);
		}

		$this->response->setHeader('Content-type', 'image/jpeg');
		$this->response->setContent($post->image);
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
			$this->SetResponse(400, null, 'task_id, unit_id and current_number are required');
			return;
		}
		$result = TasksPages::GetNextAvailablePage($taskId, $unitId, $currentPageNumber);

		if ($result !== false) {
			$this->response->setJsonContent($result[0]);
		} else {
			$this->response->setJsonContent([]);
		}
	}

	public function GetEntry($id) {
		$entry = Entries::findFirstById($id);

		if ($entry === false) {
			$this->SetResponse(400, null, 'Entry with id ' . $id . ' not found');
			return;
		}

		$entities = Entities::find(['conditions' => 'task_id = ' . $entry->tasks_id]);

		$concreteEntry = new ConcreteEntries($this->getDI());
		$entryData = $concreteEntry->LoadEntry($entities, $entry->concrete_entries_id, true);

		$this->response->setJsonContent($entryData);
	}

	public function GetEntries() {
		$taskId = $this->request->getQuery('task_id', null, null);
		$postId = $this->request->getQuery('post_id', null, null);

		if (is_null($taskId) || is_null($postId)) {
			$this->SetResponse(400, null, 'task_id and post_id are required');
			return;
		}

		$entries = Entries::find(['conditions' => 'tasks_id = ' . $taskId . ' AND posts_id = ' . $postId]);

		if (count($entries) == 0) {
			$this->SetResponse(400, null, 'No entries found for task_id ' . $taskId . ' and post_id ' . $postId);
			return;
		}

		if (count($entries) == 1) {
			$this->GetEntry($entries->toArray()[0]['id']);
		}
	}

	public function GetPostEntries($id) {
		$response = [];

		$post = Posts::findFirstById($id);

		if (!$post) {
			$this->SetResponse(400, null, ['No post found with id ' . $id]);
			return;
		}

		$entries = $post->getEntries();

		$postData = [];
		foreach ($entries as $entry) {
			//Loading entities for entry
			$entities = Entities::find(['conditions' => 'task_id = ' . $entry->tasks_id]);

			//Loading concrete entry
			$concreteEntry = new ConcreteEntries($this->getDI());
			$entryData = $concreteEntry->LoadEntry($entities, $entry->concrete_entries_id);
			$postData = array_merge($postData, $concreteEntry->ConcatEntitiesAndData($entities, $entryData, $entry->id));
		}

		$metadata = $entries[0]->GetContext();
		unset($metadata['entry_id']);
		$response['metadata'] = $metadata;
		$response['data'] = $postData;
		$errorReports = ErrorReports::find(['conditions' => 'posts_id = ' . $id . ' AND tasks_id = ' . $entries[0]->tasks_id])->toArray();
		$response['error_reports'] = $errorReports;

		$this->SetResponse(200, null, [$response]);
	}

	public function GetErrorReports() {
		$taskId = $this->request->getQuery('task_id', null, null);
		$postId = $this->request->getQuery('post_id', null, null);
		$userId = $this->request->getQuery('relevant_user_id', null, null);

		if ((is_null($taskId) || is_null($postId)) && (is_null($userId) || is_null($taskId))) {
			$this->SetResponse(400, null, 'task_id and post_id or task_id and relevant_user_id are required');
			return;
		}

		if (!is_null($taskId) && !is_null($postId)) {
			$conditions = 'tasks_id = ' . $taskId . ' AND posts_id = ' . $postId;
		}

		if (!is_null($userId) && !is_null($taskId)) {
			$conditions = 'users_id = ' . $userId . ' AND toSuperUser != 1 AND apacs_errorreports.last_update > DATE(NOW() - INTERVAL 1 WEEK)';

			$usersTasks = SuperUsers::findFirst(['conditions' => 'users_id = :userId: AND tasks_id = :taskId:', 'bind' => ['userId' => $userId, 'taskId' => $taskId]]);

			//The user is a super user. Let's get error reports older than 7 days
			if ($usersTasks !== false) {
				$this->SetResponse(400, null, ErrorReports::findByRawSql('apacs_errorreports.last_update < DATE(NOW() - INTERVAL 1 WEEK) AND tasks_id = ' . $taskId)->toArray());
				return;
			}
		}

		$this->SetResponse(ErrorReports::findByRawSql($conditions)->toArray());
	}

	public function GetUser($userId) {
		$user = Users::findFirst($userId)->toArray();

		$user['userUserTasks'] = SuperUsers::find(['conditions' => 'users_id = :userId:', 'bind' => ['userId' => $user['id']], 'columns' => ['tasks_id']])->toArray();

		$this->SetResponse($user);
	}

	public function GetActiveUsers() {
		$taskId = $this->request->getQuery('task_id', null, null);
		$unitId = $this->request->getQuery('unit_id', null, null);

		if (is_null($taskId) && is_null($unitId)) {
			$this->SetResponse(400, null, 'task_id or unit_id are required');
			return;
		}

		$conditions = '';
		if (!is_null($taskId)) {
			$conditions = 'tasks_id = ' . $taskId;
		}

		if (!is_null($unitId)) {
			$conditions = 'units_id = ' . $unitId;
		}

		$events = new Events();
		$this->SetResponse($events->GetActiveUsers($conditions)->toArray());
	}

	public function GetUserActivities() {
		$userId = $this->request->getQuery('user_id', null, null);

		if (is_null($userId)) {
			$this->SetResponse(400, null, 'user_id is required');
			return;
		}

		$events = new Events();
		$this->SetResponse($events->GetUserActivitiesForUnits($userId)->toArray());
	}

	public function ImportUnits() {
		$request = $this->getDI()->get('request');

		$collectionId = $request->getPost('collection_id', null, false);

		if (!$collectionId) {
			$this->SetResponse(400, null, ['collection_id is required']);
			return;
		}

		$type = $request->getPost('type', null, Units::OPERATION_TYPE_CREATE);

		$importer = new Units();
		$colConfig = $this->getDI()->get('configuration')->getCollection($collectionId)[0];

		if ($importer->Import($type, $collectionId, $colConfig['units_id_field'], $colConfig['units_info_field'], $colConfig['units_table'], $colConfig['units_info_condition'])) {
			$this->SetResponse(201, null, [$importer->GetStatus()]);
		} else {
			$this->SetResponse(500, null, [$importer->GetStatus()]);
		}
	}

	public function ImportPages() {
		$request = $this->getDI()->get('request');

		$collectionId = $request->getPost('collection_id', null, false);

		if (!$collectionId) {
			$this->SetResponse(400, null, ['collection_id is required']);
			return;
		}

		$type = $request->getPost('type', null, Pages::OPERATION_TYPE_CREATE);

		$importer = new Pages();
		$colConfig = $this->getDI()->get('configuration')->getCollection($collectionId)[0];

		if ($importer->Import($type, $collectionId, $colConfig['pages_id_field'], $colConfig['pages_unit_id_field'], $colConfig['pages_table'], $colConfig['pages_image_url'], $colConfig['pages_info_condition'])) {
			$this->SetResponse(201, null, [$importer->GetStatus()]);
		} else {
			$this->SetResponse(500, null, [$importer->GetStatus()]);
		}
	}
}