<?php

use function GuzzleHttp\json_encode;
class IndexDataController extends MainController {
	private $db;

	public function GetDatasourceList(){
		$this->response->setJsonContent(Datasources::find(['columns' => ['id', 'name', 'valueField'], 'conditions' => 'isPublicEditable = 1'])->toArray());
	}

	public function authCheck(){
		$this->RequireAccessControl(true);
	}

	public function UpdateDatasourceValue($datasourceId){

		$this->RequireAccessControl(true);

		try{
			if(!$this->auth->IsSuperUser()){
				throw new Exception("Only superusers are allowed to change datasource values");
			}

			$input = $this->GetAndValidateJsonPostData();

			if(!isset($input['value']) || !isset($input['id']) || !is_numeric($input['id'])){
				throw new InvalidArgumentException('one or more of the required fields \'value\' and \'id\' is not set');
			}

			$id = isset($input['id']) ? $input['id'] : null;

			$datasource = Datasources::findFirst($datasourceId);

			$saved = false;
			$saved = $datasource->UpdateValue($input['id'], $input['value']);

			if($saved){
				$this->response->setJsonContent(['status' => 'ok']);

				//Log The change
				$dataListEvent = new DatalistEvents();
				$dataListEvent->users_id = $this->auth->GetUserId();
				$dataListEvent->datasource_id = $datasourceId;
				$dataListEvent->event_type = 'edit';
				$dataListEvent->oldValue = $input['oldValue'];
				$dataListEvent->newValue = $input['value'];
				if(!$dataListEvent->save()){
					$this->response->setStatusCode('500', 'could not save event');
					$this->response->setJsonContent(implode(', ', $dataListEvent->getMessages()));
					return;
				}
			}
			else{
				throw new Exception('could not save datasource value. '/* . implode($datasource->getMessages(), ', ')*/);
			}
		}
		catch(InvalidArgumentException $e){
			$this->response->setJsonContent(['error' => $e->getMessage()]);
			$this->response->setStatusCode(401, "Invalid argument");
		}
		catch(Exception $e){
			$this->response->setJsonContent(['error' => $e->getMessage()]);
			$this->response->setStatusCode(500, "Server error");
		}
	}

	public function CreateDatasourceValue($datasourceId){

		$this->RequireAccessControl(true);

		try{
			if(!$this->auth->IsSuperUser()){
				throw new Exception("Only superusers are allowed to change datasource values");
			}

			$input = $this->GetAndValidateJsonPostData();

			if(!isset($input['value'])){
				throw new InvalidArgumentException('the required field \'value\' is not set');
			}

			$datasource = Datasources::findFirst($datasourceId);

			$saved = false;
			$saved = $datasource->CreateValue($input['value']);

			if($saved){
				$this->response->setJsonContent(['status' => 'ok']);
			
				//Log the evnts
				$dataListEvent = new DatalistEvents();
				$dataListEvent->users_id = $this->auth->GetUserId();
				$dataListEvent->datasource_id = $datasourceId;
				$dataListEvent->event_type = 'create';
				$dataListEvent->newValue = $input['value'];
				if(!$dataListEvent->save()){
					$this->response->setStatusCode('500', 'could not save event');
					$this->response->setJsonContent(implode(', ', $dataListEvent->getMessages()));
					return;
				}
			}
			else{
				throw new Exception('could not save datasource value. '/* . implode($datasource->getMessages(), ', ')*/);
			}
		}
		catch(InvalidArgumentException $e){
			$this->response->setJsonContent(['error' => $e->getMessage()]);
			$this->response->setStatusCode(401, "Invalid argument");
		}
		catch(Exception $e){
			$this->response->setJsonContent(['error' => $e->getMessage()]);
			$this->response->setStatusCode(500, "Server error");
		}
	}

