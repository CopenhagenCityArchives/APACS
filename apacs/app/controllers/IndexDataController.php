<?php

class IndexDataController extends \Phalcon\Mvc\Controller {
	private $config;
	private $response;
	private $request;
	private $auth;

	private $db;

	public function onConstruct() {
		$this->config = $this->getDI()->get('configuration');
		$this->response = $this->getDI()->get('response');
		$this->request = $this->getDI()->get('request');
	}

	private function RequireAccessControl($authenticationRequired = true) {
		$this->auth = $this->getDI()->get('AccessController');
		if (!$this->auth->AuthenticateUser() && $authenticationRequired == true) {
			$this->response->setStatusCode(401, $this->auth->GetMessage());
			$this->response->send();
			die();
		}
	}

	private function GetAndValidateJsonPostData() {
		$jsonData = json_decode($this->request->getRawBody(), true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->response->setStatusCode(400, 'Input error');
			$this->response->setJsonContent(['Invalid JSON format']);
			return;
		}

		if (count($jsonData) == 0) {
			$this->response->setStatusCode(400, 'Input error');
			$this->response->setJsonContent(['No data given']);
			return;
		}

		return $jsonData;
	}

	public function GetDataFromDatasouce($dataSourceId) {
		$query = $this->request->getQuery('q', null, null);

		$datasource = Datasources::findFirst(['conditions' => 'id = ' . $dataSourceId]);

		$this->response->setJsonContent($datasource->GetData($query), JSON_NUMERIC_CHECK);
	}

	public function SolrProxy() {
		ConcreteEntries::ProxySolrRequest();
	}

	public function ReportError() {

		$this->RequireAccessControl(false);

		$jsonData = $this->GetAndValidateJsonPostData();

		$reportingUserId = $this->auth->GetUserId();
		$requiredFields = ['post_id', 'entity_name', 'field_name', 'concrete_entries_id', 'value'];

		array_walk($requiredFields, function ($el) use ($requiredFields, $jsonData) {
			if (!isset($jsonData[$el])) {
				throw new InvalidArgumentException('the following fields are required: ' . implode($requiredFields, ',') . ' This field is not set: ' . $el);
			}
		});

		$entity = Entities::findFirst(['conditions' => 'name = "' . $jsonData['entity_name'] . '"']);

		if (!$entity) {
			throw new InvalidArgumentException('no entity found with name ' . $jsonData['entity_name']);
		}

		$entry = Entries::findFirst(['conditions' => 'tasks_id = :taskId: AND posts_id = :postId:', 'bind' => ['taskId' => $entity->task_id, 'postId' => $jsonData['post_id']]]);

		$post = Posts::findFirst(['conditions' => 'id = :postId:', 'bind' => ['postId' => $jsonData['post_id']], 'columns' => ['id', 'pages_id']]);

		if (!$entry) {
			throw new InvalidArgumentException('no entry found for task id ' . $entity->task_id . ' and post id ' . $jsonData['post_id']);
		}

		//Check if the entity and field of the concrete id is already reported as an error
		$existingReports = ErrorReports::find(['conditions' => 'entity_name = :entity: AND field_name = :field: AND concrete_entries_id = :concreteId:',
			'bind' => ['entity' => $jsonData['entity_name'], 'field' => $jsonData['field_name'], 'concreteId' => $jsonData['concrete_entries_id']]]);

		if (count($existingReports) > 0) {
			throw new InvalidArgumentException('Error report already exists on the given entity, field and concrete id');
		}

		$colInfo = $entry->GetContext();

		$errors = new ErrorReports();
		$errors->reporting_users_id = $reportingUserId;
		$errors->users_id = $entry->users_id;
		$errors->tasks_id = $entity->task_id;
		$errors->entities_id = $entity->id;
		$errors->pages_id = $post->pages_id;
		$errors->posts_id = $jsonData['post_id'];
		$errors->entity_name = $jsonData['entity_name'];
		$errors->field_name = $jsonData['field_name'];
		$errors->field_id = Fields::findFirst(['conditions' => 'fieldName = :fieldName: AND entities_id = :entities_id:', 'bind' => ['fieldName' => $jsonData['fieldName'], 'entities_id' => $entity->id]])->id;
		$errors->entity_position = $entity->GetEntityPosition(Entities::find(['condition' => 'tasks_id = :taskId:', 'bind' => ['taskId' => $entity->task_id]]), $entity);
		$errors->comment = $jsonData['comment'];
		$errors->concrete_entries_id = $jsonData['concrete_entries_id'];
		$errors->original_value = $jsonData['value'];
		$errors->toSuperUser = 0;
		$errors->entry_created_by = $colInfo['username'];
		$errors->entries_id = $entry->id;
		$errors->deleted = 0;
		$errors->beforeSave();
		if (!$errors->save($jsonData)) {
			throw new Exception('could not save error report: ' . implode($errors->getMessages(), ', '));
		}

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

		$er->toSuperUser = isset($row['toSuperUser']) ? $row['toSuperUser'] : $er->to_super_user;
		$er->deleted = isset($row['deleted']) ? $row['deleted'] : $er->deleted;
		$er->deleted_reason = isset($row['deleted_reason']) ? $row['deleted_reason'] : $er->deleted_reason;

		if (!$er->save()) {
			throw new Exception($er->getMessages());
		}
	}

