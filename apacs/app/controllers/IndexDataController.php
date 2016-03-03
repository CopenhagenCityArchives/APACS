<?php

class IndexDataController extends \Phalcon\Mvc\Controller {
	private $config;
	private $response;
	private $request;
	private $auth;

	private $dbCon;

	public function onConstruct() {
		$this->config = $this->getDI()->get('configuration');
		$this->response = $this->getDI()->get('response');
		$this->request = $this->getDI()->get('request');
	}

	private function RequireAccessControl($authenticationRequired = true) {
		$this->auth = new AccessController($this->request);
		if (!$this->auth->AuthenticateUser() && $authenticationRequired == true) {
			$this->response->setStatusCode(401, $this->auth->GetMessage());
			$this->response->send();
			die();
		}
	}

	private function GetAndValidateJsonPostData() {
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

		return $jsonData;
	}

	public function GetDataFromDatasouce($dataSourceId) {
		$query = $this->request->getQuery('q', null, null);

		$datasource = Datasources::findFirst(['conditions' => 'id = ' . $dataSourceId]);

		$this->response->setJsonContent($datasource->GetData($query));
	}

	public function SolrProxy() {
		ConcreteEntries::ProxySolrRequest();
	}

	public function ReportError() {

		$this->RequireAccessControl(false);

		$jsonData = $this->GetAndValidateJsonPostData();

		$reportingUserId = $this->auth->GetUserId();
		$requiredFields = ['post_id', 'entity_name', 'field_name', 'concrete_entries_id', 'comment', 'value'];

		array_walk($requiredFields, function ($el) use ($requiredFields, $jsonData) {
			if (!isset($jsonData[$el])) {
				throw new InvalidArgumentException('the following fields are required: ' . implode($requiredFields, ',') . ' This field is not set: ' . $el);
			}
		});

		$concreteEntry = new ConcreteEntries($this->getDI());

		$entity = Entities::findFirst(['conditions' => 'name = "' . $jsonData['entity_name'] . '"']);

		if (!$entity) {
			throw new InvalidArgumentException('no entity found with name ' . $jsonData['entity_name']);
		}

		$entry = Entries::findFirst(['conditions' => 'tasks_id = :taskId: AND posts_id = :postId:', 'bind' => ['taskId' => $entity->task_id, 'postId' => $jsonData['post_id']]]);

		$post = Posts::findFirst(['conditions' => 'id = :postId:', 'bind' => ['postId' => $jsonData['post_id']]]);

		if (!$entry) {
			throw new InvalidArgumentException('no entry found for task id ' . $entity->task_id . ' and post id ' . $jsonData['post_id']);
		}

		//Check if the entity and field of the concrete id is already reported as an error
		$existingReports = ErrorReports::find(['conditions' => 'entity_name = :entity: AND field_name = :field: AND concrete_entries_id = :concreteId:',
			'bind' => ['entity' => $jsonData['entity_name'], 'field' => $jsonData['field_name'], 'concreteId' => $jsonData['concrete_entries_id']]]);

		if (count($existingReports) > 0) {
			throw new InvalidArgumentException('Error report already exists on the given entity, field and concrete id');
		}

		$errors = new ErrorReports();
		$errors->reporting_users_id = $reportingUserId;
		$errors->users_id = $entry->users_id;
		$errors->tasks_id = $entity->task_id;
		$errors->pages_id = $post->pages_id;
		$errors->posts_id = $jsonData['post_id'];
		$errors->entity_name = $jsonData['entity_name'];
		$errors->field_name = $jsonData['field_name'];
		$errors->comment = $jsonData['comment'];
		$errors->concrete_entries_id = $jsonData['concrete_entries_id'];
		$errors->original_value = $jsonData['value'];

		if (!$errors->save($jsonData)) {
			throw new Exception('could not save error report: ' . implode($errors->getMessages(), ', '));
		}

		$colInfo = $entry->GetContext();

		$event = new Events();
		$event->users_id = $this->auth->GetUserId();
		$event->collections_id = $colInfo['collection_id'];
		$event->units_id = $colInfo['unit_id'];
		$event->pages_id = $colInfo['page_id'];
		$event->posts_id = $colInfo['post_id'];
		$event->event_type = Events::TypeReportError;
		$event->save();

		$this->response->setJsonContent(['message' => 'error report saved']);
	}

