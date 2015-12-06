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

	public function GetPage($pageId)
	{
		$page = Pages::findFirst(['id' => $pageId]);

		$result = [];
		$result = $page->toArray(Pages::$publicFields);
		$result['entries'] = $page->getEntries()->toArray();

		$this->response->setJsonContent($result);
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

	public function GetPages()
	{
		$request = $this->getDI()->get('request');

		//$collectionId = $request->getQuery('collection_id', null, false);
		$unitId = $request->getQuery('unit_id', null, false);

		if(!$unitId)
		{
			$this->error('unit_id is required');
			return;
		}

		$resultSet = Pages::find([
		    'conditions' => 'concrete_unit_id = :unitId:',
		    'bind' => ['unitId' => $unitId]
    	]);
    	
		$results = [];
		$i = 0;
		while($resultSet->valid()){
			$results[$i] = array_intersect_key($resultSet->current()->toArray(), array_flip(Pages::$publicFields));
			$results[$i]['entries'] = $resultSet->current()->getEntries()->toArray();
			$resultSet->next();
			$i++;
		}

		$this->response->setJsonContent($results);
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

		$importer = new UnitsModel();
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

		$importer = new PagesModel();
		$colConfig = $this->getDI()->get('configuration')->getCollection($collectionId)[0];

		if($importer->Import($type, $collectionId, $colConfig['pages_id_field'], $colConfig['pages_unit_id_field'], $colConfig['pages_table'], $colConfig['pages_info_condition']))
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