	public function SaveEntry($entryId = null) {

		$this->RequireAccessControl();

		//This is incomming data!
		$jsonData = $this->GetAndValidateJsonPostData();

		$userId = $this->auth->GetUserId();
		$userName = $this->auth->GetUserName();

		if (!isset($jsonData['task_id']) || !isset($jsonData['page_id'])) {
			throw new Exception('task_id and page_id are required');
		}

		$entities = Entities::find(['conditions' => 'task_id = ' . $jsonData['task_id']]);

		//Check if the task has any entities (...?)
		if (count($entities) == 0) {
			$this->response->setStatusCode(403, 'Input error');
			$this->response->setJsonContent(['No entities found for task ' . $jsonData['task_id']]);
			return;
		}

		$this->db = $this->getDI()->get('db');

		try {
			$this->db->begin();

			$concreteEntry = new ConcreteEntries($this->getDI());
			$concreteEntry->startTransaction();

			if (is_null($entryId)) {
				//Check if there are existing posts for the page that are placed in the same spot
				$existingPosts = Posts::find(['conditions' => 'pages_id = :pagesId: AND ROUND(x,5) = ROUND(:x:,5) AND ROUND(y,5) = ROUND(:y:,5)', 'bind' => [
					'pagesId' => $jsonData['page_id'],
					'y' => $jsonData['post']['y'],
					'x' => $jsonData['post']['x'],
				]]);

				if (count($existingPosts) > 0) {
					$this->response->setStatusCode(403, 'Entry already exists');
					$this->response->setJsonContent(['message' => 'Posten eksisterer allerede.']);
					return;
				}

				$entry = new Entries();
				$post = new Posts();
			} else {
				$entry = Entries::findFirstById($entryId);
				$post = Posts::findFirstById($entry->posts_id);
				$jsonData['post']['id'] = $post->id;

				if (!$this->AuthorizeUser($entry)) {
					return;
				}

				//if (!$this->AuthorizeUser($entry->GetContext(), $errorReports)) {
				//					return;
				//	}

				//	$oldData = $concreteEntry->LoadEntry($entities, $entry->concrete_entries_id, true)['persons'];

				//	$oldData = $concreteEntry->convertDataFromHierarchy($entities, $concreteEntry->LoadEntry($entities, $entry->concrete_entries_id, true));
				//		$newData = $concreteEntry->convertDataFromHierarchy($entities, $jsonData);

				$oldData = $concreteEntry->LoadEntry($entities, $entry->concrete_entries_id, true);
				$newData = $jsonData;

				$concreteEntry->deleteConcreteEntries($oldData, $newData);

				//	var_dump(ArrayComparer::getDifference($oldData, $newData));
				//$concreteEntry->deleteConcreteEntries($entities, ArrayComparer::getDifference($oldData, $newData));
				//var_dump($oldData, $newData);
				//var_dump('new data', $jsonData['persons']);
				//var_dump($oldData, $newData);
				//var_dump(ArrayComparer::getDifference($oldData['persons'], $newData['persons']));

				/*	if (!$this->auth->UserCanEdit()) {
					$this->response->setStatusCode(401, 'User cannot edit this entry');
					$this->response->setJsonContent([$this->auth->GetMessage()]);
					return;
				}*/

				//$oldEntriesData = $concreteEntry->convertDataFromHierarchy($entities, $jsonData);

				/*	foreach ($oldEntriesData as $entityRow => $value) {
						$errorReports[] = ErrorReports::find(['conditions' => []]);
					}
				*/

				//Delete existing data for the entry
				//		$concreteEntry->Delete($entities, $entry->concrete_entries_id);
			}

			//Saving the post
			$jsonData['post']['complete'] = 1;
			$jsonData['post']['pages_id'] = $jsonData['page_id'];
			if (!$post->save($jsonData['post'])) {
				throw new InvalidArgumentException('Could not save post.');
			}
			$post = Posts::findFirstById($post->id);
			$post->SaveThumbImage();

			//Saving the concrete entry
			$concreteId = $concreteEntry->SaveEntriesForTask($entities, $jsonData);

			//Saving the meta entry, holding information about the concrete entry
			$entry->tasks_id = $jsonData['task_id'];
			$entry->posts_id = $post->id;
			$entry->concrete_entries_id = $concreteId;
			$entry->users_id = $userId;
			$entry->complete = 0;

			if (!$entry->save()) {
				throw new RuntimeException('could not save entry information' . $entry->getMessages()[0]);
			}

			$solrData = ConcreteEntries::GetSolrDataFromEntryContext($entry->GetContext());

			$solrDataToSave = array_merge(
				$solrData,
				$concreteEntry->GetSolrData($entities, $jsonData),
				['user_id' => $userId, 'user_name' => $userName]
			);

			$concreteEntry->SaveInSolr($solrDataToSave);

			$entry->complete = 1;
			$entry->save();

			$event = new Events();
			$event->users_id = $this->auth->GetUserId();
			$event->collections_id = $solrData['collection_id'];
			$event->units_id = $solrData['unit_id'];
			$event->pages_id = $solrData['page_id'];
			$event->posts_id = $post->id;
			$event->tasks_id = $solrData['task_id'];
			$event->event_type = is_null($entryId) ? Events::TypeCreate : Events::TypeEdit;

			if (!$event->save()) {
				throw new RuntimeException('could not save event data: ' . implode(',', $event->getMessages()));
			}

			$concreteEntry->commitTransaction();
			$this->db->commit();

		} catch (Exception $e) {
			try {
				$this->db->rollback();
				$concreteEntry->rollbackTransaction();
			} catch (Exception $ex) {
				//Could not roll back
			}

			//Logging any exceptions with raw body data
			file_put_contents('/var/www/kbharkiv.dk/public_html/1508/stable/app/exceptions.log', $this->request->getRawBody(), FILE_APPEND);

			$this->response->setStatusCode(403, 'Save error');
			$this->response->setJsonContent(['message' => 'Could not save entry', 'userMessage' => $e->getMessage()]);
			return;
		}

		$this->response->setStatusCode(200, 'OK');
		$this->response->setJsonContent(['post_id' => $post->id, 'concrete_entry_id' => $concreteId]);
	}