	public function UpdateErrorReport($errorReportId) {

		$this->RequireAccessControl();

		$jsonData = $this->GetAndValidateJsonPostData();

		if (!isset($jsonData['to_super_user'])) {
			throw new InvalidArgumentException('to_super_user is required');
		}

		$errorReport = ErrorReports::findFirstById($errorReportId);

		if ($errorReport == false) {
			throw new InvalidArgumentException('No error report found for id ' . $errorReportId);
		}

		if ($this->auth->GetUserId() !== $errorReport->users_id) {
			throw new InvalidArgumentException('The user cannot change the error report with id ' . $errorReportId);
		}

		$errorReport->toSuperUser = $jsonData['to_super_user'];

		$errorReport->save();

		$this->response->setJsonContent(['message' => 'error report updated']);
	}

	public function SaveEntry() {

		$this->RequireAccessControl();

		//This is incomming data!
		$jsonData = $this->GetAndValidateJsonPostData();

		$userId = $this->auth->GetUserId();

		$entities = Entities::find(['conditions' => 'task_id = ' . $jsonData['task_id']]);

		if (count($entities) == 0) {
			$this->response->setStatusCode(401, 'Input error');
			$this->response->setJsonContent(['No entities found for task ' . $jsonData['task_id']]);
			return;
		}

		//Mission Impossible: We can't check for the post when we don't have an id
		/*$existingPosts = Posts::find(['conditions' => 'task_id = :taskId: AND posts_id = :postId:', 'bind' => ['taskId' => $jsonData['task_id'], 'postId' => $jsonData['post_id']]]);

		if ($existingPosts) {
			$this->response->setStatusCode(401, 'Entry already exists');
			$this->response->setJsonContent(['message' => 'An entry exists for post id ' . $jsonData['post_id'] . ' and task_id ' . $jsonData['task_id']]);
		}*/

		try {
			//Saving the post
			$post = new Posts();
			$jsonData['post']['complete'] = 1;
			$jsonData['post']['pages_id'] = $jsonData['page_id'];
			if (!$post->save($jsonData['post'])) {
				throw new InvalidArgumentException('Could not save post.');
			}
			$post = Posts::findFirst($post->id);
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

			if (!$entry->save()) {
				throw new RuntimeException('could not save entry information' . $entry->getMessages()[0]);
			}

			$solrData = ConcreteEntries::GetSolrDataFromEntryContext($entry->GetContext());

			$concreteEntry->SaveInSolr(array_merge(
				$solrData, $concreteEntry->GetSolrData($entities, $jsonData)
			));

			$entry->complete = 1;
			$entry->save();

			$taskUnit = TasksUnits::findFirst(['conditions' => 'tasks_id = ' . $jsonData['task_id'] . ' AND units_id = ' . $entry->GetContext()['unit_id']]);

			$maxPosts = $taskUnit->columns * $taskUnit->rows;

			if (count(Posts::find(['conditions' => 'pages_id = ' . $jsonData['page_id']])) == $maxPosts) {
				$taskPage = TasksPages::findFirst(['conditions' => 'tasks_id = ' . $jsonData['task_id'] . ' AND pages_id = ' . $jsonData['page_id']]);
				$taskPage->is_done = 1;
				$taskPage->save();

				$taskUnit->pages_done = $taskUnit->pages_done + 1;
				$taskUnit->save();
			}

			$event = new Events();
			$event->users_id = $this->auth->GetUserId();
			$event->collections_id = $solrData['collection_id'];
			$event->units_id = $solrData['unit_id'];
			$event->pages_id = $solrData['page_id'];
			$event->posts_id = $post->id;
			$event->tasks_id = $solrData['task_id'];
			$event->event_type = Events::TypeCreate;

			if (!$event->save()) {
				throw new RuntimeException('could not save event data: ' . implode(',', $event->getMessages()));
			}

		} catch (Exception $e) {
			$this->response->setStatusCode(401, 'Save error');
			$this->response->setJsonContent(['message' => 'Could not save entry ' . $e->getMessage()]);
			return;
		}

		$this->response->setStatusCode(200, 'OK');
		$this->response->setJsonContent(['post_id' => $post->id, 'concrete_entry_id' => $concreteId, 'pages_done' => $taskUnit->pages_done]);
	}