	public function GetDataFromDatasouce($dataSourceId) {
		$query = $this->request->getQuery('q', null, null, true);
		$getAll = $this->request->getQuery('all', null, false, true);

		if(!$getAll && (is_null($dataSourceId) || is_null($query))){
			$this->response->setJsonContent([]);
			return;
		}

		$datasource = Datasources::findFirst(['conditions' => 'id = ' . $dataSourceId]);

		if($getAll){
			$this->response->setJsonContent($datasource->GetAllRows(), JSON_NUMERIC_CHECK);
		}
		else{
			$this->response->setJsonContent($datasource->GetData($query), JSON_NUMERIC_CHECK);
		}
	}

	public function SolrProxy() {
		ConcreteEntries::ProxySolrRequest($this->getDI()->get('solrConfig'));
	}

	public function ReportError() {

		//Check for user credentials (not mandatory)
		$this->RequireAccessControl(false);

		//Get input data
		$jsonData = $this->GetAndValidateJsonPostData();

		// If the task_id is set, informations concerning the context of the entry
		//such as user_id, tasks_id, pages_id, creating user and entry id is also saved
		//An event object is also set
		if(isset($jsonData['add_metadata']) && $jsonData['add_metadata'] == true){

			//Validate input
			$requiredFields = ['task_id', 'post_id', 'comment', 'entity', 'add_metadata'];

			array_walk($requiredFields, function ($el) use ($requiredFields, $jsonData) {
				if (!isset($jsonData[$el])) {
					throw new InvalidArgumentException('the following fields are required: ' . implode($requiredFields, ',') . ' This field is not set: ' . $el);
				}
			});

			$errors = new ErrorReports();
			$event = null;

			$entry = Entries::findFirst(['conditions' => 'tasks_id = :taskId: AND posts_id = :postId:', 'bind' => ['taskId' => $jsonData['task_id'], 'postId' => $jsonData['post_id']]]);

			if(!$entry){
				throw new Exception("Could not find entry with post_id " . $jsonData['post_id']);
			}

			$post = Posts::findFirst(['conditions' => 'id = :postId:', 'bind' => ['postId' => $jsonData['post_id']], 'columns' => ['id', 'pages_id']]);

			if(!$post){
				throw new Exception("Could not find post with id " . $jsonData['post_id']);
			}


			$taskconfigLoader = new TaskConfigurationLoader2();
			$taskConf = $taskconfigLoader->getConfig($jsonData['task_id']);
			$entitiesCollection = new EntitiesCollection($taskConf);

			$entity = $entitiesCollection->getEntityByName(explode('.', $jsonData['entity'])[0]);

			if(!$entity){
				throw new Exception("Could not find entity with name " . explode('.', $jsonData['entity'])[0]);
			}

			$colInfo = $entry->GetContext();
			$errors->users_id = $entry->users_id;
			$errors->entries_id = $entry->id;
			$errors->entry_created_by = $colInfo['user_name'];
			$errors->tasks_id = $jsonData['task_id'];
			$errors->collection_id = $colInfo['collection_id'];
			$errors->pages_id = $post->pages_id;

			//An error report consists of at least these informations
			$errors->reporting_users_id = $this->auth->GetUserId();
			//$errors->collection_id = isset($jsonData['collection_id']) ? $jsonData['collection_id'] : null;
			$errors->posts_id = $jsonData['post_id'];
			$errors->entity_name = $jsonData['entity'];
			$errors->field_name = isset($jsonData['field']) ? $jsonData['field'] : null;
			$errors->comment = $jsonData['comment'];
			$errors->toSuperUser = 0;
			$errors->deleted = 0;

			$errors->beforeSave();
			if (!$errors->create()) {
				throw new Exception('could not save error report: ' . implode($errors->getMessages(), ', '));
			}

			if(!is_null($event)){
				$event->save();
			}

			//Create an event object with the informations
			$event = new Events();
			$event->users_id = $this->auth->GetUserId();
			$event->collections_id = $colInfo['collection_id'];
			$event->units_id = $colInfo['unit_id'];
			$event->pages_id = $colInfo['page_id'];
			$event->posts_id = $jsonData['post_id'];
			$event->event_type = Events::TypeReportError;
		}
		//Special cases (polle or erindringer)
		else{

			//Validate input
			$requiredFields = ['id','comment', 'entity', 'collection_id'];

			array_walk($requiredFields, function ($el) use ($requiredFields, $jsonData) {
				if (!isset($jsonData[$el])) {
					throw new InvalidArgumentException('the following fields are required: ' . implode($requiredFields, ',') . ' This field is not set: ' . $el);
				}
			});

			$error = new SpecialErrors();
			$error->source_id = $jsonData['id'];
			$error->collection_id = $jsonData['collection_id'];
			$error->comment = $jsonData['comment'];
			$error->entity = $jsonData['entity'];
			$error->field = isset($jsonData['field']) ? $jsonData['field'] : null;
			if (!$error->create()) {
				throw new Exception('could not save special error report: ' . implode($error->getMessages(), ', '));
			}
		}

		$this->response->setJsonContent(['message' => 'Fejlrapporten blev gemt']);
	}