	private function AuthorizeUser($entry) {
		if (!$this->auth->UserCanEdit($entry)) {
			$this->response->setStatusCode(401, 'User cannot edit this entry');
			$this->response->setJsonContent($this->auth->getMessage());
			return false;
		}

		return true;
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

		$entryContext = $entry->GetContext();

		$errorReports = ErrorReports::FindByRawSql('apacs_errorreports.field_name = \'' . $fieldName . '\' AND concrete_entries_id = \'' . $concreteId . '\'');

		if (!$this->AuthorizeUser($entry)) {
			return;
		}

		$conEntry = new ConcreteEntries($this->getDI());

		if ($entity->type == 'array') {
			$entryData = $conEntry->Load($entity, 'id', $concreteId)[0];
		} else {
			$entryData = $conEntry->Load($entity, 'id', $concreteId);
		}

		if (is_null($entryData)) {
			throw new InvalidArgumentException('no entry data found for ' . $jsonData['entity_name'] . ' with id ' . $concreteId);
		}

		if ($entryData[$entity->entityKeyName] !== $entry->concrete_entries_id) {
			throw new InvalidArgumentException('The entry with id ' . $entry->id . ' does not match a concrete entity of type ' . $entityName . ' with id ' . $concreteId);
		}

		if (trim($value) == "") {
			$value = NULL;
		}

		$entryData[$fieldName] = $value;

		$concreteId = $conEntry->Save($entity, $entryData);

		if (!is_numeric($concreteId)) {
			throw new RuntimeException('could not update entry wth id ' . $concreteId);
		}

		$solrData = ConcreteEntries::GetSolrDataFromEntryContext($entryContext);
		$entities = Entities::find(['conditions' => 'task_id = ' . $entry->tasks_id]);

		$completeEntry = $conEntry->LoadEntry($entities, $entry->concrete_entries_id, true);

		$conEntry->SaveInSolr(array_merge(
			$solrData, $conEntry->GetSolrData($entities, $completeEntry) /*, ['user_id' => $this->auth->GetUserId(), 'user_name' => $this->auth->GetUserName()]*/
		), $concreteId);

		//Remove any error reports for the field
		foreach ($errorReports as $error) {
			/*if ($error->delete() === false) {
				echo 'Notice: Could not delete error: ' . $error->getMessages();
			}*/
		}

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

	public function UpdateTasksPages() {

		$this->RequireAccessControl();

		$taskId = $this->request->getQuery('task_id', null, null);
		$pageId = $this->request->getQuery('page_id', null, null);

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
		$tasksUnits = TasksUnits::findFirst(['conditions' => 'tasks_id = :taskId: AND units_id = :unitsId:', 'bind' => ['taskId' => $taskPage->tasks_id, 'unitsId' => $page->unit_id]]);
		$tasksUnits->pages_done = $tasksUnits->pages_done + 1;

		if (!$tasksUnits->save()) {
			throw new RuntimeException('could not udpate tasksunits pages done: ' . implode(', ', $tasksUnits->getMessages()));
		}

		$this->response->setJsonContent($taskPage->toArray(), JSON_NUMERIC_CHECK);
	}
}