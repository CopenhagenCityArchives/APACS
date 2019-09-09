<?php

class CommonInformationsController extends MainController {

	//TODO: Should be removed. Use returnError in MainController instead
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

	/*
		Creates or updates a new collection in database
	*/
	public function CreateOrUpdateCollection() {
		file_put_contents('create_or_update_collections.log', 'her: ' . $this->request->getRawBody(), FILE_APPEND);
		$data = $this->GetAndValidateJsonPostData();

		if ($data['col_id'] < 50) {
			$this->response->setStatusCode(500, 'Invalid collection id');
			$this->response->setJsonContent(['error' => 'The collection id must be greater than 50!']);
			file_put_contents('create_or_update_collections.log', 'The collection id must be greater than 50!', FILE_APPEND);
			return;
		}

		$collection = new Collections();

		$data['num_of_filters'] = $data['level_count'];

		$data['id'] = $data['col_id'];
		$data['description'] = $data['info'];

		if (!$collection->save($data)) {
			$this->response->setStatusCode(500, 'Could not create or update collection');
			$this->response->setJsonContent(['error' => 'Could not create or update collection: ' . implode(', ', $collection->getMessages())]);
			file_put_contents('create_or_update_collections.log', 'Could not create or update collection: ' . implode(', ', $collection->getMessages()), FILE_APPEND);
			return;
		}

		$unit = new Units();
		$exampleUnit = $unit->findFirst('collections_id = ' . $collection->id);

		if($exampleUnit !== false){
			$first = false;
			$collection->level1_example_value = !is_null($exampleUnit->level1_value) ? $exampleUnit->level1_value : null;
			$collection->level2_example_value = !is_null($exampleUnit->level2_value) ? $exampleUnit->level2_value : null;
			$collection->level3_example_value = !is_null($exampleUnit->level3_value) ? $exampleUnit->level3_value : null;
			if (!$collection->save()) {
					//$this->response->setStatusCode(500, 'Could not create or update collection');
					//$this->response->setJsonContent(['error' => 'could not save collection with example data: ' . implode(', ', $collection->getMessages())]);
					file_put_contents('incomming_create_or_update_units.log', 'could not update collection with example unit data: ' . implode(', ', $collection->getMessages()), FILE_APPEND);
				}
		}

		$isPublic = $collection->status > 2 ? 1 : 0;
		$unit->updateIsPublicStatusByCollection($collection->id, $isPublic);

		if($isPublic){
			$unit->updatePagesCountByCollection($collection->id);
		}

		$this->response->setJsonContent($data, JSON_NUMERIC_CHECK);
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
		$request = $this->request;

		$taskId = $request->getQuery('task_id', 'int', null, true);

		if (is_null($taskId)) {
			$this->error('task_id is required');
			return;
		}

		$taskconfigLoader = new TaskConfigurationLoader2();
		$taskConf = $taskconfigLoader->getConfig($taskId);
		$entitiesCollection = new EntitiesCollection($taskConf);

		$mapper = new EntitiesCollectionToTaskSchemaMapper($entitiesCollection);
		$schema = $mapper->getSchema($taskConf['name'],$taskConf['description'], $taskConf['steps']);

		$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setJsonContent($schema);

		/*$task = Tasks::find(['conditions' => 'id = ' . $taskId])[0];

		$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setJsonContent($task->GetTaskSchema($taskId));*/
	}

	public function GetErrorReportConfig()
	{
		//$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setHeader("Content-Type", "application/json; charset=utf-8");
		$this->response->setContent(ErrorReports::GetConfig());
	}

	public function GetSearchConfig()
	{
		//$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setHeader("Content-Type", "application/json; charset=utf-8");
		$this->response->setContent(file_get_contents('../../app/config/search.json'));
	}

	public function GetTasksUnits() {
		$taskId = $this->request->getQuery('task_id', 'int', null, true);
		$unitId = $this->request->getQuery('unit_id', 'int', null, true);
		$indexActive = $this->request->getQuery('index_active', 'int', null, true);

		if (is_null($taskId)) {
			throw new InvalidArgumentException('task_id or task_id and unit_id are required');
		}

		$this->response->setJsonContent(TasksUnits::GetTasksUnitsAndActiveUsers($taskId, $unitId, $indexActive), JSON_NUMERIC_CHECK
		);
	}