	public function UpdateErrorReports() {
		$jsonData = $this->GetAndValidateJsonPostData();

		$rows = isset($jsonData[0]) ? $jsonData : [$jsonData];

		$messages = [];
		foreach ($rows as $row) {
			try {
				$this->UpdateErrorReport($row['id'], $row);
				$messages[] = 'Updated errorreport ' . $row['id'];
			} catch (Exception $e) {
				$messages[] = $e->getMessage();
			}
		}

		$this->response->setJsonContent(['messages' => $messages]);
	}

	public function UpdateErrorReport($errorReportId, $row = null) {

		$this->RequireAccessControl();

		if (is_null($row)) {
			$row = $this->GetAndValidateJsonPostData();
		}

		if (!isset($errorReportId)) {
			throw new InvalidArgumentException('error report id is required');
		}

		$er = ErrorReports::findFirstById($errorReportId);

		if ($er == false) {
			throw new InvalidArgumentException('No error report found for id ' . $errorReportId);
		}

		if ($this->auth->GetUserId() !== $er->users_id && count(SuperUsers::findFirstById($er->users_id)) == 0) {
			throw new InvalidArgumentException('The user cannot change the error report with id ' . $errorReportId);
		}

		$er->toSuperUser = isset($row['toSuperUser']) ? $row['toSuperUser'] : $er->toSuperUser;
		$er->deleted = isset($row['deleted']) ? $row['deleted'] : $er->deleted;
		$er->deleted_reason = isset($row['deleted_reason']) ? $row['deleted_reason'] : $er->deleted_reason;

		if (!$er->save()) {
			throw new Exception($er->getMessages());
		}
	}

