<?php
// ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ERROR);

class CommonInformationsController extends MainController {

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
		//file_put_contents('create_or_update_collections.log', 'her: ' . $this->request->getRawBody(), FILE_APPEND);
		$data = $this->GetAndValidateJsonPostData();

		$exception = new SystemExceptions();
		$exception->save([
			'type' => 'event_starbas_collection_save_debug',
			'details' => json_encode(['exception' => $data])
		]);

		if ($data['col_id'] < 50) {
			$this->response->setStatusCode(500, 'Invalid collection id');
			$this->response->setJsonContent(['error' => 'The collection id must be greater than 50!']);
			//file_put_contents('create_or_update_collections.log', 'The collection id must be greater than 50!', FILE_APPEND);
			
			$exception = new SystemExceptions();
			$exception->save([
				'type' => 'event_starbas_collection_save',
				'details' => json_encode(['exception' => $data])
			]);

			return;
		}

		$collection = new Collections();

		$data['num_of_filters'] = $data['level_count'];

		$data['id'] = $data['col_id'];
		$data['description'] = $data['info'];

		if (!$collection->save($data)) {
			$this->response->setStatusCode(500, 'Could not create or update collection');
			$this->response->setJsonContent(['error' => 'Could not create or update collection: ' . implode(', ', $collection->getMessages())]);

			$exception = new SystemExceptions();
			$exception->save([
				'type' => 'event_starbas_collection_save',
				'details' => json_encode(['exception' => $data])
			]);
			//file_put_contents('create_or_update_collections.log', 'Could not create or update collection: ' . implode(', ', $collection->getMessages()), FILE_APPEND);
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

		$taskId = $request->getQuery('task_id', 'int');

		if (is_null($taskId)) {
			$this->error('task_id is required');
			return;
		}

		$taskConfigLoader = new TaskConfigurationLoader();
		$taskConf = $taskConfigLoader->getConfig($taskId);
		$entity = new ConfigurationEntity($taskConf['entity']);
		$schema = TaskSchemaMapping::createTaskSchema($entity, $taskConf['name'], $taskConf['description'], $taskConf['steps']);

		$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setJsonContent($schema);
	}

	public function GetErrorReportConfig() {
		$this->response->setHeader("Content-Type", "application/json; charset=utf-8");
		$this->response->setJsonContent(ErrorReports::GetConfig());
	}

	public function GetSearchConfig() {
		$this->response->setHeader("Content-Type", "application/json; charset=utf-8");
		$this->response->setContent(file_get_contents('../../app/config/search.json'));
	}

	public function GetTasksUnits() {
		$taskId = $this->request->getQuery('task_id', 'int');
		$unitId = $this->request->getQuery('unit_id', 'int');
		$indexActive = $this->request->getQuery('index_active', 'int');

		if (is_null($taskId)) {
			throw new InvalidArgumentException('task_id or task_id and unit_id are required');
		}

		$this->response->setJsonContent(TasksUnits::GetTasksUnitsAndActiveUsers($taskId, $unitId, $indexActive), JSON_NUMERIC_CHECK
		);
	}