	public function GetUnits() {
		$request = $this->request;

		$collectionId = $request->getQuery('collection_id', 'int', null, true);
		$taskId = $request->getQuery('task_id', 'int', null, true);
		$index_active = $request->getQuery('index_active', 'int', null, true);

		if (is_null($collectionId)) {
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

	public function CreateOrUpdateUnits() {
		file_put_contents('incomming_create_or_update_units.log', $this->request->getRawBody(), FILE_APPEND);
		$data = $this->GetAndValidateJsonPostData();

		$collection = Collections::findFirstById($data[0]['unit']['col_id']);
		if (!$collection) {
			$this->response->setStatusCode(403, 'Collection not found');
			$this->response->setJsonContent(['error' => 'No collection with id ' . $data[0]['unit']['col_id'] . ' found']);
			return;
		}

		//Delete all Units for the collection
		if(is_numeric($collection->id)){
			$this->getDI()->get('db')->delete("apacs_units", "collections_id = " . $collection->id);
		}

		foreach ($data as $row) {
			$unit = new Units();

			$unit->id = $row['unit']['col_unit_id'];
			$unit->collections_id = $row['unit']['col_id'];
			//The unit description is a concattenated version of the filter values
			$unit->description = implode(' ', array_filter([$row['unit']['level1_value'],$row['unit']['level2_value'],$row['unit']['level3_value']], function($v){ return $v !== null; }));
			$unit->pages = 0;//count(Pages::find('unit_id = ' . $row['unit']['col_unit_id']));
			$unit->updated = date('Y-m-d H:i:s');
			$unit->level1_value = $row['unit']['level1_value'];
			$unit->level1_order = $row['unit']['level1_order'];
			$unit->level2_value = $row['unit']['level2_value'] != '' ? $row['unit']['level2_value'] : null;
			$unit->level2_order = $row['unit']['level2_order'] != '' ? $row['unit']['level2_order'] : null;;
			$unit->level3_value = $row['unit']['level3_value'] != '' ? $row['unit']['level3_value'] : null;;
			$unit->level3_order = $row['unit']['level3_order'] != '' ? $row['unit']['level3_order'] : null;;

			if (!$unit->save()) {
				$this->response->setStatusCode(500, 'Could not create or update collection');
				$this->response->setJsonContent(['error' => 'could not save data: ' . implode(', ', $unit->getMessages())]);
				file_put_contents('incomming_create_or_update_units.log', 'could not save data: ' . implode(', ', $unit->getMessages()), FILE_APPEND);
				return;
			}
		}

		$this->response->setJsonContent($data, JSON_NUMERIC_CHECK);
	}

	public function GetPages() {
		$request = $this->request;
		$unitId = $request->getQuery('unit_id', 'int', null, true);
		$pageNumber = $request->getQuery('page_number', 'int', null, true);
		$pageId = $request->getQuery('page_id', 'int', null, true);

		$conditions = [];

		if (!is_null($unitId)) {
			$conditions[] = 'unit_id = ' . $unitId;
		}

		if (!is_null($pageNumber)) {
			$conditions[] = 'page_number = ' . $pageNumber;
		}

		if (!is_null($pageId)) {
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
			$this->GetPage($resultSet->toArray()[0]['id'], $resultSet[0]);
		} else {
			$results = $resultSet->toArray();
			$this->response->setJsonContent($results, JSON_NUMERIC_CHECK);
		}
	}

	public function GetPage($pageId, $page = null) {
		if (is_null($page)) {
			$page = Pages::findFirstById($pageId);
		}

		$result = $page->toArray();

		$result['task_page'] = TasksPages::find(['conditions' => 'pages_id = :pageId:', 'bind' => ['pageId' => $pageId], 'columns' => ['is_done', 'last_activity', 'tasks_id', 'id']])->toArray();

		$taskUnit = TasksUnits::findFirst(['conditions' => 'tasks_id = :taskId: AND units_id = :unitId:', 'bind' => ['unitId' => $page->unit_id, 'taskId' => $result['task_page'][0]['tasks_id']]]);
		if ($taskUnit == false) {
			throw new Exception('TaskUnit not found for page id ' . $pageId);
		}

		$post = new Posts();
		$result['next_post'] = $post->GetNextPossiblePostForPage($pageId, $taskUnit->columns, $taskUnit->rows);
		$posts = Posts::find(['conditions' => 'pages_id = ' . $pageId . ' AND complete = 1', 'columns' => ['id', 'pages_id', 'width', 'height', 'x', 'y', 'complete']]);

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

	public function CreateOrUpdatePost($id = null) {
		$this->RequireAccessControl();

		$taskId = $this->request->getQuery('task_id', 'int', null, true);
		if (is_null($taskId)) {
			throw new InvalidArgumentException("task_id required");
		}

		$input = $this->GetAndValidateJsonPostData();

		if (!$input) {
			return;
		}

		//Check if all required fields are set
		$this->CheckFields($input, ['x', 'y', 'height', 'width', 'page_id']);

		if (!is_null($id) && Posts::findFirstById($id) == false) {
			$this->returnError(400, 'Unknown post', 'The post with id ' . $id . ' does not exist');
			return;
		}

		$page = Pages::findFirst($input['page_id']);

		if($page == false){
			throw InvalidArgumentException("Page " . $input['page_id'] .  " not found");
		}

		$post = new Posts();
		$post->id = $id;
		$post->pages_id = $page->id;
		$post->x = $input['x'];
		$post->y = $input['y'];
		$post->height = $input['height'];
		$post->width = $input['width'];
		$post->complete = 0;

		if ($post->ApproximatePostExists()) {
			$this->response->setStatusCode(403, 'Approximate post already exists');
			$this->response->setJsonContent(['message' => 'Der findes allerede en post på denne placering.']);
			return;
		}

		//Saving the post
		if (!$post->save()) {
			throw new InvalidArgumentException('Could not save post.');
		}

		//Saving the thumb
		$post->SaveThumbImage();

		//Create and save event
		$event = new Events();
		$event->users_id = $this->auth->GetUserId();
		$event->units_id = $page->unit_id;
		$event->pages_id = $page->id;
		$event->posts_id = $post->id;
		$event->tasks_id = $taskId;
		$event->collections_id = $page->getUnits()->collections_id;
		$event->event_type = Events::TypeCreateUpdatePost;

		if(!$event->save()){
			$this->response->setStatusCode('500', 'could not save event');
			$this->response->setJsonContent(implode(', ', $event->getMessages()));
			return;
		}

		//Set last activity on the TaskPage, so it is not received when getting available pages
		$taskPage = TasksPages::findFirst(['conditions' => 'pages_id = ' . $page->id . ' AND tasks_id = ' . $taskId]);
		if(!is_null($taskPage)){
			$taskPage->last_activity = time();
			$taskPage->save();
		}

		$this->response->setStatusCode(200, 'Post created');
		$this->response->setJsonContent(['post_id' => $post->id]);
	}

	public function GetPostImage($postId) {
		$post = Posts::findFirstById($postId);

		if ($post == false) {
			throw new Exception('Post image not found for post id ' . $postId);
		}

		$this->response->setHeader('Content-Type', 'image/jpeg');
		$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setContent($post->image);
	}

	/**
	 * Retrieves the next available page, meaning the next page in the protocol
	 * for which there haven't been activity the last 5 minutes, based on the current page number
	 */
	public function GetNextAvailablePage() {
		$taskId = $this->request->getQuery('task_id', 'int', null, true);
		$unitId = $this->request->getQuery('unit_id', 'int', null, true);
		$currentPageNumber = $this->request->getQuery('current_number', 'int', 0, true);

		if (is_null($taskId) || is_null($unitId) || is_null($currentPageNumber)) {
			$this->error('task_id, unit_id and current_number are required');
			return;
		}
		//$result = TasksPages::GetRandomAvailablePage($taskId, $unitId, $currentPageNumber);
		$result = TasksPages::GetNextAvailablePage($taskId, $unitId, $currentPageNumber);

		if ($result !== false) {
			//$this->response->setJsonContent($result[0], JSON_NUMERIC_CHECK);
			$this->response->setJsonContent($result, JSON_NUMERIC_CHECK);
		} else {
			$this->response->setJsonContent([]);
		}
	}

	public function GetEntry($id) {
		$entry = Entries::findFirstById($id);

		if ($entry === false) {
			throw new InvalidArgumentException('entry with id ' . $id . ' not found');
		}

		$taskconfigLoader = new TaskConfigurationLoader2();
		$taskConf = $taskconfigLoader->getConfig($entry->tasks_id);
		$entitiesCollection = new EntitiesCollection($taskConf);

		$concreteEntry = new ConcreteEntries($this->getDI());
		$entryData = $concreteEntry->LoadEntry($entitiesCollection, $entry->concrete_entries_id, true);

		$entryData['post'] = Posts::findFirst(['conditions' => 'id = :id:', 'columns' => 'id,x,y,width,height, pages_id as page_id', 'bind' => ['id' => $entry->posts_id]]);
		$entryData['task_id'] = $entry->tasks_id;
		$entryData['page_id'] = $entryData['post']['page_id'];
		$entryData['post_id'] = $entry->posts_id;
		$entryData['concrete_entries_id'] = $entry->concrete_entries_id;
		//TODO: Hardcoded solr collection id
		$entryData['solr_id'] = '1-' . $entry->concrete_entries_id;

		$this->response->setJsonContent($entryData, JSON_NUMERIC_CHECK);
	}

	public function GetEntries() {
		$taskId = $this->request->getQuery('task_id', 'int', null, true);
		$postId = $this->request->getQuery('post_id', 'int', null, true);

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
		try{
			$entries = Entries::find('posts_id = ' . $id);

			if (count($entries) == 0) {
				$this->error('no entries found for post ' . $id);
				return;
			}

			$postData = [];
			foreach ($entries as $entry) {
				//Loading entities for entry
				$taskconfigLoader = new TaskConfigurationLoader2();
				$taskConf = $taskconfigLoader->getConfig($entry->tasks_id);
				$entitiesCollection = new EntitiesCollection($taskConf);

				//Loading concrete entry
				$concreteEntry = new ConcreteEntries($this->getDI());
				$entryData = $concreteEntry->LoadEntry($entitiesCollection, $entry->concrete_entries_id);
				$postData = array_merge($postData, $concreteEntry->ConcatEntitiesAndData($entitiesCollection, $entryData, $entry->id));
			}

			$metadata = $entries[0]->GetContext();

			$auth = $this->getDI()->get('AccessController');

			$metadata['user_can_edit'] = $auth->UserCanEdit($entries[0]);
			unset($metadata['entry_id']);
			$response['metadata'] = $metadata;
			$response['data'] = $postData;
			$errorReports = ErrorReports::find(['conditions' => 'posts_id = ' . $id . ' AND tasks_id = ' . $entries[0]->tasks_id . ' AND deleted = 0'])->toArray();
			$response['error_reports'] = $errorReports;
		}
		catch(Exception $e){
			throw new Exception('could not load entry: ' . $e->getMessage());
		}
		catch(TypeError $e){
			throw new Exception('could not load entry (typerror): ' . $e->getMessage());
		}

		$this->response->setJsonContent($response, JSON_NUMERIC_CHECK);
	}

	public function GetErrorReports() {

		//Assume special errors if collection id is used
		if(	!is_null($this->request->getQuery('collection_id', 'int', null, true)) &&
			!is_null($this->request->getQuery('id', 'string', null, true)) &&
			is_null($this->request->getQuery('task_id', 'int', null, true))){

			$result = SpecialErrors::find(['conditions' => 'collection_id = ' . $this->request->getQuery('collection_id') . ' AND source_id = \'' . $this->request->getQuery('id') . '\''])->toArray();

			$result = SpecialErrors::setLabels($result, $this->request->getQuery('collection_id'));

			$this->response->setJsonContent($result, JSON_NUMERIC_CHECK);
			return;
		}

		//Normal cases: Task id and post id is set
		$taskId = $this->request->getQuery('task_id', 'int', null, true);
		$postId = $this->request->getQuery('post_id', 'int', null, true);
		$userId = $this->request->getQuery('relevant_user_id', 'int', null, true);
		$errors = [];

		if ((is_null($taskId) || is_null($postId)) && is_null($userId)) {
			$this->error('collection_id and id are required for special errors. task_id and post_id or task_id and relevant_user_id are required for normal errors');
			return;
		}

		if (!is_null($taskId) && !is_null($postId)) {
			$conditions = 'tasks_id = ' . $taskId . ' AND posts_id = ' . $postId;
			$errors = ErrorReports::FindByRawSql($conditions)->toArray();
			$errors = ErrorReports::setLabels($errors);
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
			$errors = ErrorReports::setLabels($errors);
			$this->response->setJsonContent($errors, JSON_NUMERIC_CHECK);
		}

		// User id is set and task id is not set
		if (!is_null($userId) && is_null($taskId)) {
			// Get all errors for the user (where user id matches and the age is under 1 week)
			$conditions = 'users_id = ' . $userId . ' AND toSuperUser != 1 AND apacs_errorreports.last_update > DATE(NOW() - INTERVAL 1 WEEK)';
			$errors = ErrorReports::FindByRawSql($conditions)->toArray();

			// Get all the tasks that the user is superuser for
			$superUsers = SuperUsers::Find(['columns' => 'tasks_id', 'conditions' => 'users_id = :userId:', 'bind' => ['userId' => $userId]]);
			foreach ($superUsers as $superUser) {
				$conditions = '((toSuperUser = 1) OR (apacs_errorreports.last_update < DATE(NOW() - INTERVAL 1 WEEK))) AND tasks_id = ' . $superUser->tasks_id;
				$superUserErrors = ErrorReports::findByRawSql($conditions)->toArray();
				$errors = array_merge($errors, $superUserErrors);
			}
			$errors = ErrorReports::setLabels($errors);
			$this->response->setJsonContent($errors, JSON_NUMERIC_CHECK);
		}
	}

	public function GetUser($userId) {
		$user = Users::findFirst($userId);

		if(!$user){
			$this->error("User with id " . $userId . " not found");
			return;
		}

		$user = $user->toArray();

		$user['super_user_tasks'] = SuperUsers::find(['conditions' => 'users_id = :userId:', 'bind' => ['userId' => $user['id']], 'columns' => ['tasks_id']])->toArray();

		#$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setJsonContent($user, JSON_NUMERIC_CHECK);
	}

	public function GetActiveUsers() {
		$taskId = $this->request->getQuery('task_id', 'int', null, true);
		$unitId = $this->request->getQuery('unit_id', 'int', null, true);

		if (is_null($taskId) || is_null($unitId)) {
			$this->error('task_id and unit_id are required');
			return;
		}

		$events = new Events();
		$this->response->setJsonContent($events->GetActiveUsersForTaskAndUnit($taskId, $unitId));
	}

	public function GetUserActivities() {
		$userId = $this->request->getQuery('user_id', "int", null, true);

		if (is_null($userId)) {
			$this->error('user_id is required');
			return;
		}

		$events = new Events();
		$this->response->setJsonContent($events->GetUserActivitiesForUnits($userId)->toArray(), JSON_NUMERIC_CHECK);
	}

	public function GetSystemExceptions() {
		$hours = $this->request->getQuery('hours', 'int', null, true);
		$type = $this->request->getQuery('type', 'string', null, true);

		if (is_null($hours)) {
			throw new Exception('Hours are required');
		}

		if(!is_null($type)){
			$exceptions = new SystemExceptions();
			$results = $exceptions->getLastExceptionsByTypeAndHours($type, $hours);
		}
		else{
			$exceptions = new SystemExceptions();
			$results = $exceptions->getLastExceptionsByHours($hours);
		}
		$this->response->setJsonContent($results);
	}

	//TODO: Delete when starbas API is implemented
	public function ImportUnits() {
		$request = $this->request;

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

	//TODO: Delete when starbas API is implemented
	public function ImportPages() {
		$request = $this->request;

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