	/**
	 * Saves a new Entry, or updates an old one
	 * @param int $entryId The id of the entry to update. If not given, a new Entry is created
	 */
	public function SaveEntry($entryId = null) {

		$this->RequireAccessControl();

		//This is incomming data!
		$jsonData = $this->GetAndValidateJsonPostData();

		if (!isset($jsonData['task_id']) || !isset($jsonData['post_id'])) {
			throw new Exception('task_id and post_id are required');
		}

		$this->db = $this->getDI()->get('db');


		$taskconfigLoader = new TaskConfigurationLoader2();
		$taskConf = $taskconfigLoader->getConfig($jsonData['task_id']);
		$entitiesCollection = new EntitiesCollection($taskConf);


		//If the post already have an entry, get the id of the entry, so the existing entry will be updated, instead of a new one created
		if (!is_null($entryId)) {
			$existingEntry = Entries::findFirst(['conditions' => 'posts_id = :postId:', 'bind' => ['postId' => $jsonData['post_id']]]);
			if ($existingEntry) {
				$entryId = $existingEntry->id;
			}
		}

		try {
			$this->db->begin();

			$concreteEntry = new ConcreteEntries($this->getDI());
			$concreteEntry->startTransaction();

			if (is_null($entryId)) {
				//New entry
				$userId = $this->auth->GetUserId();
				$userName = $this->auth->GetUserName();
				$entry = new Entries();
			} else {
				//Existing entry
				$entry = Entries::findFirstById($entryId);

				$userId = $entry->users_id;
				$userName = Users::findFirstById($entry->users_id)->username;

				if (!$this->AuthorizeUser($entry)) {
					return;
				}

				$oldData = $concreteEntry->LoadEntry($entitiesCollection, $entry->concrete_entries_id, true);
				$newData = $jsonData;

				$concreteEntry->removeAdditionalDataFromNewData($oldData, $newData);

				$concreteEntry->deleteConcreteEntries($oldData, $newData);

				//TODO: Hardcoded!
				$jsonData['persons'] = $newData['persons'];
				//var_dump($jsonData);
			}

			//Saving the concrete entry
			$concreteId = $concreteEntry->SaveEntriesForTask($entitiesCollection, $jsonData);

			//Saving the meta entry, holding information about the concrete entry
			$entry->tasks_id = $jsonData['task_id'];
			$entry->posts_id = $jsonData['post_id'];
			$entry->concrete_entries_id = $concreteId;
			$entry->users_id = $userId;
			$entry->complete = 0;

			if (!$entry->save()) {
				throw new RuntimeException('could not save entry information' . $entry->getMessages()[0]);
			}

			$context = $entry->GetContext();

			$solrData = ConcreteEntries::GetSolrDataFromEntryContext($context, $jsonData['task_id']);
			$solrId = $solrData['collection_id'] . '-' . $entry->concrete_entries_id;
			$conEnData = $concreteEntry->GetSolrData($entitiesCollection, $jsonData);

			$solrJsonObj = array_merge($context, $jsonData['persons'],['id' => $solrId]);
			//TODO: Hardcoded! By some unknown reason, streets field is not added when running contreteEntries->GetSolrData. It may be the combination of a field where solrfieldname and decodedfieldname is not the same, and
			//the entity in which streets belong is included in Solr. It the only field behaving that way, and the only field in which these conditions exist.
			//This query will find it: SELECT * FROM kbharkiv.apacs_fields join apacs_entities on apacs_fields.entities_id = apacs_entities.id where entities_id < 8 and solrfieldname != decodefield and apacs_entities.includeInSOLR = 1
			if(isset($conEnData['streets'])){
				$solrJsonObj['addresses']['street'] = $conEnData['streets'];
			}

			$solrDataToSave = array_merge(
				$solrData,
				$conEnData,
				['user_id' => $userId, 'user_name' => $userName],
				['jsonObj' => json_encode($solrJsonObj)] //TODO: Hardcoded name of main entity
			);

			$solrDataToSave['id'] = $solrId;

			//$solrDataToSave['jsonObj']['id'] = $solrId;

			try{
				$concreteEntry->SaveInSolr($this->getDI()->get('solrConfig'), $solrDataToSave, $solrData['id']);
			}
			catch(Exception $e){
				$exception = new SystemExceptions();
				$exception->save([
					'type' => 'event_save_solr_error',
					'details' => json_encode(['exception' => $e->getMessage(), 'rawPostData' => $this->request->getRawBody()]),
				]);
			}

			$entry->complete = 1;
			$entry->save();

			$event = new Events();
			$event->users_id = $this->auth->GetUserId();
			$event->collections_id = $solrData['collection_id'];
			$event->units_id = $solrData['unit_id'];
			$event->pages_id = $solrData['page_id'];
			$event->posts_id = $jsonData['post_id'];
			$event->tasks_id = $solrData['task_id'];
			$event->event_type = is_null($entryId) ? Events::TypeCreate : Events::TypeEdit;

			$post = Posts::findFirstById($jsonData['post_id']);
			$post->complete = 1;
			//TODO: The entry is not saved (transaction is rolled back later?)
			if (!$post->save()) {
				throw new RuntimeException('could not set post to complete: ' . implode(',', $post->getMessages()) . ' The entry is saved.');
			}
			//TODO: The entry is not saved (transaction is rolled back later?)
			if (!$event->save()) {
				throw new RuntimeException('could not save event data: ' . implode(',', $event->getMessages()) . '. The entry is saved.');
			}			

			$concreteEntry->commitTransaction();
			$this->db->commit();

		} catch (Exception $e) {
			try {
				$this->db->rollback();
				//Not necessary as the line above roll back the given connection
				//$concreteEntry->rollbackTransaction();
			} catch (Exception $ex) {
				//Could not roll back
				$exception = new SystemExceptions();
				$exception->save([
					'type' => 'could_not_roll_back_save_entry',
					'details' => json_encode(['exception' => $ex->getMessage(), 'rawPostData' => $this->request->getRawBody()]),
				]);
			}

			//Logging any exceptions with raw body data
			//file_put_contents('/var/www/kbharkiv.dk/public_html/1508/stable/app/exceptions.log', json_encode(['time' => date('Y-m-d H:i:s'), 'exception' => $e->getMessage()]) . $this->request->getRawBody(), FILE_APPEND);

			//Input error
			if(get_class($e) == 'InvalidArgumentException'){
				$exception = new SystemExceptions();
				$exception->save([
					'type' => 'event_save_invalid_input',
					'details' => json_encode(['exception' => $e->getMessage(), 'rawPostData' => $this->request->getRawBody()]),
				]);
				$this->response->setStatusCode(400, 'Save error');
			}else{
				//Not just input error. This is a real one!
				$exception = new SystemExceptions();
				$exception->save([
					'type' => 'event_save_system',
					'details' => json_encode(['exception' => $e->getMessage(), 'rawPostData' => $this->request->getRawBody()]),
				]);
				$this->response->setStatusCode(500, 'Save error');
			}

			$this->response->setJsonContent(['message' => 'Could not save entry', 'userMessage' => $e->getMessage()]);
			return;
		}

		$this->response->setStatusCode(200, 'OK');
		$this->response->setJsonContent(['post_id' => $jsonData['post_id'], 'concrete_entry_id' => $concreteId, 'entry_id' => $entry->id, 'solr_id' => $solrId]);
	}