	public function GetUnits() {
		$request = $this->request;

		$collectionId = $request->getQuery('collection_id', 'int');
		$description = $request->getQuery('description', 'string');

		$conditions = [];
		$bindings = [];
		if (!is_null($collectionId)) {
			$conditions[] = 'collections_id = :colId:';
			$bindings['colId'] = $collectionId;
		}
		if (!is_null($description)) {
			$conditions[] = 'description LIKE :desc:';
			$bindings['desc'] = '%' . $description . '%';
		}
		
		$units = Units::find([
			'conditions' => implode(' AND ', $conditions),
			'bind' => $bindings,
			'order' => 'description',
			'limit' => 100
		]);


		$this->response->setHeader("Cache-Control", "max-age=600");

		if (count($units) > 0) {
			$this->response->setJsonContent($units->toArray(), JSON_NUMERIC_CHECK);
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
		//Save local log for debugging incomming requests
		//file_put_contents('incomming_create_or_update_units.log', $this->request->getRawBody(), FILE_APPEND);
		
		$data = $this->GetAndValidateJsonPostData();

		$exception = new SystemExceptions();
		$exception->save([
			'type' => 'event_starbas_unit_save_debug',
			'details' => json_encode(['exception' => $data])
		]);

		if(!$data){
			$errorMessage = 'Invalid JSON or no data received, nothing is saved';
			
			$this->response->setStatusCode(403, $errorMessage);
			$this->response->setJsonContent(['error'=> $errorMessage]);
			
			$exception = new SystemExceptions();
			$exception->save([
				'type' => 'event_starbas_unit_save',
				'details' => json_encode(['exception' => $errorMessage])
			]);
			
			return;
		}

		if(!is_numeric($data[0]['unit']['col_id'])){
			$errorMessage = 'No col_id given in first unit. Nothing is saved';

			$this->response->setStatusCode(403, $errorMessage);
			$this->response->setJsonContent(['error' => $errorMessage]);

			$exception = new SystemExceptions();
			$exception->save([
				'type' => 'event_starbas_unit_save',
				'details' => json_encode(['exception' => $errorMessage])
			]);

			return;
		}

		$collection = Collections::findFirstById($data[0]['unit']['col_id']);
		if (!$collection) {
			$errorMessage = 'No collection with id ' . $data[0]['unit']['col_id'] . ' found';

			$this->response->setStatusCode(403, $errorMessage);
			$this->response->setJsonContent(['error' => $errorMessage]);

			$exception = new SystemExceptions();
			$exception->save([
				'type' => 'event_starbas_unit_save',
				'details' => json_encode(['exception' => $errorMessage])
			]);

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
			$unit->is_public = 	  $row['unit']['is_public'];
			$unit->level1_value = $row['unit']['level1_value'];
			$unit->level1_order = $row['unit']['level1_order'];
			$unit->level2_value = $row['unit']['level2_value'] != '' ? $row['unit']['level2_value'] : null;
			$unit->level2_order = $row['unit']['level2_order'] != '' ? $row['unit']['level2_order'] : null;
			$unit->level3_value = $row['unit']['level3_value'] != '' ? $row['unit']['level3_value'] : null;
			$unit->level3_order = $row['unit']['level3_order'] != '' ? $row['unit']['level3_order'] : null;
			
			if (!$unit->save()) {
				$errorDetails = implode(', ', $unit->getMessages());

				$this->response->setStatusCode(500, $errorDetails);
				$this->response->setJsonContent(['error' => 'could not save data: ' . $errorDetails]);

				$exception = new SystemExceptions();
				$exception->save([
					'type' => 'event_starbas_unit_save',
					'details' => json_encode(['exception' => 'could not save data: ' . $errorDetails])
				]);

				return;
			}
		}

		$this->response->setJsonContent(['status'=>'all units saved']);
	}

	public function GetPages() {
		$request = $this->request;
		$unitId = $request->getQuery('unit_id', 'int');
		$pageNumber = $request->getQuery('page_number', 'int');
		$pageId = $request->getQuery('page_id', 'int');
		$taskId = $request->getQuery('task_id', 'int');

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

	public function GetPostAreas($postId) {
		$post = Posts::findFirst([
			'conditions' => 'id = :postId:',
			'bind' => ['postId' => $postId],
			'columns' => ['id', 'pages_id', 'width', 'height', 'x', 'y']
		])->toArray();
		
		$subposts = Subposts::find([
			'conditions' => 'posts_id = :postId:',
			'bind' => ['postId' => $postId],
			'columns' => ['id', 'posts_id', 'pages_id', 'width', 'height', 'x', 'y']
		]);
		
		$post['subposts'] = [];
		foreach ($subposts as $subpost) {
			$post['subposts'][] = $subpost->toArray();
		}

		$this->response->setContent(json_encode($post, JSON_NUMERIC_CHECK));
	}

	public function GetPageFromNumber($unitId, $pageNumber) {
		$page = Pages::findFirst([
			'conditions' => 'unit_id = :unitId: AND page_number = :pageNumber:',
			'bind' => [
				'unitId' => $unitId,
				'pageNumber' => $pageNumber
			],
		]);

		if (!$page) {
			$this->returnError(404, "Page not found", "Could not find a page with the page number " . $pageNumber . " in unit " . $unitId);
			return;
		}

		$this->response->setContent(json_encode($page, JSON_NUMERIC_CHECK));
	}

	public function GetPage($pageId, $page = null) {
		$this->RequireAccessControl();

		if (is_null($page)) {
			$page = Pages::findFirstById($pageId);
		}

		$taskId = $this->request->getQuery('task_id', 'int');
		if (is_null($taskId)) {
			$this->error('task_id is required');
			return;
		}

		$result = $page->toArray();

		$taskPage = TasksPages::findFirst(['conditions' => 'tasks_id = :taskId: AND pages_id = :pageId:', 'bind' => ['pageId' => $pageId, 'taskId' => $taskId], 'columns' => ['is_done', 'last_activity', 'tasks_id', 'id']]);
		if ($taskPage == false) {
			throw new Exception('TaskPage not found for page with id=' . $pageId);
		}
		$result['task_page'] = $taskPage->toArray();

		$taskUnit = TasksUnits::findFirst(['conditions' => 'tasks_id = :taskId: AND units_id = :unitId:', 'bind' => ['unitId' => $page->unit_id, 'taskId' => $taskId]]);
		if ($taskUnit == false) {
			throw new Exception('TaskUnit not found for page with id=' . $pageId);
		}

		$post = new Posts();
		$result['next_post'] = $post->GetNextPossiblePostForPage($pageId, $taskUnit->columns, $taskUnit->rows);
		$posts = Posts::find(['conditions' => 'pages_id = ' . $pageId . ' AND complete = 1', 'columns' => ['id', 'pages_id', 'width', 'height', 'x', 'y', 'complete']]);

		$result['posts'] = [];
		$result['subposts'] = [];

		$auth = $this->getDI()->get('AccessController');

		foreach ($posts as $curPos) {
			$postEntry = Entries::findFirst('tasks_id = ' . $taskId . ' AND posts_id = ' . $curPos->id);
			//$postEntries = $curPos->getEntries();
			$post = $curPos->toArray();

			if ($postEntry !== null) {
				$post['user_can_edit'] = $auth->UserCanEdit($postEntry);
			} else {
				$post['user_can_edit'] = false;
			}

			$subposts = Subposts::find([
				'conditions' => 'posts_id = :postId:',
				'bind' => ['postId' => $post['id']],
				'columns' => ['id', 'posts_id', 'pages_id', 'width', 'height', 'x', 'y']
			]);
			$post['subposts'] = [];
			foreach ($subposts as $subpost) {
				$post['subposts'][] = $subpost->toArray();
			}

			$result['posts'][] = $post;
		}

		$result['subposts'] = [];
		$subposts = Subposts::find([
			'conditions' => 'pages_id = :pageId:',
			'bind' => ['pageId' => $pageId],
			'columns' => ['id', 'posts_id', 'pages_id', 'width', 'height', 'x', 'y']
		]);
		foreach ($subposts as $subpost) {
			$subpostArray = $subpost->toArray();
			$subpostEntry = Entries::findFirst('tasks_id = ' . $taskId . ' AND posts_id = ' . $subpost->posts_id);

			if ($subpostEntry !== null) {
				$subpostArray['user_can_edit'] = $auth->UserCanEdit($subpostEntry);
			} else {
				$subpostArray['user_can_edit'] = false;
			}

			$result['subposts'][] = $subpostArray;
		}

		$this->response->setContent(json_encode($result, JSON_NUMERIC_CHECK));
	}

	public function DeleteSubpost($subpostId) {
		$this->RequireAccessControl();

		$subpost = Subposts::find($subpostId);

		if ($subpost) {
			$subpost->delete();
		}

		$this->response->setStatusCode(200, 'Subpost deleted');
		$this->response->setContent(json_encode(['subpost_id' => $subpostId], JSON_NUMERIC_CHECK));
	}

	public function UpdateSubpost($subpostId) {
		$this->RequireAccessControl();
		$subpost = Subposts::findFirst($subpostId);

		$data = $this->GetAndValidateJsonPostData();
		$this->CheckFields($data, ['x', 'y', 'height', 'width', 'pages_id']);

		$subpost->pages_id = $data['pages_id'];
		$subpost->x = $data['x'];
		$subpost->y = $data['y'];
		$subpost->height = $data['height'];
		$subpost->width = $data['width'];

		if (!$subpost->save()) {
			$this->error('Could not save subpost. ' . implode('. ', $subpost->getMessages()));
			return;
		}

		$this->response->setStatusCode(200, 'Subpost updated');
		$this->response->setContent(json_encode(['subpost_id' => $subpostId], JSON_NUMERIC_CHECK));
		return;
	}

	public function CreateOrUpdateSubposts($parentPostId) {
		$this->RequireAccessControl();

		$taskId = $this->request->getQuery('task_id', 'int');
		if (is_null($taskId)) {
			throw new InvalidArgumentException("task_id required");
		}

		$subposts = $this->GetAndValidateJsonPostData();
		$pages = array();

		// Verify subpost data naively
		foreach ($subposts as $subpost) {
			if (!array_key_exists('x', $subpost) ||
				!array_key_exists('y', $subpost) ||
				!array_key_exists('width', $subpost) ||
				!array_key_exists('height', $subpost) ||
				!array_key_exists('pages_id', $subpost)) {
				$this->error('Missing required data');
				return;
			}

			$pages[$subpost['pages_id']] = null;
		}

		// Populate and verify pages
		foreach (array_keys($pages) as $pagesId) {
			if (is_null($pages[$pagesId])) {
				$pageData = Pages::findFirst($pagesId);

				if ($pageData == false) {
					throw new InvalidArgumentException("Page " . $pagesId .  " not found");
				}

				$pages[$pagesId] = $pageData;
			}
		}

		$subpostIds = [];

		// Create subposts
		foreach ($subposts as $subpostData) {
			$subpost = new Subposts();
			if (array_key_exists('id', $subpostData)) {
				$subpost->id = $subpostData['id'];
			}
			$subpost->pages_id = $subpostData['pages_id'];
			$subpost->posts_id = $parentPostId;
			$subpost->x = $subpostData['x'];
			$subpost->y = $subpostData['y'];
			$subpost->height = $subpostData['height'];
			$subpost->width = $subpostData['width'];

			if (!$subpost->save()) {
				$exceptionMsg = 'Could not save subpost. ' . implode('. ', $subpost->getMessages());
				throw new InvalidArgumentException($exceptionMsg);
			}

			$subpostIds[] = $subpost->id;
	
			//Saving the thumb
			$subpost->SaveThumbImage();
		}

		$this->response->setStatusCode(200, 'Subposts created');
		$this->response->setContent(json_encode(['subpost_ids' => $subpostIds], JSON_NUMERIC_CHECK));
	}

	public function CreateOrUpdatePost($id = null) {
		$taskId = $this->request->getQuery('task_id', 'int');
		if (is_null($taskId)) {
			throw new InvalidArgumentException("task_id required");
		}

		// TODO: More finely grained permission system, to prevent hard-coding
		//       Require super user for task_id 4
		$this->RequireAccessControl(true, $taskId == 4 ? 4 : false);

		$input = $this->GetAndValidateJsonPostData();

		if (!$input) {
			return;
		}

		//Check if all required fields are set
		$this->CheckFields($input, ['x', 'y', 'height', 'width', 'page_id']);

		if (is_null($id)) {
			$post = new Posts();
			$post->created = date('Y-m-d H:i:s');
		} else {
			$post = Posts::findFirstById($id);
			if ($post == false) {
				$this->returnError(400, 'Unknown post', 'The post with id ' . $id . ' does not exist');
				return;
			}
			$post->updated = date('Y-m-d H:i:s');
		}

		$page = Pages::findFirst($input['page_id']);

		if ($page == false) {
			throw new InvalidArgumentException("Page " . $input['page_id'] .  " not found");
		}

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
			$exceptionMsg = 'Could not save post. ' . implode('. ', $post->getMessages());
			throw new InvalidArgumentException($exceptionMsg);
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
		$this->response->setContent(json_encode(['post_id' => $post->id], JSON_NUMERIC_CHECK));
	}

	//New hard Delete
	public function DeletePost($id) {
		// Requires super user privileges for any task (therefore null)
		// TODO: Should require super user for the tasks the entries belong to
		$this->RequireAccessControl(true, null);
		$taskconfigLoader = new TaskConfigurationLoader();

		try {
			$entries = Entries::find('posts_id = ' . $id);

			if (count($entries) == 0) {
				$this->error('no entries found for post ' . $id);
				return;
			}

			$errorReports = ErrorReports::find(['conditions' => 'posts_id = ' . $id . ' AND tasks_id = ' . $entries[0]->tasks_id . ' AND deleted = 0'])->toArray();
			$entry_num = [];
			$postData = [];

			foreach ($entries as $entry) {
				// Loading entities for entry
				$taskConf = $taskconfigLoader->getConfig($entry->tasks_id);
				$entityTree = new ConfigurationEntity($taskConf['entity']);
				
				// Loading concrete entry
				$concreteEntry = new ConcreteEntries($this->getDI());
				$entry_info = $concreteEntry->LoadEntry($entityTree, $entry->concrete_entries_id);
				$postData = array_merge($postData, $concreteEntry->ConcatEntitiesAndData($entityTree, $entry_info[$entityTree->name], $entry->id));
				$data = $entry_info[$entityTree->name];
				if (is_null($data)) {
					continue;
				}
				$concreteEntry->DeleteSingleEntry($entityTree, $data);
			}

			// Get values for SQL calls
			$metadata = $entries[0]->GetContext();
			$tasks_id = $entries[0]->tasks_id;
			$e_id = $metadata['entry_id'];
			$t_id = $metadata['task_id'];
			$solrId = $metadata['collection_id'] . '-' . $entry->concrete_entries_id;

			// Create and save event
			$backup = json_encode($postData, JSON_UNESCAPED_UNICODE);

			$event = new Events();
			$event->users_id = $this->auth->GetUserId();
			$event->units_id = $metadata['unit_id'];
			$event->pages_id = $metadata['page_id'];
			$event->posts_id = $metadata['post_id'];

			$event->tasks_id = $tasks_id;
			$event->collections_id = $metadata['collection_id'];
			$event->event_type = Events::TypeDeletePost;
			$event->backup = $backup;

			if (!$event->save()){
				$this->response->setStatusCode('500', 'could not save event');
				$this->response->setJsonContent(implode(', ', $event->getMessages()));
				return;
			}

			//Delete the specific post
			$deleteQuery = $this->modelsManager->createQuery('DELETE FROM Posts WHERE id = :id:');
			$deleteResult = $deleteQuery->execute(['id' => $id]);

			//Run Delete on post relations
			$deleteEntry	= $this->getDI()->get('db')->query('DELETE FROM apacs_entries WHERE posts_id = :id', ['id' => $id]);
			$deleteErrorRepo= $this->getDI()->get('db')->query('DELETE FROM apacs_errorreports WHERE posts_id = :id', ['id' => $id]);

			//data for the response
			$response['metadata'] = $metadata;
			$response['data'] = $postData;

			try {
				//Delete from Solr using post id
				ConcreteEntries::DeleteFromSolr($this->getDI()->get('solrConfig'), $solrId);
			} catch(Exception $e) {
				$exception = new SystemExceptions();
				$exception->save([
					'type' => 'event_delete_solr_error',
					'details' => json_encode(['exception' => $e->getMessage(), 'post_id' => $id, 'entry_id' => $e_id]),
				]);
				$this->error("Could not delete from solr :" . $e->getMessage());
				return;
			}
		} catch(Exception $e) {
			$this->response->setStatusCode('500', 'could not delete post ' . $e->getMessage());
			return;
		}

		$this->response->setJsonContent($response, JSON_NUMERIC_CHECK);
		$this->response->setStatusCode(200, 'Post Deleted');
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
		$taskId = $this->request->getQuery('task_id', 'int');
		$unitId = $this->request->getQuery('unit_id', 'int');
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

		$taskconfigLoader = new TaskConfigurationLoader();
		$taskConf = $taskconfigLoader->getConfig($entry->tasks_id);
		$entityTree = new ConfigurationEntity($taskConf['entity']);

		$concreteEntry = new ConcreteEntries($this->getDI());
		$entryData = $concreteEntry->LoadEntry($entityTree, $entry->concrete_entries_id, true);

		$entryData['post'] = Posts::findFirst(['conditions' => 'id = :id:', 'columns' => 'id,x,y,width,height, pages_id as page_id', 'bind' => ['id' => $entry->posts_id]]);
		$entryData['task_id'] = $entry->tasks_id;
		$entryData['page_id'] = $entryData['post']['page_id'];
		$entryData['post_id'] = $entry->posts_id;
		$entryData['concrete_entries_id'] = $entry->concrete_entries_id;
		//TODO: Hardcoded solr collection id
		$entryData['solr_id'] = '1-' . $entry->concrete_entries_id;

		$this->response->setJsonContent($entryData);
	}

	public function GetEntries() {
		$taskId = $this->request->getQuery('task_id', 'int');
		$postId = $this->request->getQuery('post_id', 'int');

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

	// TODO: What is the point of this?
	// It is used in the front end ONLY to take the entry_id
	// to then call the API 
	public function GetPostEntries($id) {
		$response = [];

		try {
			$entries = Entries::find('posts_id = ' . $id);

			if (count($entries) == 0) {
				$this->error('no entries found for post ' . $id);
				return;
			}

			$postData = [];
			foreach ($entries as $entry) {
				// Loading entities for entry
				$taskconfigLoader = new TaskConfigurationLoader();
				$taskConf = $taskconfigLoader->getConfig($entry->tasks_id);
				$entity = new ConfigurationEntity($taskConf['entity']);

				// Loading concrete entry
				$concreteEntry = new ConcreteEntries($this->getDI());
				$entryData = $concreteEntry->LoadEntry($entity, $entry->concrete_entries_id);
				$postData = array_merge($postData, $concreteEntry->ConcatEntitiesAndData($entity, $entryData[$entity->name], $entry->id));
			}

			$metadata = $entries[0]->GetContext();

			$auth = $this->getDI()->get('AccessController');

			$metadata['user_can_edit'] = $auth->UserCanEdit($entries[0]);
			$metadata['entry_id'];
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

		$collectionId = $this->request->getQuery('collection_id', 'int');
		$sourceId = $this->request->getQuery('id', 'string');
		$taskId = $this->request->getQuery('task_id', 'int');
		$postId = $this->request->getQuery('post_id', 'int');
		$userId = $this->request->getQuery('relevant_user_id', 'int');

		//Assume special errors if collection id is used
		if (!is_null($collectionId) && !is_null($sourceId) && is_null($taskId)) {
			// validate source-id field
			if (!preg_match("/^\d+-\d+$/", $sourceId, $matches)) {
				$this->error('invalid id');
				return;
			}

			$result = SpecialErrors::find(['conditions' => 'collection_id = ' . $collectionId . ' AND source_id = \'' . $sourceId . '\''])->toArray();
			$result = SpecialErrors::setLabels($result, $collectionId);

			$this->response->setJsonContent($result, JSON_NUMERIC_CHECK);
			return;
		}

		//Normal cases: Task id and post id is set
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

		// User id is set
		if (!is_null($userId)) {

			// Get all errors for the user (where user id matches, and is not deleted)
			$conditions = 'users_id = ' . $userId . ' AND deleted = 0';

			// If task id is set, user it to filter
			if (!is_null($taskId)) {
				$conditions .= ' AND tasks_id = ' . $taskId;
			}

			$errors = ErrorReports::FindByRawSql($conditions)->toArray();

			// Get all the tasks that the user is superuser for
			$superUsers = SuperUsers::Find(['columns' => 'tasks_id', 'conditions' => 'users_id = :userId:', 'bind' => ['userId' => $userId]]);
			foreach ($superUsers as $superUser) {
				// If task id is set, skip other tasks
				if (!is_null($taskId) && $taskId != $superUser->tasks_id) {
					continue;
				}

				$conditions = '((toSuperUser = 1) OR NOW() > superUserTime) AND tasks_id = ' . $superUser->tasks_id;
				$superUserErrors = ErrorReports::findByRawSql($conditions)->toArray();
				$errors = array_merge($errors, $superUserErrors);
			}
			$errors = ErrorReports::setLabels($errors);
			$this->response->setJsonContent($errors, JSON_NUMERIC_CHECK);
		}
	}

	public function GetEventEntriesForLastWeek() {
		$events = new Events();
		$this->response->setJsonContent($events->GetNumEventsForUsers(null, null, []));
	}
	public function GetEventEntries($event_type, $unix_time) {
		$taskIdsParam = $this->request->getQuery('task_ids', 'string');
		$taskIds = [];
		if (!is_null($taskIdsParam)) {
			foreach (explode(',', $taskIdsParam) as $taskId) {
				if (is_numeric($taskId)) {
					$taskIds[] = (int)$taskId;
				}
			}
		}

		$events = new Events();
		$this->response->setJsonContent($events->GetNumEventsForUsers($event_type, $unix_time, $taskIds));
	}

	public function GetSystemExceptions() {
		$hours = $this->request->getQuery('hours', 'int');
		$type = $this->request->getQuery('type', 'string');

		if (is_null($hours)) {
			$this->error('hours are required');
			return;
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

	public function healthCheck(){
		$this->response->setJsonContent(['status'=>'ok']);
	}
}
