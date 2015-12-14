<?php

class CommonInformationsController extends \Phalcon\Mvc\Controller
{
	private $response;
	private $request;

	public function onConstruct()
	{
		$this->response = $this->getDI()->get('response');
		$this->request = $this->getDI()->get('request');
	}

	private function error($error_message)
	{
		$this->response->setStatusCode(400, 'Wrong parameters');
		$this->response->setJsonContent(['message' => $error_message]);
	}

	public function GetCollections()
	{
		$confLoader = new DBConfigurationLoader();
		$this->response->setJsonContent($confLoader->GetCollections());
	}

	public function GetCollection($collectionId)
	{
		$confLoader = new DBConfigurationLoader();
		$this->response->setJsonContent($confLoader->GetCollection($collectionId));
	}

	public function GetTasks()
	{
		$confLoader = new DBConfigurationLoader();
		$this->response->setJsonContent($confLoader->GetTasks());		
	}

	public function GetTask($taskId)
	{
		$confLoader = new DBConfigurationLoader();
		$this->response->setJsonContent($confLoader->GetTask($taskId));
	}	

	public function GetUnits()
	{
		$request = $this->getDI()->get('request');

		$collectionId = $request->getQuery('collection_id', null, false);
		$taskId = $request->getQuery('task_id', null, null);

		if(!$collectionId)
		{
			$this->error('collection_id is required');
			return;
		}

		$resultSet = Units::find([
			'collection_id' => $collectionId
		]);

		$results = [];
		$i = 0;
		while($resultSet->valid()){
			$results[$i] = array_intersect_key($resultSet->current()->toArray(), array_flip(Units::$publicFields));
			$results[$i]['tasks'] = $resultSet->current()->getTasksUnits(['conditions' => $taskId !== null ? 'task_id = ' . $taskId : null])->toArray();
			$resultSet->next();
			$i++;
		}

		$this->response->setJsonContent($results);
	}

	public function GetUnit($unitId)
	{
		$unit = Units::findFirst([
			'conditions' => 'id = :unitId:',
			'bind' => ['unitId' => $unitId]
		]);

		$result = [];
		$result = $unit->toArray(Units::$publicFields);
		$result['tasks'] = $unit->getTasksUnits()->toArray();

		$this->response->setJsonContent($result);
	}	

