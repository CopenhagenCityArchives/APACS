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

	public function SolrProxy() {
		$this->response->setContentType('application/json', 'UTF-8');
		ConcreteEntries::ProxySolrRequest();
	}

	public function ReportError() {
		$jsonData = json_decode($this->request->getRawBody(), true);
		//TODO: Implement user auth
		$reportingUserId = 1;
		$requiredFields = ['task_id', 'post_id', 'entity_name', 'field_name', 'concrete_entries_id', 'comment', 'value'];

		array_walk($requiredFields, function ($el) use ($requiredFields, $jsonData) {
			if (!isset($jsonData[$el])) {
				throw new InvalidArgumentException('the following fields are required: ' . implode($requiredFields, ',') . ' This field is not set: ' . $el);
			}
		});

		$concreteEntry = new ConcreteEntries($this->getDI());
		//$entry = $concreteEntry->Load(Entities::findFirst(['conditions' => ['name' => $jsonData['entity_name'], 'tasks_id' => $jsonData['task_id']]]), 'id', $jsonData['concrete_entry_id']);

		$entry = Entries::findFirst(['conditions' => 'tasks_id = :taskId: AND posts_id = :postId:', 'bind' => ['taskId' => $jsonData['task_id'], 'postId' => $jsonData['post_id']]]);

		if (!$entry) {
			throw new InvalidArgumentException('no entry found for task id ' . $jsonData['task_id'] . ' and post id ' . $jsonData['post_id']);
		}

		$errors = new ErrorReports();
		$errors->reporting_users_id = $reportingUserId;
		$errors->users_id = 1; //$entry->users_id;
		$errors->tasks_id = $jsonData['task_id'];
		$errors->posts_id = $jsonData['post_id'];
		$errors->entity_name = $jsonData['entity_name'];
		$errors->field_name = $jsonData['field_name'];
		$errors->comment = $jsonData['field_name'];
		$errors->concrete_entries_id = $jsonData['concrete_entries_id'];
		$errors->original_value = $jsonData['value'];

		if (!$errors->Save($jsonData)) {
			throw new Exception('could not save error report: ' . implode($errors->getMessages(), ', '));
		}
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
		$userId = 1;

		$entities = Entities::find(['conditions' => 'task_id = ' . $jsonData['task_id']]);
		/*$entities = [];
			foreach ($entitiesResult as $result) {
				$entities[] = $result;
		*/
		if (count($entities) == 0) {
			$this->response->setStatusCode(401, 'Input error');
			$this->response->setJsonContent(['No entities found for task ' . $jsonData['task_id']]);
			return;
		}

		try {
			//Saving the post
			$post = new Posts();
			$jsonData['post']['complete'] = 1;
			$jsonData['post']['pages_id'] = $jsonData['page_id'];
			if (!$post->Save($jsonData['post'])) {
				throw new InvalidArgumentException('Could not save post.');
			}
			//$post->SaveThumbImage();

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
			$entry->id = 0;

			if (!$entry->save()) {
				throw new RuntimeException('could not save entry information' . $entry->getMessages()[0]);
			}

			$solrData = [];
			$solrData['id'] = $concreteId;

			$solrData['task_id'] = $jsonData['task_id'];
			$solrData['post_id'] = $post->id;
			$solrData['entry_id'] = $entry->toArray()['id'];

			//Setting collection info
			$colInfo = $post->GetCollectionInfo();
			$solrData['collection_info'] = $colInfo['collection_name'] . ' ' . $colInfo['unit_description'];
			$solrData['unit_id'] = $colInfo['unit_id'];
			$solrData['page_id'] = $colInfo['page_id'];
			$solrData['collection_id'] = $colInfo['collection_id'];

			$concreteEntry->SaveInSolr(array_merge(
				$solrData, $concreteEntry->GetSolrData($entities, $jsonData)
			));
			$entry->complete = 1;
			$entry->save();

		} catch (Exception $e) {
			$this->response->setStatusCode(401, 'Save error');
			$this->response->setJsonContent(['message' => 'Could not save entry ' . $e->getMessage()]);
			return;
		}

		$this->response->setStatusCode(200, 'OK');
		$this->response->setJsonContent(['message' => 'all data saved. task_id:  ' . $jsonData['task_id'] . ', post_id: ' . $post->id]);
	}

	/**
	 * Updates part of an entry. Note that this method only supports updating one entry at a time
	 *
	 */
	public function UpdateEntry($entryId) {
		$jsonData = json_decode($this->request->getRawBody(), true);

		$concreteId = $jsonData['concrete_id'];
		$entityName = $jsonData['entity_name'];
		$fieldName = $jsonData['field_name'];
		$value = $jsonData['value'];

		$conEntry = new ConcreteEntries();
		$entityData = $conEntry->Load($entity, 'id', $concreteId);
		$entityData[$fieldName] = $value;

		if ($conEntry->Save($entity, $entityData) !== true) {
			throw new RuntimeException('could not update entry wth id: ' . $concreteId);
		}

		$this->setJsonContent(['message' => 'entry updated']);
	}

	public function GetPostEntries($id) {
		$response = [];

		$post = Posts::findFirstById($id);

		$entries = $post->getEntries();

		$postData = [];
		foreach ($entries as $entry) {
			//Loading entities for entry
			$entities = Entities::find(['conditions' => 'task_id = ' . $entry->tasks_id]);

			//Loading concrete entry
			$concreteEntry = new ConcreteEntries($this->getDI());
			$entryData = $concreteEntry->LoadEntry($entities, $entry->concrete_entries_id);
			$postData = array_merge($postData, $concreteEntry->ConcatEntitiesAndData($entities, $entryData, $entry->concrete_entries_id));
		}

		$response['metadata'] = $post->GetCollectionInfo();
		$response['data'] = $postData;
		$errorReports = ErrorReports::find(['conditions' => 'posts_id = ' . $id . ' AND tasks_id = ' . $entries[0]->tasks_id])->toArray();
		$response['error_reports'] = $errorReports;

		$this->response->setJsonContent($response);
	}

	public function GetEntry($id) {
		$entry = Entries::findFirstById($id);

		if ($entry === false) {
			throw new InvalidArgumentException('entry with id ' . $id . ' not found');
		}

		$entities = Entities::find(['conditions' => 'task_id = ' . $entry->tasks_id]);

		$concreteEntry = new ConcreteEntries($this->getDI());
		$entryData = $concreteEntry->LoadEntry($entities, $entry->concrete_entries_id);

		$this->response->setJsonContent($entryData);
	}
}