	private function AuthorizeUser($entry) {
		if (!$this->auth->UserCanEdit($entry)) {
			$this->response->setStatusCode(401, 'User cannot edit this entry');
			$this->response->setJsonContent($this->auth->getMessage());
			return false;
		}

		return true;
	}

	public function UpdateTasksPages() {

		$this->RequireAccessControl();

		$taskId = $this->request->getQuery('task_id', 'int', null, true);
		$pageId = $this->request->getQuery('page_id', 'int', null, true);

		$jsonData = $this->GetAndValidateJsonPostData();

		if (is_null($taskId) || is_null($pageId)) {
			throw new InvalidArgumentException('task_id and page_id is required');
		}

		$taskPage = TasksPages::findFirst(['conditions' => 'tasks_id = :taskId: AND pages_id = :pageId:', 'bind' => ['taskId' => $taskId, 'pageId' => $pageId]]);

		if (!$taskPage) {
			throw new InvalidArgumentException('No taskpage found with for task_id ' . $taskId . ' and page_id ' . $pageId);
		}

		$taskPage->is_done = $jsonData['is_done'];

		if (!$taskPage->save()) {
			throw new RuntimeException('could not update task page');
		}

		//Updating stats for TasksUnits (pages done)
		$page = Pages::findFirst(['conditions' => 'id = :pageId:', 'bind' => ['pageId' => $taskPage->pages_id]]);

		$taskPagesCount = TasksPages::find(['conditions' => 'is_done = 1 AND units_id = :unitId:', 'bind' => ['unitId' => $taskPage->units_id]]);

		$tasksUnits = TasksUnits::findFirst(['conditions' => 'tasks_id = :taskId: AND units_id = :unitsId:', 'bind' => ['taskId' => $taskPage->tasks_id, 'unitsId' => $page->unit_id]]);
		$tasksUnits->pages_done = count($taskPagesCount);

		if (!$tasksUnits->save()) {
			throw new RuntimeException('could not update tasksunits pages done: ' . implode(', ', $tasksUnits->getMessages()));
		}

		$this->response->setJsonContent($taskPage->toArray(), JSON_NUMERIC_CHECK);
	}
}