	public function GetPages()
	{
		$request = $this->getDI()->get('request');

		$unitId = $request->getQuery('unit_id', 'int', false);
		$pageNumber = $request->getQuery('page_number', 'int', false);

		if(!$unitId)
		{
			$this->error('unit_id is required');
			return;
		}

		$conditions = 'concrete_unit_id = ' . $unitId;

		if($pageNumber !== false)
			$conditions .= ' AND page_number = ' . $pageNumber;

		$resultSet = Pages::find([
		    'conditions' => $conditions
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

	public function GetPage($pageId)
	{
		$page = Pages::findFirst(['id' => $pageId]);
		$taskId = $this->request->getQuery('task_id', null, false);

		$entriesCondition = [];

		if($taskId !== false)
			$entriesCondition = "task_id = " . $taskId;

		$result = [];
		$result = $page->toArray(Pages::$publicFields);
		$result['entries'] = $page->getEntries($entriesCondition)->toArray();

		$this->response->setJsonContent($result);
	}	

	public function GetEntries()
	{
		$pageId = $this->request->getQuery('page_id', 'int', false);
		$taskId = $this->request->getQuery('task_id', 'int', false);

		if($pageId == false)
		{
			$this->error('page_id must be set');
			return;
		}

		$conditions = 'page_id = ' . $pageId;

		if($taskId !== false)
		{
			$conditions .= ' AND task_id = ' . $taskId; 
		}

		$resultSet = Entries::find(['conditions' => $conditions]);

		$this->response->setJsonContent($resultSet->toArray());
	}

	public function GetEntry($entryId)
	{
		$entry = Entries::findFirst(['id' => $entryId]);
		$task = $entry->getTasks();

		$entities = $task->getEntities();

		$result = [];

		//Go through entities, get values and map them to fields
		for($i = 0; $i < count($entities); $i++){
			$entity = $entities [$i];

			//Load entity fields
			$query = $this->modelsManager->createQuery('SELECT f.* FROM Entities AS e LEFT JOIN EntitiesFields as ef ON e.id = ef.entity_id LEFT JOIN Fields as f ON ef.field_id = f.id WHERE e.id = ' . $entity->id);			
			$fields  = $query->execute()->toArray();
			
			//Instantiate loader for concrete entry values
			$ge = new GenericEntry($entity->dbTableName, $fields, $this->getDI());

			//Get entries based on the entity
			$entries = $entity->getEntriesEntities()->toArray();

			$values = [];
			foreach($entries as $entry)
			{	
				//Load data
				$data = $ge->Load($entry['id']);			
				
				//We have the values. Let's map them to the fields
				foreach($fields as $field){
					$row = [];
					$row['fieldname'] = $field['dbFieldName'];
					$row['value'] = $data[$field['dbFieldName']];
					$values[] = $row;
				}				
			}

			//Setting data (entity info and values)
			$result[$i] = ['entity_id' => $entity->id, 'entry_id' => $entryId];
			$result[$i]['values'] = $values;
		}

		$this->response->setJsonContent($result);
	}

	public function SaveEntry()
	{

		$entry = $this->request->getJsonRawBody();

		$postId = $entry->post_id;
		$taskId = $entry->task_id;

		$concreteEntries = [];
		
		$dataIsValid = true;
		foreach($entry['entity_groups'] as $entityGroup){

			$fieldsMetadata = Entries::findFirst($entityGroup['entity_id'])->getFields()->toArray();

			foreach($entityGroup['entities'] as $entity){
				$ge = new GenericEntry($fieldsMetadata, $entity['fields'], $this->getDI());
				if(!$ge->Validate())
				{
					$dataIsValid = false;
				}
				$concreteEntries[] = $ge;
			}  
		}

		//All rigth, we have all entries. Let's validate them!
		$dataIsValid = true;
		foreach($concreteEntries as $conEntry)
		{
			if(!$conEntry->Validate())
			{
				$dataIsValid = false;
				break;
			}
		}

		if(!$dataIsValid)


		$task = Tasks::findFirst(['id' => $taskId]);

		$entities = $task->getEntities();

		$result = [];

		//Go through entities, get values and map them to fields
		for($i = 0; $i < count($entities); $i++){
			$entity = $entities [$i];

			//Load entity fields
			$query = $this->modelsManager->createQuery('SELECT f.* FROM Entities AS e LEFT JOIN EntitiesFields as ef ON e.id = ef.entity_id LEFT JOIN Fields as f ON ef.field_id = f.id WHERE e.id = ' . $entity->id);			
			$fields  = $query->execute()->toArray();
			
			//Instantiate loader for concrete entry values
			$ge = new GenericEntry($entity->dbTableName, $fields, $this->getDI());

			if(!$ge->Save()){
				$this->error($ge->GetErrorMessages());
			}

			//Data is saved! Let's put the data in the aggregated array
			$values = $ge->GetData();

			//Setting data (entity info and values)
			//TODO: Save the id of the generic entry!
			$result[$i] = ['entity_id' => $entity->id, 'entry_id' => 1];
			$result[$i]['values'] = $values;
		}

		$entry = new Entries();
		$entry->data = $result;
		$entry->task_id = $taskId;
		$entry->post_id = $postId;

		$entry->save();

		$this->response->setJsonContent($entry->toArray());		
	}

	public function GetEntry_ORG($entryId)
	{
		$entry = Entries::findFirst(['id' => $entryId]);	

		$data = $entry->toArray();

		//Lets combine entry values and entity fields!
		
		$entryEntities = $entry->getEntriesEntities(['entry_id' => $entryId]);
		$i = 0;
		foreach($entryEntities as $entryEntity)
		{			
			$entity = $entryEntity->getEntities();
			
			$entityData = [];
			$entityData['entity_id'] = $entity->id;
			$entityData['entry_id'] = $entryEntity->entry_id;
			$data['entities'][$i] = $entityData;

			$fields = [];
			foreach($entity->getEntitiesFields() as $entityField)
			{
				$fields[] = $entityField->getFields()->toArray();
			}

			$ge = new GenericEntry($entity->toArray()['dbTableName'], $fields, $this->getDI());
			$entryData = $ge->Load($entryId);

			$rows = [];
			foreach($entryData[0] as $key => $val){
				foreach($fields as $field){
					if($field['dbFieldName'] == $key){
						$newField = [];
						$newField['fieldname'] = $field['dbFieldName'];
						$newField['value'] = $val;
						$newField['id'] = null;
						$newField['unreadable'] = null;
						$rows[] = $newField;
					}
				}
			}

			$data['field'][$i]['values'] = $rows;
			$i++;
		}

		$this->response->setJsonContent($data);
	}

	public function CreateEntry()
	{
		$taskId = $this->request->getPost('task_id', 'int', false);
		$pageId = $this->request->getPost('page_id', 'int', false);

		if($taskId == false || $pageId == false)
		{
			$this->error('task_id and page_id must be set');
			return;
		}
/*
		$fields = $this->config->getCollection(5)[0]['indexes'][0]['entities'][0]['fields'];
		$table = $this->config->getCollection(5)[0]['indexes'][0]['entities'][0]['dbTableName'];
*/
		$configLoader = new DBConfigurationLoader();
		$task = $configLoader->GetTask($taskId);

		$genericEntry = new GenericEntry($table, $fields, $this->getDI());
		
		$action = $this->request->getPost('action', 'string', false);

		//Validation only. Do not save
		if($action == 'validate'){
			$valid = $genericEntry->PartialValidation();
			
			if($valid){
				$this->response->setStatusCode('400');
				$this->response->setJsonContent(['message' => 'all fields are okay']);
			}
			else{
				$this->response->setStatusCode('404');
				$this->response->setJsonContent($genericEntry->GetErrorMessages());
			}

			return;
		}

		//Saving data, return error messages if not possible or input is invalid
		if(!$genericEntry->Save())
		{
			$this->response->setStatusCode(401, 'Could not save entry');
			$this->response->setJsonContent($genericEntry->GetErrorMessages());
			return;
		}
/*
		//Saving the generic entry
		//Needed: collection id, task id, page id	
		$metaInfo = $dataReceiver->GetDataFromFields('POST', ['collection_id', 'task_id', 'page_id']);
		
		$entity = new Entries();
		$entity->collection_id = $metaInfo['collection_id'];
		$entity->task_id = $metaInfo['task_id'];
		$entity->page_id = $metaInfo['page_id'];
		$entity->data = json_encode($genericEntry->GetData(), true);

		if(!$entity->save()){
			$this->response->setStatusCode(500, 'Could not save meta entry');
			return $this->response;
		}*/

		$this->response->setStatusCode(201, 'Data saved');
		$this->response->setJsonContent($genericEntry->GetData());		
	}	

	public function ImportUnits()
	{
		$request = $this->getDI()->get('request');

		$collectionId = $request->getPost('collection_id', null, false);

		if(!$collectionId){
			$this->error('collection_id is required');
			return;
		}

		$type = $request->getPost('type', null, Units::OPERATION_TYPE_CREATE);

		$importer = new Units();
		$colConfig = $this->getDI()->get('configuration')->getCollection($collectionId)[0];

		if($importer->Import($type, $collectionId, $colConfig['units_id_field'], $colConfig['units_info_field'], $colConfig['units_table'], $colConfig['units_info_condition']))
		{
			$this->response->setStatusCode('201', 'Content added');
			$this->response->setJsonContent($importer->GetStatus());
		}
		else{
			$this->response->setStatusCode('500', 'Internal server error');
			$this->response->setJsonContent($importer->GetStatus());
		}
	}

	public function ImportPages()
	{
		$request = $this->getDI()->get('request');

		$collectionId = $request->getPost('collection_id', null, false);

		if(!$collectionId){
			$this->error('collection_id is required');
			return;
		}

		$type = $request->getPost('type', null, Pages::OPERATION_TYPE_CREATE);

		$importer = new Pages();
		$colConfig = $this->getDI()->get('configuration')->getCollection($collectionId)[0];

		if($importer->Import($type, $collectionId, $colConfig['pages_id_field'], $colConfig['pages_unit_id_field'], $colConfig['pages_table'], $colConfig['pages_image_url'], $colConfig['pages_info_condition']))
		{
			$this->response->setStatusCode('201', 'Content added');
			$this->response->setJsonContent($importer->GetStatus());
		}
		else{
			$this->response->setStatusCode('500', 'Internal error');
			$this->response->setJsonContent($importer->GetStatus());
		}
	}	
}