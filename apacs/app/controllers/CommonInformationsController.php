<?php

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
		$this->response->setJsonContent($confLoader->GetCollections(), JSON_NUMERIC_CHECK);
	}

	public function GetCollection($collectionId) {
		$confLoader = new DBConfigurationLoader();
		$this->response->setJsonContent($confLoader->GetCollection($collectionId), JSON_NUMERIC_CHECK);
	}

	public function GetTasks() {
		$confLoader = new DBConfigurationLoader();
		$this->response->setJsonContent($confLoader->GetTasks(), JSON_NUMERIC_CHECK);
	}

	public function GetTask($taskId) {
		$confLoader = new DBConfigurationLoader();
		$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setJsonContent($confLoader->GetTask($taskId), JSON_NUMERIC_CHECK);
	}

	public function GetTaskFieldsSchema() {
		$request = $this->getDI()->get('request');

		$taskId = $request->getQuery('task_id', null, null);

		if (is_null($taskId)) {
			$this->error('task_id is required');
			return;
		}

		$task = Tasks::find(['conditions' => 'id = ' . $taskId])[0];

		$this->response->setHeader("Cache-Control", "max-age=600");
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

		$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setJsonContent($result, JSON_NUMERIC_CHECK);
	}

	public function GetTasksUnits() {
		$indexActive = $this->request->getQuery('index_active', null, null);
		$taskId = $this->request->getQuery('task_id', null, null);

		$conditions = [];

		if (is_null($taskId) && is_null($indexActive)) {
			throw new InvalidArgumentException('task_id or index_active is required');
		}

		if (!is_null($indexActive)) {
			$conditions[] = 'index_active = ' . $indexActive;
		}

		if (!is_null($taskId)) {
			$conditions[] = 'tasks_id = ' . $taskId;
		}

		$taskUnits = TasksUnits::FindUnitsAndTasks(implode(' AND ', $conditions));

		$this->response->setJsonContent($taskUnits->toArray(), JSON_NUMERIC_CHECK);
	}

	public function GetUnits() {
		$request = $this->getDI()->get('request');

		$collectionId = $request->getQuery('collection_id', null, false);
		$taskId = $request->getQuery('task_id', null, null);
		$index_active = $request->getQuery('index_active', null, null);

		if (!$collectionId) {
			$this->error('collection_id is required');
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

		$this->response->setHeader("Cache-Control", "max-age=600");

		if (count($results) > 0) {
			$this->response->setJsonContent($results, JSON_NUMERIC_CHECK);
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

		$this->response->setJsonContent($result, JSON_NUMERIC_CHECK);
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
			$this->error('page_id, unit_id or page_number is required');
			return;
		}

		$resultSet = Pages::find([
			'conditions' => implode(' AND ', $conditions),
		]);

		if (count($resultSet) == 1) {
			$this->GetPage($resultSet->toArray()[0]['id']);
		} else {
			$results = $resultSet->toArray();
			$this->response->setJsonContent($results, JSON_NUMERIC_CHECK);
		}
	}

	public function GetPage($pageId) {
		$page = Pages::findFirstById($pageId);
		$taskId = $this->request->getQuery('task_id', null, null);

		$taskPageConditions = 'pages_id = ' . $pageId;
		if (!is_null($taskId)) {
			$taskPageConditions .= ' AND tasks_id = ' . $taskId;
		}

		$result = $page->toArray();
		$result['task_page'] = TasksPages::find(['conditions' => $taskPageConditions, 'columns' => ['is_done', 'last_activity', 'tasks_id', 'id']])->toArray();
		$taskUnit = TasksUnits::findFirst(['conditions' => ['tasks_id = ' . $taskId]]);
		if ($taskUnit == false) {
			throw new Exception('TaskUnit not found for page id ' . $pageId);
		}
		$post = new Posts();
		$result['next_post'] = $post->GetNextPossiblePostForPage($pageId, $taskUnit->columns, $taskUnit->rows);
		$posts = Posts::find(['conditions' => 'pages_id = ' . $pageId, 'columns' => ['id', 'pages_id', 'width', 'height', 'x', 'y', 'complete']]);

		$result['posts'] = [];

		$auth = $this->getDI()->get('AccessController');

		foreach ($posts as $curPos) {
			$postEntries = Entries::find('posts_id = ' . $curPos->id);
			//$postEntries = $curPos->getEntries();
			$post = $curPos->toArray();

			if (count($postEntries) > 0) {
				$post['user_can_edit'] = $auth->UserCanEdit($postEntries[0]);
			} else {
				$post['user_can_edit'] = false;
			}

			$result['posts'][] = $post;
		}

		$this->response->setContent(json_encode($result, JSON_NUMERIC_CHECK));
	}

	public function GetPostImage($postId) {
		$post = Posts::findFirstById($postId);

		if ($post == false) {
			throw new Exception('Post image not found for post id ' . $postId);
		}

		$this->response->setHeader('Content-type', 'image/jpeg');
		$this->response->setHeader("Cache-Control", "max-age=600");

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
			$this->error('task_id, unit_id and current_number are required');
			return;
		}
		$result = TasksPages::GetNextAvailablePage($taskId, $unitId, $currentPageNumber);

		if ($result !== false) {
			$this->response->setJsonContent($result[0], JSON_NUMERIC_CHECK);
		} else {
			$this->response->setJsonContent([]);
		}
	}

	public function GetEntry($id) {
		$entry = Entries::findFirstById($id);

		if ($entry === false) {
			throw new InvalidArgumentException('entry with id ' . $id . ' not found');
		}

		$entities = Entities::find(['conditions' => 'task_id = ' . $entry->tasks_id]);

		$concreteEntry = new ConcreteEntries($this->getDI());
		$entryData = $concreteEntry->LoadEntry($entities, $entry->concrete_entries_id, true);

		$entryData['post'] = Posts::findFirst(['conditions' => 'id = :id:', 'columns' => 'id,x,y,width,height, pages_id as page_id', 'bind' => ['id' => $entry->posts_id]]);
		$entryData['task_id'] = $entry->tasks_id;
		$entryData['page_id'] = $entryData['post']['page_id'];

		$this->response->setJsonContent($entryData, JSON_NUMERIC_CHECK);
	}

	public function GetEntries() {
		$taskId = $this->request->getQuery('task_id', null, null);
		$postId = $this->request->getQuery('post_id', null, null);

		if (is_null($taskId) || is_null($postId)) {
			$this->error('task_id and post_id are required');
			return;
		}

		$entries = Entries::find(['conditions' => 'tasks_id = ' . $taskId . ' AND posts_id = ' . $postId]);

		if (count($entries) == 0) {
			throw new InvalidArgumentException('No entries found for task_id ' . $taskId . ' and post_id ' . $postId);
		}

		if (count($entries) == 1) {
			$this->GetEntry($entries->toArray()[0]['id']);
		}
	}

	public function GetPostEntries($id) {
		$response = [];

		/*$post = Posts::findFirstById($id);

			if (!$post) {
				$this->error('no post found');
				return;
			}
		*/
		$entries = Entries::find('posts_id = ' . $id);

		if (count($entries) == 0) {
			$this->error('no post found');
			return;
		}

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

		$auth = $this->getDI()->get('AccessController');

		$metadata['user_can_edit'] = $auth->UserCanEdit($entries[0]);
		unset($metadata['entry_id']);
		$response['metadata'] = $metadata;
		$response['data'] = $postData;
		$errorReports = ErrorReports::find(['conditions' => 'posts_id = ' . $id . ' AND tasks_id = ' . $entries[0]->tasks_id . ' AND deleted = 0'])->toArray();
		$response['error_reports'] = $errorReports;

		$this->response->setJsonContent($response, JSON_NUMERIC_CHECK);
	}

	public function GetErrorReports() {
		$taskId = $this->request->getQuery('task_id', null, null);
		$postId = $this->request->getQuery('post_id', null, null);
		$userId = $this->request->getQuery('relevant_user_id', null, null);
		$errors = [];

		if ((is_null($taskId) || is_null($postId)) && (is_null($userId) || is_null($taskId))) {
			$this->error('task_id and post_id or task_id and relevant_user_id are required');
			return;
		}

		if (!is_null($taskId) && !is_null($postId)) {
			$conditions = 'tasks_id = ' . $taskId . ' AND posts_id = ' . $postId;
			$errors = ErrorReports::FindByRawSql($conditions)->toArray();
			$this->response->setJsonContent($errors, JSON_NUMERIC_CHECK);
		}

		//User id and task id is set
		if (!is_null($userId) && !is_null($taskId)) {
			//Get all errors for the user (where user id matches and the age is under 1 week)
			$conditions = 'users_id = ' . $userId . ' AND toSuperUser != 1 AND apacs_errorreports.last_update > DATE(NOW() - INTERVAL 1 WEEK) AND tasks_id = ' . $taskId;

			$errors = ErrorReports::FindByRawSql($conditions)->toArray();

			$superUsers = SuperUsers::findFirst(['conditions' => 'users_id = :userId: AND tasks_id = :taskId:', 'bind' => ['userId' => $userId, 'taskId' => $taskId]]);

			//The user is a super user. Let's also get error reports older than 7 days
			if ($superUsers !== false) {
				$conditions = '((toSuperUser = 1) OR (apacs_errorreports.last_update < DATE(NOW() - INTERVAL 1 WEEK))) AND tasks_id = ' . $taskId;
				$superUserErrors = ErrorReports::findByRawSql($conditions)->toArray();
				$errors = array_merge($errors, $superUserErrors);
				//$this->response->setJsonContent(ErrorReports::findByRawSql('apacs_errorreports.last_update < DATE(NOW() - INTERVAL 1 WEEK) AND tasks_id = ' . $taskId)->toArray(), JSON_NUMERIC_CHECK);
				//return;
			}
			$this->response->setJsonContent($errors, JSON_NUMERIC_CHECK);
		}
	}

	public function GetUser($userId) {
		$user = Users::findFirst($userId)->toArray();

		$user['super_user_tasks'] = SuperUsers::find(['conditions' => 'users_id = :userId:', 'bind' => ['userId' => $user['id']], 'columns' => ['tasks_id']])->toArray();

		$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setJsonContent($user, JSON_NUMERIC_CHECK);
	}

	public function GetActiveUsers() {
		$taskId = $this->request->getQuery('task_id', null, null);
		$unitId = $this->request->getQuery('unit_id', null, null);

		if (is_null($taskId) && is_null($unitId)) {
			$this->error('task_id or unit_id are required');
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
		$this->response->setJsonContent($events->GetActiveUsers($conditions)->toArray(), JSON_NUMERIC_CHECK);
	}

	public function GetUserActivities() {
		$userId = $this->request->getQuery('user_id', null, null);

		if (is_null($userId)) {
			$this->error('user_id is required');
			return;
		}

		$events = new Events();
		$this->response->setJsonContent($events->GetUserActivitiesForUnits($userId)->toArray(), JSON_NUMERIC_CHECK);
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