	/**
	 * Updates part of an entry. Note that this method only supports updating one entry at a time
	 *
	 */
	public function UpdateEntry($entryId) {

		$this->RequireAccessControl();

		$jsonData = $this->GetAndValidateJsonPostData();

		$concreteId = $jsonData['concrete_entries_id'];
		$entityName = $jsonData['entity_name'];
		$fieldName = $jsonData['field_name'];
		$value = $jsonData['value'];

		$entity = Entities::findFirst(['conditions' => 'name = :entity_name: AND task_id = :task_id:', 'bind' => ['entity_name' => $jsonData['entity_name'], 'task_id' => $jsonData['task_id']]]);

		if ($entity == false) {
			throw new InvalidArgumentException('Not entity found with name ' . $jsonData['entity_name'] . ' for task id ' . $jsonData['task_id']);
		}

		$entry = Entries::findFirstById($entryId);

		if ($entry == false) {
			throw new InvalidArgumentException('No entry found with id ' . $entryId);
		}

		if (!$this->auth->UserCanEdit($entry->users_id, $entity->task_id)) {
			$this->response->setStatusCode(401, 'User cannot edit this entry');
			return;
		}

		$conEntry = new ConcreteEntries($this->getDI());
		$entryData = $conEntry->Load($entity, 'id', $concreteId);

		if (is_null($entryData)) {
			throw new InvalidArgumentException('no entry data found for ' . $jsonData['entity_name'] . ' with id ' . $concreteId);
		}

		if ($entryData[$entity->entityKeyName] !== $entry->concrete_entries_id) {
			throw new InvalidArgumentException('The entry with id ' . $entry->id . ' does not match a concrete entity of type ' . $entityName . ' with id ' . $concreteId);
		}

		$entryData[$fieldName] = $value;

		$concreteId = $conEntry->Save($entity, $entryData);

		if (!is_numeric($concreteId)) {
			throw new RuntimeException('could not update entry wth id ' . $concreteId);
		}

		$solrData = ConcreteEntries::GetSolrDataFromEntryContext($entry->GetContext());
		$entities = Entities::find(['conditions' => 'task_id = ' . $entry->tasks_id]);

		$completeEntry = $conEntry->LoadEntry($entities, $entry->concrete_entries_id, true);

		$conEntry->SaveInSolr(array_merge(
			$solrData, $conEntry->GetSolrData($entities, $completeEntry)
		), $entryId);

		$event = new Events();
		$event->users_id = $this->auth->GetUserId();
		$event->collections_id = $solrData['collection_id'];
		$event->units_id = $solrData['unit_id'];
		$event->pages_id = $solrData['page_id'];
		$event->posts_id = $solrData['post_id'];
		$event->event_type = Events::TypeEdit;
		$event->save();

		$this->response->setJsonContent(['message' => 'entry updated']);
	}

	public function UpdateTasksPages($id) {

		$this->RequireAccessControl();

		$jsonData = $this->GetAndValidateJsonPostData();

		$taskPage = TasksPages::findFirstById($id);

		if (!$taskPage) {
			throw new InvalidArgumentException('No taskpage found with id ' . $id);
		}

		$taskPage->is_done = $jsonData['is_done'];

		if (!$taskPage->save()) {
			throw new RuntimeException('could not update task page');
		}

		//Updating stats for TasksUnits (pages done)
		$page = Pages::findFirst(['conditions' => 'id = :pageId:', 'bind' => ['pageId' => $taskPage->pages_id]]);
		$tasksUnits = TasksUnits::findFirst(['conditions' => 'tasks_id = :taskId: AND units_id = :unitsId:', 'bind' => ['taskId' => $taskPage->tasks_id, 'unitsId' => $page->unit_id]]);
		$tasksUnits->pages_done = $tasksUnits->pages_done + 1;
		$tasksUnits->save();

		$this->response->setJsonContent($taskPage->toArray());
	}
}