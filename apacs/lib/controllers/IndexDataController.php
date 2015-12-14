<?php

class IndexDataController extends \Phalcon\Mvc\Controller
{
	private $config;
	private $response;
	private $request;

	public function onConstruct()
	{
		$this->config = $this->getDI()->get('configuration');
		$this->response = $this->getDI()->get('response');
		$this->request = $this->getDI()->get('request');
	}

	public function CreateEntry()
	{
		$taskId = $this->request->getPost('task_id', 'int', false);
		$pageId = $this->request->getPost('page_id', 'int', false);

		if($taskId == false || $pageId == false)
		{
			$this->response->setStatusCode(401, 'Wrong request');
			$this->response->setJsonContent(['error' => 'task_id and page_id must be set']);
			return;
		}

		$fields = $this->config->getCollection(5)[0]['indexes'][0]['entities'][0]['fields'];
		$table = $this->config->getCollection(5)[0]['indexes'][0]['entities'][0]['dbTableName'];

		$genericEntry = new GenericEntry($table, $fields, $this->getDI());
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

	public function Create()
	{
		$dataReceiver = new DataReceiver(new Phalcon\Http\Request());
		$data = $dataReceiver->GetDataFromFields('POST', $this->config->getIndexEntity($entityId)['fields']);
		
		//Needed: collection id, task id, page id	
		$metaInfo = $dataReceiver->GetDataFromFields('POST', ['collection_id', 'task_id', 'page_id']);
		
		$entity = new Entity();
		$entity->collection_id = $metaInfo['collection_id'];
		$entity->task_id = $metaInfo['task_id'];
		$entity->page_id = $metaInfo['page_id'];
		$entity->data = json_encode($data, true);

		if(!$entity->save()){
			$this->response->setStatusCode('500', 'Could not save entity');
			return $this->response;
		}

		$saver = new GenericIndex();
		if(!$saver->save($data)){
			$this->response->setStatusCode('401', 'Could not save data');
			$this->response->setJsonContent($saver->getErrorMessages());
			return $this->response;
		}

		$this->response->setJsonContent($data);
		return $this->response;
	}

	public function Update()
	{
		$dataReceiver = new DataReceiver(new Phalcon\Http\Request());
		$data = $dataReceiver->GetDataFromFields('POST', $this->config->getIndexEntity($entityId)['fields']);
		
		//Needed: collection id, task id, page id	
		$metaInfo = $dataReceiver->GetDataFromFields('POST', ['collection_id', 'task_id', 'page_id']);
		
		$existingEntity = Entities::findById($metaInfo['entity_id']);
		if($existingEntity['user_id'] != "userObj.id" && "tasksUsers.isSuperUser" !== true)
			throw new Exception("Unauthorized access");
		
		$entity->id = $metaInfo['entity_id'];
		$entity->collection_id = $metaInfo['collection_id'];
		$entity->task_id = $metaInfo['task_id'];
		$entity->page_id = $metaInfo['page_id'];
		$entity->data = json_encode($data, true);

		if(!$entity->save()){
			$this->response->setStatusCode('500', 'Could not save entity');
			return $this->response;
		}

		$saver = new GenericIndex();
		if(!$saver->save($data)){
			$this->response->setStatusCode('401', 'Could not save data');
			$this->response->setJsonContent($saver->getErrorMessages());
			return $this->response;
		}

		$this->response->setJsonContent($data);
		return $this->response;		
	}

	public function Delete($id)
	{
		//Needed: collection id, task id, page id	
		$metaInfo = $dataReceiver->GetDataFromFields('POST', ['collection_id', 'task_id', 'page_id']);
		
		$existingEntity = Entities::findById($metaInfo['entity_id']);
		if($existingEntity['user_id'] != "userObj.id" && "tasksUsers.isSuperUser" !== true)
			throw new Exception("Unauthorized access");
		
		$model = new GenericIndex();
		$model->setContext($existingEntity);
		$model->delete();

		if($existingEntity.delete() !== true){
			$this->response->setStatusCode('500', 'Could not save entity');	
		}
		else{
			$this->response->setStatusCode('401', 'Entity deleted');
		}

		return $this->response;
	}
	
	public function insert($entityId)
	{
				/*
		Flow:
			Check user access rights (authorize and authenticate)
			Get configuration for the current collection and volume
			Validate input
			Build insert statement
			Save data
			Update stats
			Return response
		 */
		
		//if(!$user->hasAccess)
		//	throw new Exeception("You don't have access to this action");
		//	
		//if(!$user->isAuthorized)
		//	throw new Exception("Unauthorized access!");

		//Setting the entity id for usage in controller and models
        $this->getDI()->set('currentEntityId', function() use ($entityId){
            return $entityId;
        });

		$this->response = new \Phalcon\Http\Response();
		$entity = new GenericIndex();
		$dataReceiver = new DataReceiver(new Phalcon\Http\Request());

		$valuesFieldsMap = $dataReceiver->GetDataFromFields('POST', $this->config->getIndexEntity($entityId)['fields']);

		if(!$entity->save($valuesFieldsMap)){
			foreach($entity->getMessages() as $message){
				$errorMessages[] = $message;
			}

			if(count($errorMessages) > 0){
				$this->response->setStatusCode('??', 'validation error');
				$this->response->setJsonContent($errorMessages);
			}

			return false;
		}

		return true;
	}
}